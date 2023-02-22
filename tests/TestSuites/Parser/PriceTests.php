<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Parser;

use Mistralys\CurrencyParser\PriceFormatter;
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

    public function test_formatText() : void
    {
        $price = parsePrice('50 EUR');

        $this->assertSame('50 EUR', $price->formatText());
    }

    public function test_formatHTML() : void
    {
        $price = parsePrice('50 EUR');

        $this->assertSame('50&#160;EUR', $price->formatHTML());
    }

    public function test_createCustomFormatter() : void
    {
        $price = parsePrice('5000 EUR');

        $this->assertSame(
            'EUR 5 000',
            $price->createCustomFormatter()
                ->configureWithLocale('EUR_FR')
                ->setSymbolSpaceBeforeMinus(PriceFormatter::SPACE_AFTER)
                ->setSymbolPositionBeforeMinus()
                ->format()
        );
    }
}
