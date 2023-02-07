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
     * @param string $searchTerm
     * @param BaseCurrency[] $currencies
     * @return BaseCurrency|NULL
     */
    public function autoDetect(string $searchTerm, array $currencies) : ?BaseCurrency
    {
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

            if($currency->getSymbol() === $searchTerm)
            {
                return $currency;
            }
        }

        return null;
    }
}
