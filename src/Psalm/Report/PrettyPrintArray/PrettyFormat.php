<?php

namespace Psalm\Report\PrettyPrintArray;

use function count;
use function str_repeat;

use const PHP_EOL;

final class PrettyFormat
{
    public function format(string $inputPayload): string
    {
        $buffer = $previousToken = '';
        $prettyCursorBracket = new PrettyCursorBracket();
        $prettyMatchTokens = new PrettyMatchTokens();

        $payload = PrettyHelper::normalizeBracket($inputPayload);
        $payload = PrettyHelper::normalizeTokens($payload);

        $prettyMatchTokens->tokenize($payload);
        $tokens = $prettyMatchTokens->getMatchedTokens();

        for ($i=0; $i<count($tokens); $i++) {
            $token = $tokens[$i];
            $nextToken = $tokens[$i+1];

            $prettyCursorBracket->accept($token);

            if (PrettyMatchTokens::T_SPACE === $token) {
                continue;
            }

            if ($token === PrettyMatchTokens::T_PSALM_KEY &&
                $nextToken === PrettyMatchTokens::T_COMMA) {
                $i++;
                continue;
            }

            $tokenAfter = $tokenBefore = '';
            $numBrackets = $prettyCursorBracket->getNumberBrackets();

            [$tokenAfter, $tokenBefore] = $this->processByCurrentToken(
                $token,
                $tokenBefore,
                $tokenAfter,
                $numBrackets
            );

            $tokenBefore = $this->processByPreviousToken(
                $previousToken,
                $numBrackets,
                $tokenBefore
            );

            $buffer .= $tokenBefore . $token . $tokenAfter;

            $previousToken = $token;
            if ($prettyCursorBracket->closed()) {
                break;
            }
        }
        return $buffer;
    }

    private function indentOf(int $numBrackets): string
    {
        return str_repeat(PrettyMatchTokens::T_SPACE, $numBrackets);
    }

    private function processByPreviousToken(string $previousToken, int $numBrackets, string $tokenBefore): string
    {
        if ($previousToken === PrettyMatchTokens::T_COMMA) {
            return $this->indentOf($numBrackets);
        }

        if ($previousToken === PrettyMatchTokens::T_CURLY_BRACKET_OPEN) {
            return $this->indentOf($numBrackets);
        }

        return $tokenBefore;
    }

    /**
     * @return array{string,string}
     */
    private function processByCurrentToken(string $token, string $tokenBefore, string $tokenAfter, int $numBrackets): array
    {
        if ($token === PrettyMatchTokens::T_COLON) {
            return [PrettyMatchTokens::T_SPACE,$tokenAfter];
        }

        if ($token === PrettyMatchTokens::T_COMMA) {
            return [PHP_EOL,$tokenAfter];
        }

        if ($token === PrettyMatchTokens::T_CURLY_BRACKET_OPEN) {
            return [PHP_EOL,PrettyMatchTokens::T_SPACE];
        }

        if ($token === PrettyMatchTokens::T_CURLY_BRACKET_CLOSE) {
            return [$tokenAfter,PHP_EOL . $this->indentOf($numBrackets)];
        }

        return [$tokenAfter, $tokenBefore];
    }
}
