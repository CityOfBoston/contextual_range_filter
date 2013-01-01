<?php

/**
 * @file
 * contextual_range_filter.admin.inc
 */

/**
 * Menu callback for selecting which filters should become range filters.
 *
 * From a UI perspective it would make sense to simply have a tick-box on the
 * the Views contextual filter configuration panel. The problem is that at that
 * point the argument handler class has already been selected and instantiated.
 * This is why we make the user define the contextual filter first, then have
 * them select on this page which contextual filters need to be converted to
 * range filters.
 */
function contextual_range_filter_admin_config() {

  $numeric_fields = array(); // includes properties like nid
  $string_fields = array();  // includes properties like node:title

  foreach (views_get_all_views() as $view) {
    foreach ($view->display as $display_id => $display) {

      if ($view->set_display($display_id)) {

        $items = $view->get_items('argument', $display_id);

        foreach ($items as $item) {

          $table_data = views_fetch_data($item['table']);
          $field_data = $table_data[$item['field']];

          if (isset($field_data['argument']['handler'])) {

            $handler = $field_data['argument']['handler'];
            $is_numeric_handler = strpos($handler, '_handler_argument_numeric');
            $is_string_handler  = strpos($handler, '_handler_argument_string');

            if ($is_numeric_handler || $is_string_handler) {

              // We get one field for every $display_id.
              // Should we allow selection per field AND per display ?
              // If not, then we can simplify these nested loops...
              // Currently we find but don't add the "duplicates".
              $full_name = $item['table'] . ':' . $item['field'];
              $view_name = empty($view->human_name) ? $view->name : $view->human_name;
              if (views_view_is_disabled($view)) {
                $view_name .= ' (' . t('disabled') . ')';
              }

              if ($is_numeric_handler) {
                if (!isset($numeric_fields[$full_name])) {
                  $numeric_fields[$full_name] = array($field_data['title']);
                }
                if (!in_array($view_name, $numeric_fields[$full_name])) {
                  $numeric_fields[$full_name][] = $view_name;
                }
              }
              else {
                if (!isset($string_fields[$full_name])) {
                  $string_fields[$full_name] = array($field_data['title']);
                }
                if (!in_array($view_name, $string_fields[$full_name])) {
                  $string_fields[$full_name][] = $view_name;
                }
              }
            }
          }
        }
      }
    }
  }
  $form['field_names'] = array(
    '#type' => 'fieldset',
    '#title' => t('Select contextual filters to be converted to contextual range filters'),
    '#description' => t("You must first add contextual filters to your views for them to appear on this page.<br/>Once added you use contextual range filters like contextual date filters in that you separate start and end values by a double hyphen, e.g. <strong>http://yoursite.com/yourview/50--149.95</strong> You may omit the start or end values to create open-ended ranges, e.g. <strong>http://yoursite.com/yourview/--149.95</strong> or <strong>http://yoursite.com/yourview/50--</strong> <br/>Both numeric and alphabetic ranges are inclusive of start and end values. Instead of '<strong>--</strong>' you may use a colon '<strong>:</strong>'")
  );
  foreach ($numeric_fields as $field_name => $view_names) {
    $numeric_fields[$field_name] = t('%field in view: @views', array(
      '%field' => reset($view_names), '@views' => implode(', ', array_slice($view_names, 1))));
  }
  $form['field_names']['contextual_range_filter_numeric_field_names'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Select which of the below contextual <em>numeric</em> filters should be converted to <em>numeric range</em> filters:'),
    '#default_value' => variable_get('contextual_range_filter_numeric_field_names', array()),
    '#options' => $numeric_fields,
    '#description' => t('')
  );

  foreach ($string_fields as $field_name => $view_names) {
    $string_fields[$field_name] = t('%field in view: @views', array(
      '%field' => reset($view_names), '@views' => implode(', ', array_slice($view_names, 1))));
  }
  $form['field_names']['contextual_range_filter_string_field_names'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Select which of the below contextual <em>string</em> filters should be converted to <em>alphabetic range</em> filters:'),
    '#default_value' => variable_get('contextual_range_filter_string_field_names', array()),
    '#options' => $string_fields,
    '#description' => t('')
  );
  return system_settings_form($form);
}