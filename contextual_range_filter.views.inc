<?php
/**
 * @file
 * contextual_range_filter_views.inc
 *
 * Defines the Contextual Range Filters plugins and hooks.
 */
use Drupal\views\Views;

/**
 * Implements hook_views_data_alter().
 *
 * Replaces argument handlers by their corresponding RANGE argument handlers.
 *
 * This function finds all field and (node) property argument handlers.
 */
function contextual_range_filter_views_data_alter(&$data) {
  
  $config = config('contextual_range_filter.settings');
  $numeric_field_names = $config->get('numeric_field_names');
  $string_field_names  = $config->get('string_field_names');

  if (!empty($numeric_field_names) || !empty($string_field_names)) {

    foreach ($data as $table_name => $table_data) {
      foreach ($table_data as $field_name => $field_data) {
        if (isset($field_data['argument']['id'])) {
          // If listed on the configuration page, replace this argument handler
          // by its corresponding argument RANGE handler
          $full_name = "$table_name:$field_name";
          if (in_array($full_name, $numeric_field_names)) {
            $data[$table_name][$field_name]['argument']['id'] = 'numeric_range';
          }
          elseif (in_array($full_name, $string_field_names)) {
            $data[$table_name][$field_name]['argument']['id'] = 'string_range';
          }
        }
      }
    }
  }
}
