<?php

namespace Psalm\Report\PrettyPrintArray;

final class PrettyCursorBracket
{
    private const BRACKET_OPEN = '{';
    private const BRACKET_CLOSE = '}';

    private int $nBrackets;
    private string $char;
    private bool $openedBracket = false;

    public function __construct()
    {
        $this->nBrackets = 0;
        $this->char = '';
    }

    public function accept(string $char): void
    {
        $this->char = $char;
        if (self::BRACKET_OPEN === $this->char) {
            $this->openedBracket = true;
            $this->nBrackets++;
        }

        if (self::BRACKET_CLOSE === $char) {
            $this->nBrackets--;
        }
    }

    public function closed(): bool
    {
        if ($this->openedBracket === true && self::BRACKET_CLOSE === $this->char) {
            $this->nBrackets--;
            if ($this->nBrackets <= 0) {
                return true;
            }
        }
        return false;
    }

    public function getNumberBrackets(): int
    {
        return $this->nBrackets;
    }
}
