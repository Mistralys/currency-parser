<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * Container for a price parser result: Holds the original
 * parsed subject string as well as all price matches that
 * were found, if any.
 *
 * Offers a number of helper methods to access the stored
 * information. Can be used like an array, including in
 * foreach statements.
 *
 * @package CurrencyParser
 * @implements ArrayAccess<int,PriceMatch>
 */
class PriceMatches implements ArrayAccess, Countable, Iterator
{
    private string $subject;

    /**
     * @var PriceMatch[]
     */
    private array $matches;

    /**
     * @param string $subject
     * @param PriceMatch[] $matches
     */
    public function __construct(string $subject, array $matches)
    {
        $this->subject = $subject;
        $this->matches = $matches;
    }

    /**
     * @return PriceMatch[]
     */
    public function getAll(): array
    {
        return $this->matches;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function hasMatches() : bool
    {
        return !empty($this->matches);
    }

    /**
     * @return PriceMatch|null
     */
    public function getFirst() : ?PriceMatch
    {
        if(!empty($this->matches)) {
            return $this->matches[0];
        }

        return null;
    }

    public function createFormatter(string $decimalSeparator, string $thousandSeparator, string $arithmeticSeparator) : PriceFormatter
    {
        return new PriceFormatter($decimalSeparator, $thousandSeparator, $arithmeticSeparator);
    }

    // region: Array interfaces

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->matches[$offset]);
    }

    /**
     * @param int $offset
     * @return PriceMatch|null
     */
    public function offsetGet($offset) : ?PriceMatch
    {
        return $this->matches[$offset] ?? null;
    }

    /**
     * @param int $offset
     * @param PriceMatch $value
     * @return void
     */
    public function offsetSet($offset, $value) : void
    {
        $this->matches[$offset] = $value;
    }

    /**
     * @param int $offset
     * @return void
     */
    public function offsetUnset($offset) : void
    {
        unset($this->matches[$offset]);
    }

    public function count() : int
    {
        return count($this->matches);
    }

    private int $iteratorPos = 0;

    public function current() : PriceMatch
    {
        return $this->matches[$this->iteratorPos];
    }

    public function next() : void
    {
        $this->iteratorPos++;
    }

    public function key() : int
    {
        return $this->iteratorPos;
    }

    public function valid() : bool
    {
        return $this->iteratorPos < count($this->matches);
    }

    public function rewind() : void
    {
        $this->iteratorPos = 0;
    }

    // endregion
}
