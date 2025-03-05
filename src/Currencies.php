<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppLocalize\Localization\Countries\CountryInterface;
use AppUtils\ClassHelper;
use AppUtils\ClassHelper\BaseClassHelperException;
use AppUtils\FileHelper;
use AppUtils\FileHelper_Exception;
use Mistralys\CurrencyParser\Currencies\EUR;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;

class Currencies
{
    public const ERROR_CANNOT_GET_BY_NAME = 127701;
    public const ERROR_CANNOT_REGISTER_FORMATTER = 127702;
    public const ERROR_CANNOT_ACCESS_CURRENCIES_FOLDER = 127703;
    public const ERROR_UNKNOWN_CURRENCY_SYMBOL = 12704;
    public const ERROR_UNKNOWN_CURRENCY_LOCALE = 12706;

    /**
     * @var array<string,BaseCurrency>
     */
    private array $nameIndex = array();

    private static ?Currencies $instance = null;

    private string $referenceClass;

    /**
     * @throws CurrencyParserException
     */
    private function __construct()
    {
        $this->referenceClass = (string)str_replace(ClassHelper::getClassTypeName(EUR::class), '{ID}', EUR::class);

        try
        {
            $ids = FileHelper::createFileFinder(__DIR__ . '/Currencies')
                ->getPHPClassNames();
        }
        catch (FileHelper_Exception $e)
        {
            throw new CurrencyParserException(
                'Cannot access currencies folder.',
                '',
                self::ERROR_CANNOT_ACCESS_CURRENCIES_FOLDER
            );
        }

        foreach($ids as $id)
        {
            $this->registerCurrency($id);
        }
    }

    public static function getInstance() : Currencies
    {
        if(!isset(self::$instance)) {
            self::$instance = new Currencies();
        }

        return self::$instance;
    }

    /**
     * @param string $id
     * @return void
     * @throws CurrencyParserException
     * @see self::ERROR_CANNOT_REGISTER_FORMATTER
     */
    private function registerCurrency(string $id) : void
    {
        $class = str_replace('{ID}', $id, $this->referenceClass);

        try
        {
            $currency = ClassHelper::requireObjectInstanceOf(
                BaseCurrency::class,
                new $class($this)
            );
        } catch (BaseClassHelperException $e)
        {
            throw new CurrencyParserException(
                'Cannot register a currency formatter.',
                sprintf(
                    'The formatter class [%s] does not implement the base class [%s].',
                    $class,
                    BaseCurrency::class
                ),
                self::ERROR_CANNOT_REGISTER_FORMATTER,
                $e
            );
        }

        $this->nameIndex[$currency->getName()] = $currency;
    }

    /**
     * @return BaseCurrency[]
     */
    public function getAll() : array
    {
        return array_values($this->nameIndex);
    }

    /**
     * @param string $name Case insensitive currency name, e.g. "EUR", "eur"
     * @return BaseCurrency
     * @throws CurrencyParserException
     */
    public function getByName(string $name) : BaseCurrency
    {
        $name = strtoupper($name);
        if(isset($this->nameIndex[$name])) {
            return $this->nameIndex[$name];
        }

        throw new CurrencyParserException(
            'Cannot find the target currency.',
            sprintf(
                'The name [%s] does not match any known currencies.',
                $name
            ),
            self::ERROR_CANNOT_GET_BY_NAME
        );
    }

    public function nameExists(string $name) : bool
    {
        $name = strtoupper($name);
        return isset($this->nameIndex[$name]);
    }

    /**
     * @param string $id Locale ID, e.g. "EUR_DE". If only the currency is specified (e.g. "USD"), the default locale will be used.
     * @return BaseCurrencyLocale
     * @throws CurrencyParserException
     */
    public function getLocaleByID(string $id) : BaseCurrencyLocale
    {
        $parts = explode('_', $id);

        if($this->nameExists($parts[0])) {
            $currency = $this->getByName($parts[0]);

            if (isset($parts[1])) {
                return $currency->getLocaleByISO($parts[1]);
            }

            return $currency->getDefaultLocale();
        }

        throw new CurrencyParserException(
            'Unknown currency locale.',
            sprintf(
                'Cannot find any currency locale matching the provided ID [%s].',
                $id
            ),
            self::ERROR_UNKNOWN_CURRENCY_LOCALE
        );
    }

    /**
     * @param string $searchTerm Can be a currency symbol, name or HTML entity.
     * @param array<string,BaseCurrencyLocale> $locales List of currency locales in which to search, keyed by currency name.
     * @param array<string,string> $symbolDefaults List of symbol > currency locale ID pairs to se the default currency to use for currencies that share the same symbol.
     * @return BaseCurrencyLocale|NULL
     */
    public function autoDetect(string $searchTerm, array $locales, array $symbolDefaults) : ?BaseCurrencyLocale
    {
        $symbolMatches = array();

        foreach($locales as $locale)
        {
            $currency = $locale->getCurrency();

            if($currency->getName() === strtoupper($searchTerm))
            {
                return $locale;
            }

            if($currency->getHTMLEntity() === $searchTerm)
            {
                return $locale;
            }

            // There can be several currencies with the same symbol,
            // so we build a list of all matching currencies.
            if($currency->getSymbol() === $searchTerm)
            {
                $symbolMatches[] = $currency->getName();
            }
        }

        // Currencies matched the symbol: Now we either use the currency
        // that is set as the default for that symbol, or the first of the
        // currencies we found.
        if(!empty($symbolMatches))
        {
            // A single currency was matched, so no conflict exists,
            // we simply use this one.
            if(count($symbolMatches) === 1)
            {
                $name = array_shift($symbolMatches);
            }
            else
            {
                $name = $symbolDefaults[$searchTerm] ?? array_shift($symbolMatches);
            }

            if(isset($locales[$name])) {
                return $locales[$name];
            }
        }

        return null;
    }

    /**
     * @param string $symbol The currency symbol, e.g. "$"
     * @return bool
     */
    public function symbolExists(string $symbol) : bool
    {
        return in_array($symbol, $this->getKnownSymbols(), true);
    }

    /**
     * @param string $symbol The currency symbol, e.g. "$"
     * @return $this
     *
     * @throws CurrencyParserException
     * @see self::ERROR_UNKNOWN_CURRENCY_SYMBOL
     */
    public function requireSymbolExists(string $symbol) : self
    {
        if(!$this->symbolExists($symbol))
        {
            throw new CurrencyParserException(
                'Currency symbol does not exist.',
                sprintf(
                    'The symbol [%s] is not known. Known symbols are: [%s].',
                    $symbol,
                    implode(', ', $this->getKnownSymbols())
                ),
                self::ERROR_UNKNOWN_CURRENCY_SYMBOL
            );
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function getKnownSymbols() : array
    {
        $result = array();
        $currencies = $this->getAll();

        foreach($currencies as $currency)
        {
            $symbol = $currency->getSymbol();

            if(!in_array($symbol, $result, true))
            {
                $result[] = $symbol;
            }
        }

        return $result;
    }

    /**
     * @param string|BaseCurrencyLocale $nameOrInstance
     * @return BaseCurrencyLocale
     * @throws CurrencyParserException
     */
    public function getLocale($nameOrInstance) : BaseCurrencyLocale
    {
        if($nameOrInstance instanceof BaseCurrencyLocale) {
            return $nameOrInstance;
        }

        return $this->getLocaleByID($nameOrInstance);
    }

    /**
     * @param string|BaseCurrency $nameOrInstance
     * @return BaseCurrency
     * @throws CurrencyParserException
     */
    public function getCurrency($nameOrInstance) : BaseCurrency
    {
        if($nameOrInstance instanceof BaseCurrency) {
            return $nameOrInstance;
        }

        return $this->getByName($nameOrInstance);
    }

    /**
     * Gets the currency locale matching the specified country.
     *
     * @param CountryInterface $country
     * @return BaseCurrencyLocale
     * @throws CurrencyParserException
     */
    public function getLocaleByCountry(CountryInterface $country) : BaseCurrencyLocale
    {
        return $this
            ->getByName($country->getCurrency()->getISO())
            ->getLocaleByISO($country->getCode());
    }

    /**
     * Retrieves a list of default currency locales for
     * all available currencies.
     *
     * @return BaseCurrencyLocale[]
     */
    public function getDefaultLocales() : array
    {
        $currencies = $this->getAll();
        $result = array();

        foreach($currencies as $currency)
        {
            $result[] = $currency->getDefaultLocale();
        }

        return $result;
    }
}
