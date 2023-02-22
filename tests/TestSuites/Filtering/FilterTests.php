<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParserTests\TestSuites\Formatting;

use AppUtils\FileHelper\FileInfo;
use Mistralys\CurrencyParser\PriceFilter;
use Mistralys\CurrencyParser\PriceParser;
use Mistralys\CurrencyParserTests\TestClasses\CurrencyParserTestCase;
use function Mistralys\CurrencyParser\currencyLocale;

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
Starting price: 35[SPACE]€
Black Friday rebate: 9,99[SPACE]€
Your price: 2.500,01[SPACE]EUR
EOT;

        $this->assertSame(
            $expected,
            PriceFilter::createForLocales('EUR')
                ->setDebugEnabled($this->isDebugEnabled())
                ->setNonBreakingSpace('[SPACE]')
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
Starting price: 35[SPACE]€
Black Friday rebate: 9,99[SPACE]€
Your price: 2[SPACE]500,01[SPACE]EUR
EOT;

        $this->assertSame(
            $expected,
            PriceFilter::createForLocales('EUR_FR')
                ->setDebugEnabled($this->isDebugEnabled())
                ->setNonBreakingSpace('[SPACE]')
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
Starting price: 35[SPACE]€
Black Friday rebate: 9,99[SPACE]€
Your price: 2[SPACE]500,01[SPACE]EUR
EOT;

        $this->assertSame(
            $expected,
            PriceFilter::createForCountries('FR')
                ->setDebugEnabled($this->isDebugEnabled())
                ->setNonBreakingSpace('[SPACE]')
                ->filterString($subject)
        );
    }

    public function test_filterNotHasFormatter() : void
    {
        $filter = PriceFilter::create();

        $this->assertFalse($filter->hasFormatter('USD'));
    }

    public function test_filterHasFormatterWhenSet() : void
    {
        $filter = PriceFilter::create();

        $filter->setFormatterByLocale('USD');

        $this->assertTrue($filter->hasFormatter('USD'));
    }

    public function test_localeFilterHasFormatter() : void
    {
        $filter = currencyLocale('USD')->createFilter();

        $this->assertTrue($filter->hasFormatter('USD'));
    }

    public function test_preserveWhitespace() : void
    {
        $subject = PHP_EOL.'    -    50    EUR    '.PHP_EOL;
        $expected = PHP_EOL.'    -50 EUR    '.PHP_EOL;

        $this->assertSame(
            $expected,
            currencyLocale('EUR')
                ->createFilter()
                ->filterString($subject)
        );
    }

    public function test_filterFile() : void
    {
        $this->assertSame(
            PriceFilter::createForLocales('EUR_FR')
                ->setNonBreakingSpace('[SPACE]')
                ->filterFile(FileInfo::factory(__DIR__.'/../../files/test-text.txt')),
            file_get_contents(__DIR__.'/../../files/test-text-expected.txt')
        );
    }
}
