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
            'EUR[SPACE]-1[SPACE]000.00',
            PriceFormatter::createCustom('.', ' ')
                ->setSymbolPosition(PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS)
                ->setSymbolModeName()
                ->setSymbolSpaceStyle(PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS, PriceFormatter::SPACE_AFTER)
                ->setNonBreakingSpace('[SPACE]')
                ->format($price)
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
            '-[SPACE]1[SPACE]000,00[SPACE]€[SPACE]TTC',
            PriceFormatter::createLocale('EUR_FR')
                ->setNonBreakingSpace('[SPACE]')
                ->format($price)
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
            '-[SPACE]1[SPACE]000,00[SPACE]€[SPACE]TTC',
            PriceFormatter::createLocale('EUR_FR')
                ->setSymbolModeSymbol()
                ->setNonBreakingSpace('[SPACE]')
                ->format($price)
        );
    }

    public function test_priceFormatMethod() : void
    {
        $this->assertSame(
            '-[SPACE]1[SPACE]000,00[SPACE]EUR[SPACE]TTC',
            $this
                ->createTestParser()
                ->expectCurrency('EUR_FR')
                ->findPrices('-1,000.00 EUR TTC')
                ->requireFirst()
                ->createFormatter()
                ->setNonBreakingSpace('[SPACE]')
                ->format()
        );
    }
}
