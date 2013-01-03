<?php

/**
 * @file
 * Definition of contextual_filter_range_handler_argument_range.
 *
 */

/**
 * Base argument handler for arguments that are ranges, numeric, list or string.
 *
 * @ingroup views_argument_handlers
 */
class contextual_range_filter_handler_argument_range extends views_handler_argument_numeric {

  function option_definition() {
    return parent::option_definition();
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['more']['#collapsed'] = FALSE;

    $form['break_phrase']['#title'] = t('Allow multiple ranges');
    $form['break_phrase']['#description'] = t('If selected, multiple ranges may be specified by stringing them together with plus signs.<br/>Example: <strong>1--4+9--12</strong>');

    $form['not'] = array(
      '#type' => 'checkbox',
      '#title' => t('Exclude'),
      '#description' => t('Negate the range. If selected, output matching the specified ranges will be excluded, rather than included.'),
      '#default_value' => !empty($this->options['not']),
      '#fieldset' => 'more',
    );
  }

  /**
   * Title override.
   *
   * Required because of range version of views_break_phrase().
   */
  function title() {
    if (!$this->argument) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : t('Uncategorized');
    }

    if (!empty($this->options['break_phrase'])) {
      $this->views_break_phrase_range($this->argument);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'or';
    }
    if ($this->value === FALSE) {
      return !empty($this->definition['invalid input']) ? $this->definition['invalid input'] : t('Invalid input');
    }
    if (empty($this->value)) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : t('Uncategorized');
    }

    return implode($this->operator == 'or' ? ' + ' : ', ', $this->title_query());
  }

  function query($group_by = FALSE) {
    $this->ensure_my_table();

    if (!empty($this->options['break_phrase'])) { // from "Allow multple ranges" checkbox
      $this->views_break_phrase_range($this->argument);
    }
    else {
      $this->value = array($this->argument);
    }
    if ($this->value === FALSE) {
      return;
    }
    $is_not = !empty($this->options['not']); // from "Exclude: tickbox
    $field = "$this->table_alias.$this->real_field"; // e.g. field_datafield_price.field_price_value
    $null_check = $is_not ? "OR $field IS NULL" : '';

    // All WHERE clauses are OR-ed or AND-ed together in the same group.
    // Note: NOT (a OR b OR c) == (NOT a) AND (NOT b) AND (NOT c)
    $group = $this->query->set_where_group($is_not ? 'AND' : 'OR');

    foreach ($this->value as $value) {

      $placeholder = $this->placeholder();

      list($from, $to) = contextual_range_filter_split($value);

      if ($to === FALSE) { // single value, not a range
        $operator = $is_not ? '!=' : '=';
        $this->query->add_where_expression($group, "$field $operator $placeholder $null_check", array($placeholder => $value));
      }
      elseif (!empty($from) && !empty($to)) { // from--to
        $operator = $is_not ? 'NOT BETWEEN' : 'BETWEEN';
        $placeholder_from = $placeholder;
        $placeholder_to = $this->placeholder();
        $this->query->add_where_expression($group, "$field $operator $placeholder_from AND $placeholder_to $null_check",
          array($placeholder_from => $from, $placeholder_to => $to));
      }
      elseif (!empty($from)) { // from--
        $operator = $is_not ? '<' : '>=';
        $this->query->add_where_expression($group, "$field $operator $placeholder $null_check", array($placeholder => $from));
      }
      elseif (!empty($to)) { // --to
        $operator = $is_not ? '>' : '<=';
        $this->query->add_where_expression($group, "$field $operator $placeholder $null_check", array($placeholder => $to));
      }
    }
  }

  /**
   * Break xfrom--xto+yfrom--yto+zfrom--zto into an array or ranges.
   *
   * @param $str
   *   The string to parse.
   */
  function views_break_phrase_range($str) {

    if (empty($str)) {
      return;
    }
    $this->operator = 'or';
    $this->value = preg_split('/[+ ]/', $str);
    // Keep an 'error' value if invalid ranges were given.
    // A single non-empty value is ok, but a plus sign without values is not.
    if (count($this->value) > 1 && (empty($this->value[0]) || empty($this->value[1]))) {
      $this->value = FALSE; // used in $this->title()
    }
  }

}
