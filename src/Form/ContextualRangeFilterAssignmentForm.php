<?php

/**
 * @file
 * Contains \Drupal\system\Form\ContextualRangeFilterAssignmentForm.
 */

namespace Drupal\contextual_range_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Convert selected contextual filters to contextual range filters.
 *
 * From a UI perspective it would make sense to simply have a tick-box on the
 * the Views UI contextual filter config panel. The problem is that at that
 * point the plugin class has already been selected and instantiated.
 * This is why we make the user define the contextual filter first, then have
 * them select on this page which contextual filters need to be converted to
 * range filters.
 */
class ContextualRangeFilterAssignmentForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormID() {
    return 'contextual_range_filter_settings';
  }
  protected function getEditableConfigNames() {
    return [
      'contextual_range_filter.settings',
    ];
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $range_fields = array(
      'date_field_names' => array(),
      'numeric_field_names' => array(),
      'string_field_names' => array(),
    );
    $class_path = 'Drupal\views\Plugin\views\argument';

    $plugin_data = views_get_plugin_definitions();

    foreach (views_get_all_views() as $view) {

      foreach ($view->get('display') as $display) {

        if (!empty($display['display_options']['arguments'])) {

          foreach ($display['display_options']['arguments'] as $contextual_filter) {

            if (empty($contextual_filter['plugin_id'])) {
              // E.g., search back links.
              continue;
            }
            $plugin_id = $contextual_filter['plugin_id'];
            $class = $plugin_data['argument'][$plugin_id]['class'];

            // Does this contextual filter class extend one of the base
            // contextual filter classes?
            // Note: lists have a class of Numeric or String, so nothing special
            // needs or can be done for lists...
            $is_date_handler = is_a($class, "$class_path\Date", TRUE);
            $is_numeric_handler = is_a($class, "$class_path\Numeric", TRUE)
              || is_a($class, 'Drupal\comment\Plugin\views\argument\UserUid', TRUE);
            //  || is_a($class, 'Drupal\taxonomy\Plugin\views\argument\IndexTidDepth', TRUE)
            //  || is_a($class, 'Drupal\taxonomy\Plugin\views\argument\IndexTidDepthModifier', TRUE)
            //  || is_a($class, 'Drupal\views\Plugin\views\argument\ManyToOne', TRUE);
            $is_string_handler = is_a($class, "$class_path\String", TRUE);

            if ($is_date_handler || $is_numeric_handler || $is_string_handler) {

              // For every View $display we get a number of fields.
              // Should we allow selection per display AND per field?
              // Currently we find, but don't add, the "duplicates".
              // @todo: Find something more human-readible than this.
              $title = "$plugin_id: " . $contextual_filter['id'];

              // @todod Taxonomy term depth has Views machine name
              // "taxonomy_term_data:tid", not "node:term_node_tid_depth".
              $machine_name = $contextual_filter['table'] . ':' . $contextual_filter['field'];

              $view_name = $view->get('label');
              if (views_view_is_disabled($view)) {
                $view_name .= ' (' . t('disabled') . ')';
              }

              if ($is_date_handler) {
                $this->addToRangeFields($range_fields['date_field_names'][$machine_name], $title, $view_name);
              }
              elseif ($is_numeric_handler) {
                $this->addToRangeFields($range_fields['numeric_field_names'][$machine_name], $title, $view_name);
              }
              elseif ($is_string_handler) {
                $this->addToRangeFields($range_fields['string_field_names'][$machine_name], $title, $view_name);
              }
            }
          }
        }
      }
    }
    $form['field_names'] = array(
      '#type' => 'fieldset',
      '#title' => t('Select contextual filters to be converted to contextual range filters'),
    );
  //$config = $this->configFactory->get('contextual_range_filter.settings');
    $config = config('contextual_range_filter.settings');
    $labels = array(t('date'), t('numeric'), t('string'));
    $label = reset($labels);
    foreach ($range_fields as $type => $data) {
      $options = array();
      foreach ($data as $full_name => $view_names) {
        $options[$full_name] = t('%field in view(s): @views', array(
          '%field' => reset($view_names), '@views' => implode(', ', array_slice($view_names, 1))));
        $form['#view_names'][$full_name] = array_slice($view_names, 1);
      }
      $form['field_names'][$type] = array(
        '#type' => 'checkboxes',
        '#title' => t('Select which of the below contextual <em>@label</em> filters should be converted to <em>@label range</em> filters:', array(
          '@label' => $label)),
        '#default_value' => $config->get($type) ?: array(),
        '#options' => $options,
      );
      $label = next($labels);
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Clear out stuff we're not interested with.
    form_state_values_clean($form_state);

    $config = $this->configFactory->get('contextual_range_filter.settings');

    foreach ($form_state['values'] as $type => $filters) {
      // Clear out the unticked boxes.
      $filters = array_filter($form_state['values'][$type]);

      $prev_filters = $config->get($type) ?: array();
      $added_filters = array_diff($filters, $prev_filters);
      $removed_filters = array_diff($prev_filters, $filters);
      $changed_filters = array_merge($added_filters, $removed_filters);

      if (empty($changed_filters)) {
        continue;
      }
      $config->set($type, $filters);

      // Find corresponding Views and save them.
      $changed_view_names = array();
      foreach ($changed_filters as $filter_name) {
        $changed_view_names = array_merge($changed_view_names, $form['#view_names'][$filter_name]);
      }
      foreach (views_get_all_views() as $view) {
        $view_name = $view->get('label');
        if (in_array($view_name, $changed_view_names)) {
          drupal_set_message(t('Updated contextual filter(s) on view %view_name.', array('%view_name' => $view_name)));
          $view->save();
        }
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Add a field to the collection of contextual range filter fields.
   *
   * @param array $range_field_view_names
   *   the array to append the supplied field name and view names to
   * @param string $title
   *   the "compound" title to be used
   * @param string $view_name
   *   the name of the view that the field occurs in
   */
  protected function addToRangeFields(&$range_field_view_names, $title, $view_name) {
    if (!isset($range_field_view_names)) {
      $range_field_view_names = array($title);
    }
    if (!in_array($view_name, $range_field_view_names)) {
      $range_field_view_names[] = $view_name;
    }
  }

}
