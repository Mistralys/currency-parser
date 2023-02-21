<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Formatting;

use Mistralys\CurrencyParser\PriceFilter;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class FilterTests extends CurrencyParserTestCase
{
    public function test_filterStringDefaultFormatters() : void
    {
        $subject = <<<'EOT'
Starting price: €35
Black Friday rebate: €9.99
Your price: EUR25.01
EOT;

        $expected = <<<'EOT'
Starting price: 35&#160;€
Black Friday rebate: 9,99&#160;€
Your price: 25,01&#160;EUR
EOT;

        $this->assertSame(
            $expected,
            PriceFilter::createForCurrencies('EUR')
                ->filterString($subject)
        );
    }
}
