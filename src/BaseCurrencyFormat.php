<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppLocalize\Localization;
use AppLocalize\Localization_Country;
use AppLocalize\Localization_Exception;
use AppUtils\ClassHelper;
use Mistralys\CurrencyParser\Currencies\EUR\EUR_DE;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

abstract class BaseCurrencyFormat
{
    public const ERROR_CANNOT_CREATE_COUNTRY = 127901;

    public const SYMBOL_TYPE_SYMBOL = 'symbol';
    public const SYMBOL_TYPE_NAME = 'name';

    private BaseCurrency $currency;
    private string $iso;
    private string $id;
    private Localization_Country $country;

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

            if($iso === 'gb') {
                $iso = 'uk';
            }

            $this->country = Localization::createCountry($iso);
        }
        catch (Localization_Exception $e)
        {
            throw new CurrencyParserException(
                'Cannot create country for currency formatter.',
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
     * @return string The format ID, e.g. "EUR_DE"
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
