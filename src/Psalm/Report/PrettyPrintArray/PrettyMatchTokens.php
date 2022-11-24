<?php

namespace Psalm\Report\PrettyPrintArray;

use function in_array;
use function str_split;
use function strlen;
use function substr;

use const PHP_EOL;

final class PrettyMatchTokens
{
    public const T_SPACE = ' ';
    public const T_COMMA = ',';
    public const T_PSALM_KEY = 'psalm-key';
    public const T_CURLY_BRACKET_OPEN = '{';
    public const T_CURLY_BRACKET_CLOSE = '}';
    public const T_COLON = ':';
    public const T_ARRAY = 'array';

    /**
     * @var string[]
     */
    private $tokens;

    /**
     * @var string[]
     */
    private $machedTokens;

    /**
     * @param string[] $tokens
     */
    public function __construct(array $tokens = [
            self::T_CURLY_BRACKET_OPEN,
            self::T_CURLY_BRACKET_CLOSE,
            self::T_COLON,
            self::T_COMMA,
            self::T_SPACE,
            self::T_PSALM_KEY,
            self::T_ARRAY
    ])
    {
        $this->tokens = $tokens;
        $this->machedTokens = [];
    }

    public function tokenize(string $payload): void
    {
        $getNextToken = function (string $payload, int $start): array {
            $restPayload = substr($payload, $start);
//            print_r(PHP_EOL.PHP_EOL."REST [ $restPayload ] start: $start");

            $offset = 0;
            $streamToken = '';

            foreach (str_split($restPayload) as $char) {
//                print_r(PHP_EOL."DEBUG-CHAR: [ $char ] [ offset: $offset ] ");

                if (in_array($streamToken, $this->tokens, true)) {
//                    print_r(PHP_EOL."DEBUG-DOUBLE: [ $streamToken ] [ offset: $offset ] ");

                    return [$offset,$streamToken];
                }

                if (in_array($char, $this->tokens, true)) {
//                    print_r(PHP_EOL."DEBUG-SINGLE: [ $char ] [ offset: $offset ] ");

                    if ($streamToken !== '') {
                        return [$offset,$streamToken];
                    }

                    return [$offset,$char];
                }

                $streamToken .= $char;
                $offset++;
            }
            return [$offset,$streamToken];
        };


        /** @psalm-suppress UnusedVariable */
        $firstIsAToken = fn (int $newOffset, int $offset): int => $newOffset === 0 ? ++$offset : $offset;

        $offset = 0;
        while ($offset < strlen($payload)) {
            [$newOffset,$token] = $getNextToken($payload, $offset);

            $offset += $newOffset;
            $offset = $firstIsAToken($newOffset, $offset);
            $this->machedTokens[] = $token;

            if ($timeout++ >= 10000) {
                die(PHP_EOL."timeout: $timeout reach!");
            }
        }
    }

    /**
     * @return string[]
     */
    public function getMatchedTokens(): array
    {
        return $this->machedTokens;
    }
}
