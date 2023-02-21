<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Locales;

use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;
use function Mistralys\CurrencyParser\currencyLocale;
use function Mistralys\CurrencyParser\filterString;
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
Starting price: 35&#160;€
Black Friday rebate: 9,99&#160;€
Your price: 2&#160;500,01&#160;EUR
EOT;

        $this->assertSame($expected, filterString($subject, 'EUR_FR'));
    }

    public function test_formatPrice() : void
    {
        $this->assertSame(
            '500&#160;EUR',
            currencyLocale('EUR_FR')->formatPriceString('500 EUR')
        );
    }

    public function test_tryParsePrice() : void
    {
        $this->assertNull(tryParsePrice('Nope, not a price'));
        $this->assertNotNull(tryParsePrice('$50'));
    }

    public function test_findPrices() : void
    {
        $prices = findPrices('$50 $60 $70', 'USD');

        $this->assertCount(3, $prices);
    }
}
