<?php
/**
 * @file
 * contextual_range_filter_views.inc
 *
 * Defines the Contextual Range Filters plugins and hooks.
 */

/**
 * Implements hook_views_data_alter().
 *
 * Changes argument handlers to corresponding range argument handlers.
 *
 * This function finds all field and property argument handlers.
 */
function contextual_range_filter_views_data_alter(&$data) {

  $numeric_field_names = array_filter(variable_get('contextual_range_filter_numeric_field_names', array()));
  $string_field_names  = array_filter(variable_get('contextual_range_filter_string_field_names', array()));
  $list_field_names    = array_filter(variable_get('contextual_range_filter_list_field_names', array()));
  if (!empty($numeric_field_names) || !empty($string_field_names)) {

    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if (isset($field_data['argument']['handler'])) {
          // If listed on the configuration page, switch this argument handler
          // to its corresponding argument RANGE handler
          $full_name = "$table_name:$field_name";
          if (in_array($full_name, $numeric_field_names)) {
            $data[$table_name][$field_name]['argument']['handler'] = 'contextual_range_filter_handler_argument_numeric_range';
          }
          elseif (in_array($full_name, $string_field_names)) {
            $data[$table_name][$field_name]['argument']['handler'] = 'contextual_range_filter_handler_argument_string_range';
          }
          elseif (in_array($full_name, $list_field_names)) {
            $data[$table_name][$field_name]['argument']['handler'] = 'contextual_range_filter_handler_argument_list_range';
          }
        }
      }
    }
  }
}

/**
 * Implements hook_views_plugins().
 *
 * Defines the Numeric Range contextual filter validator.
 */
function contextual_range_filter_views_plugins() {
  $plugins = array(
    'argument validator' => array(
      'numeric range' => array(
        'title' => t('Numeric or Numeric Range'),
        'handler' => 'contextual_range_filter_plugin_argument_validate_numeric_range',
      ),
      'string range' => array(
        'title' => t('Safe Text or Safe Text Range'),
        'handler' => 'contextual_range_filter_plugin_argument_validate_string_range',
      )
    )
  );
  return $plugins;
}

/**
 * Implements hook_field_views_data_alter().
 *
 * Called from field_views_data() (views/modules/field.views.inc), after
 * it sets $data = field_views_field_default_views_data($field).
 * This hook does not include plain properties like 'node:title'.
 *
function contextual_range_filter_field_views_data_alter(&$data, $field) {
}
 */
