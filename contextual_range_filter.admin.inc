<?php

/**
 * @file
 * contextual_range_filter.admin.inc
 */

/**
 * Form builder; select contextual range filters.
 *
 * From a UI perspective it would make sense to simply have a tick-box on the
 * the Views contextual filter configuration panel. The problem is that at that
 * point the argument plugin class has already been selected and instantiated.
 * This is why we make the user define the contextual filter first, then have
 * them select on this page which contextual filters need to be converted to
 * range filters.
 *
 * @ingroup forms
 * @see contextual_range_filter_settings_sumbit()
 */
function contextual_range_filter_settings($form, $form_state) {

  $range_fields = array(
    'numeric_field_names' => array(), // includes properties like node:nid
    'string_field_names' => array(),  // includes properties like node:title
  );
  $class_path = 'Drupal\views\Plugin\views\argument';

  foreach (views_get_all_views() as $view) {
    foreach ($view->get('display') as $display) {

      if (!empty($display['display_options']['arguments'])) {

        foreach ($display['display_options']['arguments'] as $contextual_filter) {
          
          if ($contextual_filter['table'] == 'search_node_links_to') {
            // Temporary: produces error in views_get_handler() ... @todo
            continue;
          }
          $argument_handler = views_get_handler($contextual_filter['table'], $contextual_filter['field'], 'argument');

          if ($argument_handler) {

            $class = $argument_handler->definition['class'];

            // Does this class extend the Numeric or String classes?
            // @see views/lib/Drupal/views/Plugin/views/argument/Numeric.php
            // Note: lists have a class of Numeric or String so nothing specific
            //       can be done for lists...
            $is_numeric_handler = ($class == "$class_path\Numeric") || is_subclass_of($class, "$class_path\Numeric");
            $is_string_handler  = ($class == "$class_path\String")  || is_subclass_of($class, "$class_path\String");

            if ($is_numeric_handler || $is_string_handler) {

              // For every $display we get a number of fields.
              // Should we allow selection per display AND per field ?
              // Currently we find --but don't add-- the "duplicates".

              $title = $argument_handler->definition['group'] . ':' . $argument_handler->definition['title'];
              $machine_name = $contextual_filter['table'] . ':' . $contextual_filter['field'];

              $view_name = $view->get('human_name') ?: $view->get('name');
              if (views_view_is_disabled($view)) {
                $view_name .= ' (' . t('disabled') . ')';
              }

              if ($is_numeric_handler) {
                _contextual_range_filter_add_to_range_fields($range_fields['numeric_field_names'][$machine_name], $title, $view_name);
              }
              elseif ($is_string_handler) {
                _contextual_range_filter_add_to_range_fields($range_fields['string_field_names'][$machine_name], $title, $view_name);
              }
            }
          }
        }
      }
    }
  }
  $config = config('contextual_range_filter.settings');

  $form['field_names'] = array(
    '#type' => 'fieldset',
    '#title' => t('Select contextual filters to be converted to contextual range filters'),
    '#description' => t('You must first add contextual filters to your views for them to appear on this page.<br/>Once added you use contextual range filters like contextual date filters in that you separate "from" and "to" values by a double hyphen, e.g. http://yoursite.com/yourview/50--149.95 <br/>You may omit the from or to values to create open-ended ranges, e.g. http://yoursite.com/yourview/--149.95 or http://yoursite.com/yourview/50-- <br/>All filter ranges are inclusive of from and to values. Instead of "<strong>--</strong>" you may use a colon "<strong>:</strong>"')
  );
  $types = array(t('numeric'), t('string'));
  $type = reset($types);
  foreach ($range_fields as $key => $data) {
    $options = array();
    foreach ($data as $full_name => $view_names) {
      $options[$full_name] = t('%field in view(s): @views', array(
        '%field' => reset($view_names), '@views' => implode(', ', array_slice($view_names, 1))));
    }
    $form['field_names'][$key] = array(
      '#type' => 'checkboxes',
      '#title' => t('Select which of the below contextual <strong>@type</strong> filters should be converted to @type <strong>range</strong> filters:', array(
        '@type' => $type)),
      '#default_value' => $config->get($key) ?: array(),
      '#options' => $options,
      '#description' => t('')
    );
    $type = next($types);
  }
  return system_config_form($form, $form_state);
}

/**
 * Save contextual range filter configuration.
 *
 * @ingroup forms
 * @see contextual_range_filter_settings()
 */
function contextual_range_filter_settings_submit($form, &$form_state) {
  config('contextual_range_filter.settings')
    ->set('numeric_field_names', $form_state['values']['numeric_field_names'])
    ->set('string_field_names',  $form_state['values']['string_field_names'])
    ->save();
}

function _contextual_range_filter_add_to_range_fields(&$range_field_view_names, $title, $view_name) {
  if (!isset($range_field_view_names)) {
    $range_field_view_names = array($title);
  }
  if (!in_array($view_name, $range_field_view_names)) {
    $range_field_view_names[] = $view_name;
  }
}