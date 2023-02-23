<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Interfaces;

use Mistralys\CurrencyParser\PriceMatch;

interface ReusableFormatterInterface extends FormatterInterface
{
    public function format(PriceMatch $price) : string;
}
