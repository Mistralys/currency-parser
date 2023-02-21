<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Formatting;

use Mistralys\CurrencyParser\PriceFormatter;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class FormatterTests extends CurrencyParserTestCase
{
    public function test_formatPrice() : void
    {
        $price = $this
            ->createTestParser()
            ->expectCurrency('EUR')
            ->findPrices('-1.000,00 €')
            ->getFirst();

        $this->assertNotNull($price);

        $this->assertSame(
            'EUR&#160;-1 000.00',
            PriceFormatter::createCustom('.', ' ')
                ->setSymbolPosition(PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS)
                ->setSymbolMode(PriceFormatter::SYMBOL_MODE_NAME)
                ->setSymbolSpaceStyle(PriceFormatter::SYMBOL_POSITION_BEFORE_MINUS, PriceFormatter::SPACE_AFTER)
                ->formatPrice($price)
        );
    }
}
