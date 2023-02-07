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

abstract class BaseCurrency
{
    public const ERROR_INVALID_FORMAT_INSTANCE = 127801;
    public const ERROR_CANNOT_LOAD_FORMATTERS = 127802;

    private static ?string $referenceClass = null;

    /**
     * @var array<string,BaseCurrencyFormat>
     */
    private array $isoIndex = array();

    /**
     * @var array<string,BaseCurrencyFormat>
     */
    private array $idIndex = array();

    /**
     * @throws CurrencyParserException
     * @see self::ERROR_INVALID_FORMAT_INSTANCE
     * @see self::ERROR_CANNOT_LOAD_FORMATTERS
     */
    public function __construct()
    {
        $this->initFormatters();
    }

    private function getReferenceClass() : string
    {
        if(isset(self::$referenceClass)) {
            return self::$referenceClass;
        }

        $replaces = array(
            BaseCurrencyFormat::getIDByClass(EUR_DE::class) => '{FORMAT_ID}',
            ClassHelper::getClassTypeName(EUR::class) => '{CURRENCY_ID}'
        );

        self::$referenceClass = (string)str_replace(array_keys($replaces), array_values($replaces), EUR_DE::class);

        return self::$referenceClass;
    }

    abstract public function getName(): string;
    abstract public function getSymbol(): string;

    abstract public function getEntityNumber(): int;

    public function getHTMLEntity() : string
    {
        return sprintf(
            '&#%s;',
            $this->getEntityNumber()
        );
    }

    /**
     * @return BaseCurrencyFormat[]
     */
    public function getFormatters() : array
    {
        return array_values($this->idIndex);
    }

    /**
     * @return void
     *
     * @throws CurrencyParserException
     * @see self::ERROR_INVALID_FORMAT_INSTANCE
     * @see self::ERROR_CANNOT_LOAD_FORMATTERS
     */
    private function initFormatters() : void
    {
        try
        {
            $ids = FileHelper::createFileFinder(__DIR__ . '/Currencies/' . $this->getName())
                ->getPHPClassNames();
        }
        catch (FileHelper_Exception $e)
        {
            throw new CurrencyParserException(
                'Could not access the currency formats folder.',
                sprintf(
                    'Failed to load the formatter IDs for currency [%s].',
                    $this->getName()
                ),
                self::ERROR_CANNOT_LOAD_FORMATTERS,
                $e
            );
        }

        foreach($ids as $id)
        {
            $this->registerFormatter($id);
        }
    }

    /**
     * @param string $iso Country ISO code, case insensitive. Examples: "de", "FR".
     * @return BaseCurrencyFormat|null
     */
    public function getFormatterByISO(string $iso) : ?BaseCurrencyFormat
    {
        return $this->isoIndex[strtolower($iso)] ?? null;
    }

    /**
     * @param string $id The formatter ID, e.g. "EUR_DE". Case sensitive.
     * @return BaseCurrencyFormat|null
     */
    public function getFormatterByID(string $id) : ?BaseCurrencyFormat
    {
        return $this->idIndex[$id] ?? null;
    }

    /**
     * @param string $id
     * @return void
     * @throws CurrencyParserException {@see BaseCurrency::ERROR_INVALID_FORMAT_INSTANCE}
     */
    private function registerFormatter(string $id) : void
    {
        $replaces = array(
            '{FORMAT_ID}' => $id,
            '{CURRENCY_ID}' => $this->getName()
        );

        $class = str_replace(array_keys($replaces), array_values($replaces), $this->getReferenceClass());

        try
        {
            $format = ClassHelper::requireObjectInstanceOf(
                BaseCurrencyFormat::class,
                new $class($this)
            );
        }
        catch (BaseClassHelperException $e)
        {
            throw new CurrencyParserException(
                'Could not register currency format.',
                sprintf(
                    'The class [%s] is not an instance of [%s].',
                    $class,
                    BaseCurrencyFormat::class
                ),
                self::ERROR_INVALID_FORMAT_INSTANCE,
                $e
            );
        }

        $this->isoIndex[$format->getCountryISO()] = $format;
        $this->idIndex[$format->getID()] = $format;
    }
}

