<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Interfaces;

trait NonBreakingSpaceTrait
{
    protected ?string $nonBreakingSpace = null;

    /**
     * @return string
     */
    public function getNonBreakingSpace(): string
    {
        return $this->nonBreakingSpace ?? NonBreakingSpaceInterface::NON_BREAKING_SPACE_TEXT;
    }

    /**
     * @param string $nonBreakingSpace
     * @return $this
     */
    public function setNonBreakingSpace(string $nonBreakingSpace): self
    {
        $this->nonBreakingSpace = $nonBreakingSpace;
        return $this;
    }

    /**
     * Sets to use a plain text non-breaking space character
     * to ensure price components like the symbol do not get
     * wrapped to the next line.
     *
     * @return $this
     */
    public function setNonBreakingSpaceText() : self
    {
        return $this->setNonBreakingSpace(NonBreakingSpaceInterface::NON_BREAKING_SPACE_TEXT);
    }

    /**
     * Sets to use a non-breaking space HTML entity to ensure
     * price components like the symbol do not get wrapped to
     * the next line.
     *
     * @return $this
     */
    public function setNonBreakingSpaceHTML() : self
    {
        return $this->setNonBreakingSpace(NonBreakingSpaceInterface::NON_BREAKING_SPACE_HTML);
    }
}
