<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Formatter;

use Mistralys\CurrencyParser\Interfaces\ReusableFormatterInterface;
use Mistralys\CurrencyParser\PriceMatch;

class ReusableLocaleFormatter extends BaseLocaleFormatter implements ReusableFormatterInterface
{
    public function format(PriceMatch $price): string
    {
        return $this->_format($price);
    }
}
