<?php

/**
 * @file
 * NumericRange.php
 *
 * Drupal\contextual_range_filter\Plugin\views\argument_validator\NumericRange
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

  /**
   * {@inheritdoc}.
   */
  public function validateArgument($argument) {
    // Plus sign may arrive as a space, so cover both.
    $ranges = preg_split('/[+ ]/', $argument);

    foreach ($ranges as $range) {
      $minmax = explode(CONTEXTUAL_RANGE_FILTER_SEPARATOR1, $range);
      if (count($minmax) < 2) {
        $minmax = explode(CONTEXTUAL_RANGE_FILTER_SEPARATOR2, $range);
      }
      if (count($minmax) < 2) {
        // Not a range but single value. Delegate to parent class.
        if (!parent::validateArgument($argument)) {
          return FALSE;
        }
      }
      elseif (!(
        (parent::validateArgument($minmax[0]) && parent::validateArgument($minmax[1]) && $minmax[0] <= $minmax[1]) ||
        (empty($minmax[0]) && parent::validateArgument($minmax[1])) ||
        (empty($minmax[1]) && parent::validateArgument($minmax[0])))) {
        return FALSE;
      }
    }
    return TRUE;
  }
}
