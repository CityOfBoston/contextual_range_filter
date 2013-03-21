<?php

/**
 * @file
 * Definition of Drupal\contextual_range_filter\Plugin\views\argument\StringRange
 */

namespace Drupal\contextual_range_filter\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\String;
use Drupal\Component\Annotation\Plugin;

/**
 * Argument handler to accept a string (e.g. alphabetical) range.
 *
 * @Plugin(
 *   id = "string_range",
 *   module = "contextual_range_filter"
 * )
 */
class StringRange extends String {

  protected function defineOptions() {
    $options = parent::defineOptions();
    // Add 'Exclude' tick box as it is not supplied by the String base class
    $options['not'] = array('default' => FALSE, 'bool' => TRUE);
    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    
    $form['more']['#collapsed'] = FALSE;

    $form['break_phrase']['#title'] = t('Allow multiple string (e.g. alphabetic) ranges');
    $form['break_phrase']['#description'] = t('If selected, multiple ranges may be specified by stringing them together with plus signs.<br/>Example: <strong>a--f+q--y</strong>');

    $form['not'] = array(
      '#type' => 'checkbox',
      '#title' => t('Exclude'),
      '#description' => t('Negate the range. If selected, output matching the specified range(s) will be excluded, rather than included.'),
      '#default_value' => !empty($this->options['not']),
      '#fieldset' => 'more',
    );
  }

  /**
   * Build the query.
   */
  function query($group_by = FALSE) {
    $argument = $this->argument;
    if (!empty($this->options['transform_dash'])) {
      $argument = strtr($argument, '-', ' ');
    }
    if (!empty($this->options['break_phrase'])) {
      $this->breakPhraseString($argument, $this);
    }
    else {
      $this->value = array($argument);
    }
    $this->ensureMyTable();

    if (!empty($this->definition['many to one'])) {
      if (!empty($this->options['glossary'])) {
        $this->helper->formula = TRUE;
      }
      $this->helper->ensureMyTable();
      $this->helper->add_filter();
      return;
    }

    if (empty($this->options['glossary'])) {
      $field = "$this->tableAlias.$this->realField";
    }
    else {
      $field = $this->get_formula();
    }
    contextual_range_filter_build_range_query($this, $field);
  }
}
