<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppLocalize\Localization;
use AppLocalize\Localization_Country;
use AppLocalize\Localization_Exception;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

abstract class BaseCurrencyLocale
{
    public const ERROR_CANNOT_CREATE_COUNTRY = 127901;

    public const SYMBOL_TYPE_SYMBOL = 'symbol';
    public const SYMBOL_TYPE_NAME = 'name';

    private BaseCurrency $currency;
    private string $iso;
    private string $id;
    private Localization_Country $country;

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

            $this->country = Localization::createCountry($iso);
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

    abstract public function getDecimalSeparator() : string;
    abstract public function getThousandsSeparator() : string;
    abstract public function getArithmeticSeparator() : string;
    abstract public function getSymbolSeparator() : string;
    abstract public function getSymbolPosition() : string;
    abstract public function getPreferredSymbolType() : string;
    abstract public function getSymbolSpaceStyles() : array;

    /**
     * @return string The lowercase country ISO code, e.g. "de"
     */
    public function getCountryISO() : string
    {
        return $this->iso;
    }

    public function getCountry() : Localization_Country
    {
        return $this->country;
    }
}
