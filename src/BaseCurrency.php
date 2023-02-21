<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use AppUtils\ClassHelper;
use AppUtils\ClassHelper\BaseClassHelperException;
use AppUtils\FileHelper;
use AppUtils\FileHelper_Exception;
use Mistralys\CurrencyParser\Currencies\EUR;
use Mistralys\CurrencyParser\Currencies\EUR\EUR_DE;
use Mistralys\Rygnarok\Newsletter\CharFilter\CurrencyParserException;
use testsuites\FileHelperTests\PathInfoTest;

abstract class BaseCurrency
{
    public const ERROR_INVALID_LOCALE_INSTANCE = 127801;
    public const ERROR_CANNOT_LOAD_LOCALES = 127802;

    private static ?string $referenceClass = null;

    /**
     * @var array<string,BaseCurrencyLocale>
     */
    private array $isoIndex = array();

    /**
     * @var array<string,BaseCurrencyLocale>
     */
    private array $idIndex = array();

    /**
     * @throws CurrencyParserException
     * @see self::ERROR_INVALID_LOCALE_INSTANCE
     * @see self::ERROR_CANNOT_LOAD_LOCALES
     */
    public function __construct()
    {
        $this->initLocales();
    }

    private function getReferenceClass() : string
    {
        if(isset(self::$referenceClass)) {
            return self::$referenceClass;
        }

        $replaces = array(
            BaseCurrencyLocale::getIDByClass(EUR_DE::class) => '{FORMAT_ID}',
            ClassHelper::getClassTypeName(EUR::class) => '{CURRENCY_ID}'
        );

        self::$referenceClass = (string)str_replace(array_keys($replaces), array_values($replaces), EUR_DE::class);

        return self::$referenceClass;
    }

    abstract public function getName(): string;
    abstract public function getSymbol(): string;
    abstract public function getDefaultLocaleISO() : string;

    abstract public function getEntityNumber(): int;

    public function getHTMLEntity() : string
    {
        return sprintf(
            '&#%s;',
            $this->getEntityNumber()
        );
    }

    /**
     * @return BaseCurrencyLocale[]
     */
    public function getLocales() : array
    {
        return array_values($this->idIndex);
    }

    /**
     * @return void
     *
     * @throws CurrencyParserException
     * @see self::ERROR_INVALID_LOCALE_INSTANCE
     * @see self::ERROR_CANNOT_LOAD_LOCALES
     */
    private function initLocales() : void
    {
        try
        {
            $ids = FileHelper::createFileFinder(__DIR__ . '/Currencies/' . $this->getName())
                ->getPHPClassNames();
        }
        catch (FileHelper_Exception $e)
        {
            throw new CurrencyParserException(
                'Could not access the currency locales folder.',
                sprintf(
                    'Failed to load the locale IDs for currency [%s].',
                    $this->getName()
                ),
                self::ERROR_CANNOT_LOAD_LOCALES,
                $e
            );
        }

        foreach($ids as $id)
        {
            $this->registerLocale($id);
        }
    }

    /**
     * @param string $iso Country ISO code, case insensitive. Examples: "de", "FR".
     * @return BaseCurrencyLocale|null
     */
    public function getLocaleByISO(string $iso) : ?BaseCurrencyLocale
    {
        return $this->isoIndex[strtolower($iso)] ?? null;
    }

    public function getDefaultLocale() : BaseCurrencyLocale
    {
        return $this->getLocaleByISO($this->getDefaultLocaleISO());
    }

    /**
     * @param string $id The formatter ID, e.g. "EUR_DE". Case sensitive.
     * @return BaseCurrencyLocale|null
     */
    public function getLocaleByID(string $id) : ?BaseCurrencyLocale
    {
        return $this->idIndex[$id] ?? null;
    }

    /**
     * @param string $id
     * @return void
     * @throws CurrencyParserException {@see BaseCurrency::ERROR_INVALID_LOCALE_INSTANCE}
     */
    private function registerLocale(string $id) : void
    {
        $replaces = array(
            '{FORMAT_ID}' => $id,
            '{CURRENCY_ID}' => $this->getName()
        );

        $class = str_replace(array_keys($replaces), array_values($replaces), $this->getReferenceClass());

        try
        {
            $locale = ClassHelper::requireObjectInstanceOf(
                BaseCurrencyLocale::class,
                new $class($this)
            );
        }
        catch (BaseClassHelperException $e)
        {
            throw new CurrencyParserException(
                'Could not register currency locale.',
                sprintf(
                    'The class [%s] is not an instance of [%s].',
                    $class,
                    BaseCurrencyLocale::class
                ),
                self::ERROR_INVALID_LOCALE_INSTANCE,
                $e
            );
        }

        $this->isoIndex[$locale->getCountryISO()] = $locale;
        $this->idIndex[$locale->getID()] = $locale;
    }
}

