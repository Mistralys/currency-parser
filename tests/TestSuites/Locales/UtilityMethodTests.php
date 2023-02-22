<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Locales;

use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;
use function Mistralys\CurrencyParser\currencyLocale;
use function Mistralys\CurrencyParser\findPrices;
use function Mistralys\CurrencyParser\tryParsePrice;

final class UtilityMethodTests extends CurrencyParserTestCase
{
    public function test_filterString() : void
    {
        $subject = <<<'EOT'
Starting price: €35
Black Friday rebate: €9.99
Your price: EUR2500.01
EOT;

        $expected = <<<'EOT'
Starting price: 35 €
Black Friday rebate: 9,99 €
Your price: 2 500,01 EUR
EOT;

        $this->assertSame(
            $expected,
            currencyLocale('EUR_FR')
                ->createFilter()
                ->filterString($subject)
        );
    }

    public function test_formatPrice() : void
    {
        $this->assertSame(
            '500[SPACE]EUR',
            currencyLocale('EUR_FR')
                ->parsePrice('500 EUR')
                ->createFormatter()
                ->setNonBreakingSpace('[SPACE]')
                ->format()
        );
    }

    public function test_tryParsePrice() : void
    {
        $this->assertNull(tryParsePrice('Nope, not a price'));
        $this->assertNotNull(tryParsePrice('$50'));
    }

    public function test_tryParsePriceWrongCurrency() : void
    {
        $this->assertNull(tryParsePrice('$50', 'EUR'));
        $this->assertNotNull(tryParsePrice('$50', 'USD'));
    }

    public function test_findPrices() : void
    {
        $prices = findPrices('$50 $60 $70', 'USD');

        $this->assertCount(3, $prices);
    }
}
