<?php

namespace Drupal\contextual_range_filter\Plugin\views\argument;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\argument\Date;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\contextual_range_filter\ContextualRangeFilter;

/**
 * Argument handler to accept a date range.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("date_range")
 */
class DateRange extends Date {

  /**
   * Overrides Drupal\views\Plugin\views\argument\Formula::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    switch ($options['id']) {

      case 'changed_year':
      case 'created_year':
        $this->format = 'Y';
        $this->argFormat = 'Y';
        break;

      case 'changed_year_month':
      case 'created_year_month':
        $this->format = 'F Y';
        $this->argFormat = 'Ym';
        break;

      case 'changed_month':
      case 'created_month':
        $this->format = 'F';
        $this->argFormat = 'm';
        break;

      case 'changed_week':
      case 'created_week':
        $this->format = 'w';
        $this->argFormat = 'W';
        break;

      case 'changed_day':
      case 'created_day':
        $this->format = 'j';
        $this->argFormat = 'd';
        break;

      // 'changed':
      // 'changed_fulldate':
      // 'created':
      // 'created_fulldate':
      default:
        $this->format = 'F j, Y';
        // Should we allow an optional appended time-of-day, eg 'Ymd H:i:s'?
        $this->argFormat = 'Ymd';
        break;
    }
  }

  /**
   * Define our options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['relative_dates'] = ['default' => TRUE];
    $options['break_phrase'] = ['default' => FALSE];
    $options['not'] = ['default' => FALSE];
    return $options;
  }

  /**
   * Build the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['description']['#markup'] = t('Contextual date range filter values are taken from the URL.');

    $form['more']['#open'] = TRUE;

    $form['relative_dates'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow relative date ranges'),
      '#description' => t('If ticked, offsets from the current date may be specified.<br/>Example: <strong>2 weeks ago--yesterday"</strong>'),
      '#default_value' => $this->options['relative_dates'],
      '#group' => 'options][more',
    ];
    // Allow passing multiple values.
    $form['break_phrase'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow multiple date ranges'),
      '#description' => t('If selected, multiple date ranges may be specified by stringing them together with plus signs.<br/>Example: <strong>19990101--20051231+20130701--20140630</strong>'),
      '#default_value' => $this->options['break_phrase'],
      '#group' => 'options][more',
    ];

    $form['not'] = [
      '#type' => 'checkbox',
      '#title' => t('Exclude'),
      '#description' => t('Negate the range. If selected, output matching the specified date range(s) will be excluded, rather than included.'),
      '#default_value' => !empty($this->options['not']),
      '#group' => 'options][more',
    ];
  }

  /**
   * Title override.
   *
   * Required because of range version of views_break_phrase() in this function.
   */
  public function title() {
    if (!$this->argument) {
      return $this->definition['empty field name'] ?: t('Uncategorized');
    }
    if (!empty($this->options['break_phrase'])) {
      $this->breakPhraseRange($this->argument);
    }
    else {
      $this->value = [$this->argument];
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
   * Prepare the range query where clause.
   *
   * @param bool $group_by
   *   Whether to apply grouping.
   */
  public function query($group_by = FALSE) {

    $this->ensureMyTable();

    if (!empty($this->options['break_phrase'])) {
      // From "Allow multple ranges" checkbox.
      $this->breakPhraseRange($this->argument);
    }
    else {
      $this->value = [$this->argument];
    }
    $formula = $this->getFormula();
    $range_conversion = empty($this->options['relative_dates']) ? NULL : [$this, 'convertRelativeDateRange'];
    ContextualRangeFilter::buildRangeQuery($this, $formula, $range_conversion);
  }

  /**
   * Converts relative date range, "six months ago--now" to absolute date range.
   *
   * The format used for the absolute date range is the one set on this plugin.
   *
   * @param string $from
   *   The tart date of the range.
   * @param string $to
   *   The end date of the range.
   *
   * @return array
   *   Array of 2 strings.
   */
  public function convertRelativeDateRange($from, $to) {
    $format = $this->argFormat;
    if (!empty($from)) {
      $abs_from = strtotime($from);
      $from = empty($abs_from) ? date($format) : date($format, $abs_from);
    }
    if (!empty($to)) {
      $abs_to = strtotime($to);
      $to = empty($abs_to) ? date($format) : date($format, $abs_to);
    }
    return [$from, $to];
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
