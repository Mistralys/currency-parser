<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Locales;

use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;
use function Mistralys\CurrencyParser\currencyLocale;

final class UtilityMethodTests extends CurrencyParserTestCase
{
    public function test_formatPrice() : void
    {
        $this->assertSame(
            '500&#160;EUR',
            currencyLocale('EUR_FR')->formatPriceString('500 EUR')
        );
    }

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

        $this->assertSame(
            $expected,
            currencyLocale('EUR_FR')->filterString($subject)
        );
    }
}
