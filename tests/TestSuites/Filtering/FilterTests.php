<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Formatting;

use Mistralys\CurrencyParser\PriceFilter;
use Mistralys\CurrencyParser\PriceFormatter;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class FilterTests extends CurrencyParserTestCase
{
    public function test_filterString() : void
    {
        $subject = <<<'EOT'
Starting price: €35
Black Friday rebate: €9.99
Your price: €25.01
EOT;

        $expected = <<<'EOT'
Starting price: 35 EUR
Black Friday rebate: 9.99 EUR
Your price: 25.01 EUR
EOT;

        $formatter = PriceFormatter::create('.', ' ')
            ->setSymbolPosition(PriceFormatter::SYMBOL_POSITION_END)
            ->setSymbolMode(PriceFormatter::SYMBOL_MODE_NAME)
            ->setSymbolSpaceEnabled();

        $filter = PriceFilter::createForCurrency('EUR', $formatter);

        $this->assertSame($expected, $filter->filterString($subject));
    }
}
