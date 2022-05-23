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

        $payload = PrettyGeneric::normalizeBracket($payload);
        $payload = PrettyGeneric::normalizeTokens($payload);

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

            $numChars = $prettyCursorBracket->getNumberBrackets();
            if (',' === $char) {
                $charAfter = PHP_EOL;
            } elseif (',' === $previousChar) {
                $charBefore = $this->addIdentChar($numChars);
            }

            if ('{' === $previousChar) {
                $charBefore =  $this->addIdentChar($numChars);
            } elseif ('{' === $char) {
                $charBefore = self::CHAR_SPACE;
                $charAfter = PHP_EOL;
            }

            if ('}' === $char) {
                $charBefore = PHP_EOL .$this->addIdentChar($numChars);
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

    private function addIdentChar(int $numChars): string
    {
        return str_repeat(self::CHAR_SPACE, $numChars);
    }
}
