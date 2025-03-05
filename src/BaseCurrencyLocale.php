<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppLocalize\Localization;
use AppLocalize\Localization\Countries\CountryInterface;
use AppLocalize\Localization_Exception;
use Mistralys\CurrencyParser\Formatter\ReusableLocaleFormatter;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

abstract class BaseCurrencyLocale
{
    public const ERROR_CANNOT_CREATE_COUNTRY = 127901;

    public const SYMBOL_TYPE_SYMBOL = 'symbol';
    public const SYMBOL_TYPE_NAME = 'name';

    protected BaseCurrency $currency;
    private string $iso;
    private string $id;
    private CountryInterface $country;

    /**
     * Country ISO mappings for special cases.
     * @var array<string,string>
     */
    private array $isoAliases = array(
        'gb' => 'uk',
        'eu' => 'de'
    );

    /**
     * @param BaseCurrency $currency
     *
     * @throws CurrencyParserException
     * @see self::ERROR_CANNOT_CREATE_COUNTRY
     */
    public function __construct(BaseCurrency $currency)
    {
        $this->currency = $currency;
        $this->id = self::getIDByClass(get_class($this));

        $parts = explode('_', $this->id);
        $this->iso = strtolower(array_pop($parts));

        try
        {
            $iso = $this->getCountryISO();

            if(isset($this->isoAliases[$iso])) {
                $iso = $this->isoAliases[$iso];
            }

            $this->country = Localization::createCountries()->getByISO($iso);
        }
        catch (Localization_Exception $e)
        {
            throw new CurrencyParserException(
                'Cannot create country for currency locale.',
                sprintf(
                    'Could not get country for ISO [%s].',
                    $this->getCountryISO()
                ),
                self::ERROR_CANNOT_CREATE_COUNTRY,
                $e
            );
        }
    }

    public static function getIDByClass(string $class) : string
    {
        $parts = explode('\\', $class);
        return (string)array_pop($parts);
    }

    /**
     * @return string The locale ID, e.g. "EUR_DE"
     */
    public function getID() : string
    {
        return $this->id;
    }

    public function getCurrency() : BaseCurrency
    {
        return $this->currency;
    }

    public function getCurrencyName() : string
    {
        return $this->getCurrency()->getName();
    }

    abstract public function getDecimalSeparator() : string;
    abstract public function getThousandsSeparator() : string;
    abstract public function getArithmeticSeparator() : string;
    abstract public function getSymbolSeparator() : string;
    abstract public function getSymbolPosition() : string;
    abstract public function getPreferredSymbolType() : string;

    /**
     * @return array<string,string|NULL>
     */
    abstract public function getSymbolSpaceStyles() : array;

    /**
     * @return string The lowercase country ISO code, e.g. "de"
     */
    public function getCountryISO() : string
    {
        return $this->iso;
    }

    public function getCountry() : CountryInterface
    {
        return $this->country;
    }

    /**
     * @return ReusableLocaleFormatter
     * @throws CurrencyParserException
     */
    public function createFormatter() : ReusableLocaleFormatter
    {
        return PriceFormatter::createLocale($this);
    }

    public function createFilter() : PriceFilter
    {
        return PriceFilter::createForLocales($this);
    }

    /**
     * @param string $price
     * @return PriceMatch
     * @throws CurrencyParserException
     */
    public function parsePrice(string $price) : PriceMatch
    {
        return PriceParser::create()
            ->expectCurrency($this)
            ->findPrices($price)
            ->requireFirst();
    }

    public function tryParsePrice(string $price) : ?PriceMatch
    {
        return PriceParser::create()
            ->expectCurrency($this)
            ->findPrices($price)
            ->getFirst();
    }
}
