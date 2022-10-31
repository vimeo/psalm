<?php

namespace Psalm\Internal\Json;

use RuntimeException;

use function json_encode;
use function json_last_error_msg;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Provides ability of pretty printed JSON output.
 *
 * @internal
 */
class Json
{
    public const PRETTY = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @var int
     */
    public const DEFAULT = 0;

    /**
     * @param mixed $data
     *
     *
     * @psalm-pure
     */
    public static function encode($data, ?int $options = null): string
    {
        if ($options === null) {
            $options = self::DEFAULT;
        }

        $result = json_encode($data, $options);
        if ($result === false) {
            /** @psalm-suppress ImpureFunctionCall */
            throw new RuntimeException('Cannot create JSON string: '.json_last_error_msg());
        }

        return $result;
    }
}
