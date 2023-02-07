<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Currencies\EUR;

use Mistralys\CurrencyParser\BaseEURFormat;
use Mistralys\CurrencyParser\Currencies\EUR;

/**
 * @property EUR $currency
 */
class EUR_FR extends BaseEURFormat
{
    public function getThousandsSeparator(): string
    {
        return ' ';
    }

    public function getArithmeticSeparator(): string
    {
        return ' ';
    }
}
