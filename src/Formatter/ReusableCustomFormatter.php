<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Formatter;

use Mistralys\CurrencyParser\PriceMatch;

class ReusableCustomFormatter extends BaseCustomFormatter
{
    public function format(PriceMatch $price): string
    {
        return $this->_format($price);
    }
}
