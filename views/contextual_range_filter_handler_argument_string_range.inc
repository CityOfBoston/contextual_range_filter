<?php

/**
 * @file
 * Definition of contextual_range_filter_handler_argument_string_range.
 */

/**
 * Argument handler to implement string range arguments.
 *
 * @ingroup views_argument_handlers
 */
class contextual_range_filter_handler_argument_string_range extends views_handler_argument_string {

  function option_definition() {
    $options = parent::option_definition();
    $options['not'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['more']['#collapsed'] = FALSE;

    $form['break_phrase']['#title'] = t('Allow multiple ranges');
    $form['break_phrase']['#description'] = t('If selected, multiple ranges may be specified by stringing them together with plus signs. Example: <strong>A--F+Q--Y</strong>');

    $form['not'] = array(
      '#type' => 'checkbox',
      '#title' => t('Exclude'),
      '#description' => t('Negate the range. If selected, output matching the specified ranges will be excluded, rather than included.'),
      '#default_value' => !empty($this->options['not']),
      '#fieldset' => 'more',
    );
  }

  /**
   * Build the query based upon the formula
   */
  function query($group_by = FALSE) {
    $argument = $this->argument;
    if (!empty($this->options['transform_dash'])) {
      $argument = strtr($argument, '-', ' ');
    }
    if (!empty($this->options['break_phrase'])) {
      views_break_phrase_string($argument, $this);
    }
    else {
      $this->value = array($argument);
    }

    if (!empty($this->definition['many to one'])) {
      if (!empty($this->options['glossary'])) {
        $this->helper->formula = TRUE;
      }
      $this->helper->ensure_my_table();
      $this->helper->add_filter();
      return;
    }

    $this->ensure_my_table();
    if (empty($this->options['glossary'])) {
      $field = "$this->table_alias.$this->real_field";
    }
    else {
      $field = $this->get_formula();
    }
    $is_not = !empty($this->options['not']); // from "Exclude" tickbox
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

}

