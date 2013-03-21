<?php

/**
 * @file
 * Definition of Drupal\contextual_range_filter\Plugin\views\argument\NumericRange
 */

namespace Drupal\contextual_range_filter\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Component\Annotation\Plugin;

/**
 * Argument handler to accept a numeric range.
 *
 * @Plugin(
 *   id = "numeric_range",
 *   module = "contextual_range_filter"
 * )
 */
class NumericRange extends Numeric {

  protected function defineOptions() {
    return parent::defineOptions();
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['more']['#collapsed'] = FALSE;

    $form['break_phrase']['#title'] = t('Allow multiple numeric ranges');
    $form['break_phrase']['#description'] = t('If selected, multiple ranges may be specified by stringing them together with plus signs.<br/>Example: <strong>29--29.95+100--250</strong>');

    $form['not'] = array(
      '#type' => 'checkbox',
      '#title' => t('Exclude'),
      '#description' => t('Negate the range. If selected, output matching the specified numeric range(s) will be excluded, rather than included.'),
      '#default_value' => !empty($this->options['not']),
      '#fieldset' => 'more',
    );
  }

  /**
   * Title override.
   *
   * Required because of range version of views_break_phrase() in this function.
   */
  function title() {
    if (!$this->argument) {
      return isset($this->definition['empty field name']) ? $this->definition['empty field name'] : t('Uncategorized');
    }
    if (!empty($this->options['break_phrase'])) {
      $this->viewsBreakPhraseRange($this->argument);
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

  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    if (!empty($this->options['break_phrase'])) { // from "Allow multple ranges" checkbox
      $this->viewsBreakPhraseRange($this->argument);
    }
    else {
      $this->value = array($this->argument);
    }
    contextual_range_filter_build_range_query($this);
  }

  /**
   * Break xfrom--xto+yfrom--yto+zfrom--zto into an array or ranges.
   *
   * @param $str
   *   The string to parse.
   */
  function viewsBreakPhraseRange($str) {
    if (empty($str)) {
      return;
    }
    $this->value = preg_split('/[+ ]/', $str);
    $this->operator = 'or';
    // Keep an 'error' value if invalid ranges were given.
    // A single non-empty value is ok, but a plus sign without values is not.
    if (count($this->value) > 1 && (empty($this->value[0]) || empty($this->value[1]))) {
      $this->value = FALSE; // used in $this->title()
    }
  }

}
