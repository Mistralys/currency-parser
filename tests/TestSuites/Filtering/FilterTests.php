<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Formatting;

use Mistralys\CurrencyParser\PriceFilter;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;

final class FilterTests extends CurrencyParserTestCase
{
    /**
     * When using the price filter with default settings, it
     * uses the currency's default locale for the formatting.
     * In this case, it's the EUR_EU locale.
     */
    public function test_filterStringDefaultFormatters() : void
    {
        $subject = <<<'EOT'
Starting price: €35
Black Friday rebate: €9.99
Your price: EUR2500.01
EOT;

        $expected = <<<'EOT'
Starting price: 35&#160;€
Black Friday rebate: 9,99&#160;€
Your price: 2.500,01&#160;EUR
EOT;

        $this->assertSame(
            $expected,
            PriceFilter::createForLocales('EUR')
                ->setDebugEnabled($this->isDebugEnabled())
                ->filterString($subject)
        );
    }

    /**
     * Using a specific locale for a currency, like the
     * French notation that is based on the European style,
     * but uses spaces as thousands separators.
     */
    public function test_filterStringLocaleFormatter() : void
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
            PriceFilter::createForLocales('EUR_FR')
                ->setDebugEnabled($this->isDebugEnabled())
                ->filterString($subject)
        );
    }

    public function test_filterStringCountryFormatter() : void
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
            PriceFilter::createForCountries('FR')
                ->setDebugEnabled($this->isDebugEnabled())
                ->filterString($subject)
        );
    }
}
