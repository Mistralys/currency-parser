<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Formatting;

use Mailcode\Mailcode;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

class MailcodePlaceholderTests extends CurrencyParserTestCase
{
    private string $testText = <<<'EOT'
A regular price: 45 EUR
And a variable price: {showvar: $FOO.PRICE} EUR
EOT;

    /**
     * Mailcode placeholders are numbers. This means that when using
     * currencies from variables, since the safeguarded command is a
     * number, it will recognize the placeholder and currency sign as
     * a price to format.
     */
    public function test_placeholder() : void
    {
        $safeguard = Mailcode::create()->createSafeguard($this->testText);
        $text = $safeguard->makeSafe();

        echo $text;
        $prices = $this
            ->createTestParser()
            ->expectCurrency('EUR')
            ->expectMailcode()
            ->findPrices($text)
            ->getAll();

        $this->assertCount(1, $prices);

    }
}
