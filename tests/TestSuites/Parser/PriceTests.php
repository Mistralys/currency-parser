<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Parser;

use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;
use function Mistralys\CurrencyParser\parsePrice;

final class PriceTests extends CurrencyParserTestCase
{
    public function test_getDecimals() : void
    {
        $price = parsePrice('$50.42');

        $this->assertSame('42', $price->getDecimals());
        $this->assertSame(42, $price->getDecimalsInt());
        $this->assertSame(5042, $price->getAsMoney());
    }

    public function test_getDecimalsGermanHyphen() : void
    {
        $price = parsePrice('50,- EUR');

        $this->assertSame('-', $price->getDecimals());
        $this->assertSame(0, $price->getDecimalsInt());
        $this->assertSame(5000, $price->getAsMoney());
    }
}