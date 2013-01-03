<?php

/**
 * @file
 * Definition of contextual_filter_range_handler_argument_list_range.
 */

/**
 * Argument handler for arguments that are list ranges.
 *
 * @ingroup views_argument_handlers
 */
class contextual_range_filter_handler_argument_list_range extends contextual_range_filter_handler_argument_range {

  function option_definition() {
    return parent::option_definition();
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['break_phrase']['#description'] = t('If selected, multiple list ranges may be specified by stringing them together with plus signs. You must use the list keys, i.e. the numbers, not the human-readable names.<br/>Example: <strong>5--8+14--22</strong><br/>Be aware that while list keys are meant to be integers, core stores these as strings, so make sure that your ranges do not straddle 10 or 100 or the results will be unexpected.');
  }
}
