<?php
namespace Psalm\Internal\Json;

use RuntimeException;

use function json_encode;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Provides pretty printed JSON output.
 */
class Json
{
    protected const OPTIONS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @param mixed $data
     * @param int|null $options
     * @return string
     */
    public static function encode($data, ?int $options = null): string
    {
        if ($options === null) {
            $options = static::OPTIONS;
        }

        $result = json_encode($data, $options);
        if ($result === false) {
            throw new RuntimeException('Cannot create JSON string.');
        }

        return $result;
    }
}
