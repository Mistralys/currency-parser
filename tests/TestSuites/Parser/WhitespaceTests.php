<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Parser;

use AppUtils\ConvertHelper;
use Mistralys\CurrencyParser\PriceParser;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class WhitespaceTests extends CurrencyParserTestCase
{
    public function test_isWhitespacePreserved() : void
    {
        $subject = <<<'EOT'
With two newlines €35

With three spaces at the end €9.99  
Without newline €25.01
EOT;

        $prices = PriceParser::create()
            ->expectCurrency('EUR')
            ->setDebugEnabled(true)
            ->findPrices($subject);

        $this->assertCount(3, $prices);

        foreach($prices as $price)
        {
            print_r(array(
                'matched' => ConvertHelper::hidden2visible($price->getMatchedString()),
                'front' => ConvertHelper::hidden2visible($price->getSpaceFront()),
                'end' => ConvertHelper::hidden2visible($price->getSpaceEnd())
            ));
        }
    }
}
