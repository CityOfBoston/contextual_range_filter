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
          //Debug: just to see if the range filter can be created
          //$manager = Views::pluginManager('argument');
          //$argument_handler = $manager->createInstance($field_data['argument']['id'], $field_data['argument']);
        }
      }
    }
  }
}

/**
 * Build a range query based on the ranges passed in.
 */
function contextual_range_filter_build_range_query($views_handler_argument, $field = NULL) {

  if (!isset($views_handler_argument) || $views_handler_argument->value === FALSE) {
    return;
  }
  $real_field = $views_handler_argument->realField;
  if (!isset($field)) {
    $field = "$views_handler_argument->tableAlias.$real_field"; // e.g. field_datafield_price.field_price_value
  }
  $is_not = !empty($views_handler_argument->options['not']); // from "Exclude: tickbox
  $null_check = $is_not ? "OR $field IS NULL" : '';

  // All WHERE clauses are OR-ed or AND-ed together in the same group.
  // Note: NOT (a OR b OR c) == (NOT a) AND (NOT b) AND (NOT c)
  $group = $views_handler_argument->query->set_where_group($is_not ? 'AND' : 'OR');

  foreach ($views_handler_argument->value as $range) {

    $placeholder = $views_handler_argument->query->placeholder($real_field);

    list($from, $to) = contextual_range_filter_split($range);

    if ($to === FALSE) { // single value, not a range
      $operator = $is_not ? '!=' : '=';
      $views_handler_argument->query->add_where_expression($group, "$field $operator $placeholder $null_check", array($placeholder => $range));
    }
    elseif ($from != '' && $to != '') { // from--to
      $operator = $is_not ? 'NOT BETWEEN' : 'BETWEEN';
      $placeholder_from = $placeholder;
      $placeholder_to = $views_handler_argument->query->placeholder($real_field);
      $views_handler_argument->query->add_where_expression($group, "$field $operator $placeholder_from AND $placeholder_to $null_check",
        array($placeholder_from => $from, $placeholder_to => $to));
    }
    elseif ($from != '') { // from--
      $operator = $is_not ? '<' : '>=';
      $views_handler_argument->query->add_where_expression($group, "$field $operator $placeholder $null_check", array($placeholder => $from));
    }
    elseif ($to != '') { // --to
      $operator = $is_not ? '>' : '<=';
      $views_handler_argument->query->add_where_expression($group, "$field $operator $placeholder $null_check", array($placeholder => $to));
    }
  }
}
