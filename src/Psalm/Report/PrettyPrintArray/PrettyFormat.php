<?php

namespace Psalm\Report\PrettyPrintArray;

use function str_repeat;
use function str_split;

use const PHP_EOL;

final class PrettyFormat
{
    private const CHAR_SPACE = ' ';

    public function format(string $inputPayload): string
    {
        $buffer = '';
        $previousChar = '';
        $prettyCursorBracket = new PrettyCursorBracket();
        $payload = $inputPayload;

        foreach (str_split($payload) as $char) {
            $prettyCursorBracket->accept($char);

            if (self::CHAR_SPACE === $char) {
                continue;
            }

            $charAfter = '';
            $charBefore = '';

            if (':' === $char) {
                $charAfter = self::CHAR_SPACE;
            }

            if (',' === $char) {
                $charAfter = PHP_EOL;
            } elseif (',' === $previousChar) {
                $charBefore = $this->addIdentChar($prettyCursorBracket);
            }

            if ('{' === $previousChar) {
                $charBefore =  $this->addIdentChar($prettyCursorBracket);
            } elseif ('{' === $char) {
                $charBefore = self::CHAR_SPACE;
                $charAfter = PHP_EOL;
            }

            if ('}' === $char) {
                $charBefore = PHP_EOL .$this->addIdentChar($prettyCursorBracket);
            }

            $buffer .= $charBefore
                    . $char
                    . $charAfter;

            $previousChar = $char;
            if ($prettyCursorBracket->closed()) {
                break;
            }
        }
        return $buffer;
    }

    private function addIdentChar(PrettyCursorBracket $prettyCursorBracket): string
    {
        return str_repeat(self::CHAR_SPACE, $prettyCursorBracket->getNumberBrackets());
    }
}
