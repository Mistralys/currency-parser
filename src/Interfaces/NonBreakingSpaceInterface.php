<?php

declare(strict_types=1);

namespace Mistralys\CurrencyParser\Interfaces;

interface NonBreakingSpaceInterface
{
    public const NON_BREAKING_SPACE_HTML = '&#160;';
    public const NON_BREAKING_SPACE_TEXT = ' ';

    public function getNonBreakingSpace(): string;

    /**
     * Sets the character to use as spaces.
     *
     * @param string $nonBreakingSpace
     * @return $this
     */
    public function setNonBreakingSpace(string $nonBreakingSpace): self;

    /**
     * Sets to use the text-based non-breaking space character (default).
     * @return $this
     */
    public function setNonBreakingSpaceText() : self;

    /**
     * Sets to use the HTML-based non-breaking space character.
     * @return $this
     */
    public function setNonBreakingSpaceHTML() : self;
}
