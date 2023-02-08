<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppUtils\ClassHelper;
use AppUtils\ClassHelper\BaseClassHelperException;
use AppUtils\ClassHelper\ClassNotExistsException;
use AppUtils\ClassHelper\ClassNotImplementsException;
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
     * @return BaseCurrency|null
     */
    public function getByName(string $name) : ?BaseCurrency
    {
        return $this->nameIndex[strtoupper($name)] ?? null;
    }

    /**
     * Retrieves a currency by name, symbol or HTML entity.
     * Throws an exception if not found.
     *
     * @param string $name
     * @return BaseCurrency
     *
     * @throws CurrencyParserException {@see Currencies::ERROR_CANNOT_GET_BY_NAME}
     */
    public function requireByName(string $name) : BaseCurrency
    {
        $currency = $this->getByName($name);

        if($currency !== null) {
            return $currency;
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

    public function getFormatterByID(string $id) : ?BaseCurrencyFormat
    {
        $parts = explode('_', $id);

        if(count($parts) !== 2) {
            return null;
        }

        $currency = $this->getByName($parts[0]);

        if($currency !== null) {
            return $currency->getFormatterByISO($parts[1]);
        }

        return null;
    }

    /**
     * @param string $searchTerm Can be a currency symbol, name or HTML entity.
     * @param BaseCurrency[] $currencies List of currencies in which to search.
     * @param array<string,string> $symbolDefaults List of symbol > currency name pairs to se the default currency to use for currencies that share the same symbol.
     * @return BaseCurrency|NULL
     */
    public function autoDetect(string $searchTerm, array $currencies, array $symbolDefaults) : ?BaseCurrency
    {
        $symbolMatches = array();

        foreach($currencies as $currency)
        {
            if($currency->getName() === strtoupper($searchTerm))
            {
                return $currency;
            }

            if($currency->getHTMLEntity() === $searchTerm)
            {
                return $currency;
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
            return $this->getByName($symbolDefaults[$searchTerm] ?? array_shift($symbolMatches));
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
}
