<?php

/**
 * @file
 * Definition of contextual_filter_range_handler_argument_numeric_range.
 */

/**
 * Argument handler for arguments that are numeric or numeric ranges.
 *
 * @ingroup views_argument_handlers
 */
class contextual_range_filter_handler_argument_numeric_range extends contextual_range_filter_handler_argument_range {

  function option_definition() {
    return parent::option_definition();
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['break_phrase']['#description'] = t('If selected, multiple ranges may be specified by stringing them together with plus signs.<br/>Example: <strong>100--150+400--499.99</strong>');
  }
}
