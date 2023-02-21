<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Formatting;

use Mistralys\CurrencyParser\PriceFormatter;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class FormatterTests extends CurrencyParserTestCase
{
    public function test_customFormat() : void
    {
        $price = $this
            ->createTestParser()
            ->expectCurrency('EUR')
            ->findPrices('-1.000,00 €')
            ->getFirst();

        $this->assertNotNull($price);

        $this->assertSame(
            'EUR&#160;-1&#160;000.00',
            PriceFormatter::createCustom('.', ' ')
                ->setSymbolPosition(PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS)
                ->setSymbolModeName()
                ->setSymbolSpaceStyle(PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS, PriceFormatter::SPACE_AFTER)
                ->formatPrice($price)
        );
    }

    public function test_localeFormat() : void
    {
        $price = $this
            ->createTestParser()
            ->expectCurrency('EUR')
            ->findPrices('-1,000.00 € TTC')
            ->getFirst();

        $this->assertNotNull($price);

        $this->assertSame(
            '-&#160;1&#160;000,00&#160;€&#160;TTC',
            PriceFormatter::createLocale('EUR_FR')
                ->formatPrice($price)
        );
    }

    /**
     * The preferred style for european currencies is to use the
     * symbol instead of the currency name. When using the preferred
     * style, it must replace names with symbols.
     */
    public function test_localeFormatPreferredSymbol() : void
    {
        $price = $this
            ->createTestParser()
            ->expectCurrency('EUR')
            ->findPrices('-1,000.00 EUR TTC')
            ->getFirst();

        $this->assertNotNull($price);

        $this->assertSame(
            '-&#160;1&#160;000,00&#160;€&#160;TTC',
            PriceFormatter::createLocale('EUR_FR')
                ->setSymbolModeSymbol()
                ->formatPrice($price)
        );
    }

    public function test_priceFormatMethod() : void
    {
        $this->assertSame(
            '-&#160;1&#160;000,00&#160;EUR&#160;TTC',
            $this
                ->createTestParser()
                ->expectCurrency('EUR')
                ->findPrices('-1,000.00 EUR TTC')
                ->requireFirst()
                ->formatForLocale('EUR_FR')
        );
    }
}
