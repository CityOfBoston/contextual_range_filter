<?php

/**
 * @file
 * Definition of Drupal\contextual_range_filter\Plugin\views\argument_validator\NumericRange
 */

namespace Drupal\contextual_range_filter\Plugin\views\argument_validator;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\argument_validator\Numeric;

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
class NumericRange extends Numeric {

  function validate_argument($argument) {
    $ranges = preg_split('/[+ ]/', $argument); // '+' may arrive as space

    foreach ($ranges as $range) {
      $minmax = explode(CONTEXTUAL_RANGE_FILTER_SEPARATOR1, $range);
      if (count($minmax) < 2) {
        $minmax = explode(CONTEXTUAL_RANGE_FILTER_SEPARATOR2, $range);
      }
      if (count($minmax) < 2) {
        // Not a range but single value. Delegate to parent class.
        if (!parent::validate_argument($argument)) {
          return FALSE;
        }
      }
      elseif (!(
        (parent::validate_argument($minmax[0]) && parent::validate_argument($minmax[1]) && $minmax[0] <= $minmax[1]) ||
        (empty($minmax[0]) && parent::validate_argument($minmax[1])) ||
        (empty($minmax[1]) && parent::validate_argument($minmax[0])))) {
        return FALSE;
      }
    }
    return TRUE;
  }
}
