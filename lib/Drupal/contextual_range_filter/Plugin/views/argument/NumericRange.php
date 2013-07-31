<?php

/**
 * @file
 * NumericRange.php
 * 
 * Contains Drupal\contextual_range_filter\Plugin\views\argument\NumericRange
 */

namespace Drupal\contextual_range_filter\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Component\Annotation\PluginID;

/**
 * Argument handler to accept a numeric range.
 *
 * @PluginID("numeric_range")
 */
class NumericRange extends Numeric {
  
  /**
   * {@inheritdoc}.
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['more']['#collapsed'] = FALSE;

    $form['break_phrase']['#title'] = t('Allow multiple numeric ranges');
    $form['break_phrase']['#description'] = t('If selected, multiple ranges may be specified by stringing them together with plus signs.<br/>Example: <strong>29--29.95+100--250</strong>');

    $form['not']['#description'] = t('Negate the range. If selected, output matching the specified numeric range(s) will be excluded, rather than included.');
  }

  /**
   * Title override.
   *
   * Required because of range version of breakPhrase() in this function.
   */
  public function title() {
    if (!$this->argument) {
      return $this->definition['empty field name'] ?: t('Uncategorized');
    }
    if (!empty($this->options['break_phrase'])) {
      $this->breakPhraseRange($this->argument);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'or';
    }
    if ($this->value === FALSE) {
      return $this->definition['invalid input'] ?: t('Invalid input');
    }
    if (empty($this->value)) {
      return $this->definition['empty field name'] ?: t('Uncategorized');
    }
    return implode($this->operator == 'or' ? ' + ' : ', ', $this->value);
  }

  /**
   * {@inheritdoc}.
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    // Check "Allow multple ranges" checkbox.
    if (!empty($this->options['break_phrase'])) {
      $this->breakPhraseRange($this->argument);
    }
    else {
      $this->value = array($this->argument);
    }
    contextual_range_filter_build_range_query($this);
  }

  /**
   * Break xfrom--xto+yfrom--yto+zfrom--zto into an array or ranges.
   *
   * @param string $str
   *   The string to parse.
   */
  protected function breakPhraseRange($str) {
    if (empty($str)) {
      return;
    }
    $this->value = preg_split('/[+ ]/', $str);
    $this->operator = 'or';
    // Keep an 'error' value if invalid ranges were given.
    // A single non-empty value is ok, but a plus sign without values is not.
    if (count($this->value) > 1 && (empty($this->value[0]) || empty($this->value[1]))) {
      $this->value = FALSE;
    }
  }

}
