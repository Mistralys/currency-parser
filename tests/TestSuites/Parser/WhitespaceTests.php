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
Without newline  €25.01
EOT;

        $expectedWhitespace = array(
            array(
                'front' => '[SPACE]',
                'end' => '[LF][LF]'
            ),
            array(
                'front' => '[SPACE]',
                'end' => '[SPACE][SPACE][LF]'
            ),
            array(
                'front' => '[SPACE][SPACE]',
                'end' => ''
            )
        );

        $prices = PriceParser::create()
            ->expectCurrency('EUR')
            ->setDebugEnabled($this->isDebugEnabled())
            ->findPrices($this->normalizeNewlines($subject));

        $this->assertCount(3, $prices);

        foreach($prices as $idx => $price)
        {
            $expected = $expectedWhitespace[$idx];

            $this->assertSame($expected['front'], ConvertHelper::hidden2visible($price->getSpaceFront()));
            $this->assertSame($expected['end'], ConvertHelper::hidden2visible($price->getSpaceEnd()));
        }
    }

    private function normalizeNewlines(string $subject) : string
    {
        return str_replace(
            "\r\n",
            "\n",
            $subject
        );
    }
}
