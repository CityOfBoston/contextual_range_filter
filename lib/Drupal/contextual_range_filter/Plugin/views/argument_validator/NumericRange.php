<?php

/**
 * @file
 * Definition of Drupal\contextual_range_filter\Plugin\views\argument_validator\NumericRange
 */

namespace Drupal\contextual_range_filter\Plugin\views\argument_validator;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Validate whether an argument is a numeric range.
 *
 * A valid range is either a valid single number or a range of the form:
 *  xfrom--xto  or  xfrom--  or  --xto
 * Instead of the double-hyphen a colon may be used.
 *
 * @Plugin(
 *   id = "numeric_range",
 *   title = @Translation("Numeric Range")
 * )
 */
class NumericRange extends ArgumentValidatorPluginBase {

  function validate_argument($argument) {
    $ranges = preg_split('/[+ ]/', $argument); // '+' may arrive as space

    foreach ($ranges as $range) {
      $minmax = explode(CONTEXTUAL_RANGE_FILTER_SEPARATOR1, $range);
      if (count($minmax) < 2) {
        $minmax = explode(CONTEXTUAL_RANGE_FILTER_SEPARATOR2, $range);
      }
      if (count($minmax) < 2) {
        // Not a range but single value. Must be numeric.
        if (is_numeric($range)) {
          continue;
        }
        return FALSE;
      }
      if (!(
        (is_numeric($minmax[0]) && is_numeric($minmax[1]) && $minmax[0] <= $minmax[1]) ||
        (empty($minmax[0]) && is_numeric($minmax[1])) ||
        (empty($minmax[1]) && is_numeric($minmax[0])))) {
        return FALSE;
      }
    }
    return TRUE;
  }
}
