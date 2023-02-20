<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Parser;

use Mistralys\CurrencyParser\PriceParser;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class PriceMatchesTests extends CurrencyParserTestCase
{
    public function test_arrayAccess() : void
    {
        $subject = <<<EOT
$35
$9.99
$25.01
EOT;

        $prices = PriceParser::create()
            ->expectCurrency('USD')
            ->findPrices($subject);

        $this->assertCount(3, $prices);
        $this->assertArrayHasKey(0, $prices);
    }

    public function test_iterable() : void
    {
        $subject = <<<EOT
$35
$9.99
$25.01
EOT;

        $expected = array(
            35.00,
            9.99,
            25.01
        );

        $prices = PriceParser::create()
            ->expectCurrency('USD')
            ->findPrices($subject);

        foreach($prices as $idx => $price)
        {
            $this->assertSame($expected[$idx], $price->getAsFloat());
        }
    }
}
