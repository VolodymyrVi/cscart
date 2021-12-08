<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Addons\VendorRating\Calculator;

use DivisionByZeroError;
use Tygh\Addons\VendorRating\Exception\CalculationException;

/**
 * Class EvalBackend implements a fallback calculator backend that uses eval internally.
 * This class is used only on PHP 5.x.
 *
 * @package Tygh\Addons\VendorRating\Calculator
 */
class EvalBackend implements BackendInterface
{
    /**
     * @param string                                          $formula
     * @param \Tygh\Addons\VendorRating\Calculator\Variable[] $variables
     *
     * @return int|float
     * @throws \Tygh\Addons\VendorRating\Exception\CalculationException
     * @throws \DivisionByZeroError
     */
    public function evaluate($formula, array $variables)
    {
        foreach ($variables as $variable) {
            $formula = strtr($formula, [$variable->getShortCode() => $variable->getValue()]);
        }

        $formula = '$result = ' . $formula . ';';

        $result = 0;

        set_error_handler([$this, 'errorHandler']);
        @eval($formula);
        restore_error_handler();

        return $result;
    }

    protected function errorHandler($errno, $error)
    {
        if ($errno === 2) {
            throw new DivisionByZeroError();
        }

        throw new CalculationException($error, $errno);
    }
}
