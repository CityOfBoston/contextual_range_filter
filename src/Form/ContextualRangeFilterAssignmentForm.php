<?php

namespace Drupal\contextual_range_filter\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ContextualRangeFilterAssignmentForm object
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
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
    $plugin_data = [];
    foreach (\Drupal\views\ViewExecutable::getPluginTypes() as $plugin_type) {
      $plugin_data[$plugin_type] = \Drupal\views\Views::pluginManager($plugin_type)->getDefinitions();
    }

    foreach ($this->entityTypeManager->getStorage('view')->loadMultiple() as $view) {
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
            //$is_numeric_handler = is_a($class, "$class_path\NumericArgument", TRUE);
            $is_string_handler = is_a($class, "$class_path\StringArgument", TRUE);
            $is_numeric_handler = !$is_date_handler && !$is_string_handler;
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
                $view_name .= ' (' . $this->t('disabled') . ')';
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
      '#title' => $this->t('Select contextual filters to be converted to contextual range filters'),
    );
    $config = $this->configFactory->get('contextual_range_filter.settings');
    $labels = array($this->t('date'), $this->t('numeric'), $this->t('string'));
    $label = reset($labels);
    foreach ($range_fields as $type => $data) {
      $options = array();
      foreach ($data as $full_name => $view_names) {
        $replace = [
          '%field' => reset($view_names),
          '@views' => implode(', ', array_slice($view_names, 1)),
        ];
        $options[$full_name] = $this->t('%field in view(s): @views', $replace);
        $form['#view_names'][$full_name] = array_slice($view_names, 1);
      }
      $form['field_names'][$type] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Select which of the below contextual <em>@label</em> filters should be converted to <em>@label range</em> filters:', array(
          '@label' => $label)),
        '#default_value' => $config->get($type) ?: array(),
        '#options' => $options,
      );
      $label = next($labels);
    }
    $element['#cache']['tags'] = $config->getCacheTags();
    $form[] = $element;
 
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear out stuff we're not interested with.
    $types = ['numeric', 'string', 'date'];
    $config = $this->configFactory->getEditable('contextual_range_filter.settings');
    foreach ($types as $type) {
      $field_names = $type . '_field_names';
      $range_type = $type . '_range';
      // Clear out the unticked boxes.
      $filters = array_filter($form_state->getValue($field_names));
      $prev_filters = $config->get($field_names) ?: array();
      $added_filters = array_diff($filters, $prev_filters);
      $removed_filters = array_diff($prev_filters, $filters);
      $changed_filters = array_merge($added_filters, $removed_filters);

      if (empty($changed_filters)) {
        continue;
      }
      $config->set($field_names, $filters);

      // Find corresponding Views and save them.
      $changed_view_names = array();
      foreach ($changed_filters as $filter_name) {
        $changed_view_names = array_merge($changed_view_names, $form['#view_names'][$filter_name]);
      }

      // We cycle through all the views. If the view is flagged as needing to be
      // edited, we check if any of the changed filters is present in that view.
      // If we find one, we set its value depending if we are adding or removing
      // the new plugin.
      foreach ($this->entityTypeManager->getStorage('view')->loadMultiple() as $view) {
        $view_name = $view->get('label');
        if (in_array($view_name, $changed_view_names)) {
          $display = &$view->getDisplay('default');
          foreach ($changed_filters as $filter_name)  {
            $field_name = substr($filter_name, strpos($filter_name, ":") + 1);
            if (isset($display['display_options']['arguments'][$field_name]['plugin_id'])) {
              $new_value = in_array($filter_name, $added_filters) ? $range_type : $type;
              $display['display_options']['arguments'][$field_name]['plugin_id'] = $new_value;
            }
            drupal_set_message($this->t('Updated contextual filter(s) on view %view_name.', array('%view_name' => $view_name)));
            $view->save();
          }
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
