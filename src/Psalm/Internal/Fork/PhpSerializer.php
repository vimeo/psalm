<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use Override;
use Throwable;

use function ini_get;
use function serialize;
use function sprintf;
use function unserialize;

/**
 * Like Amp\Serialization\NativeSerializer, but with a higher `unserialize_max_depth` than PHP's
 * default of 4096: every level of a nested array literal takes about three levels in the serialized
 * AST, so files nested only ~1400 levels deep could be written to the cache and then not be read back.
 *
 * The limit stays finite, as unserialize() has no call stack guard and crashes the process once
 * the stack runs out. Measured on PHP 8.5 (macOS arm64), serialized ASTs overflow at a depth of about
 * 25,000 on an 8 MiB main thread stack and about 50,000 in a 16 MiB fiber, so this leaves a margin
 * of more than 2x. A larger `unserialize_max_depth`, or 0 for no limit, configured by the
 * administrator is respected.
 *
 * @internal
 */
final class PhpSerializer implements Serializer
{
    public const MINIMUM_MAX_DEPTH = 10_000;

    #[Override]
    public function serialize(mixed $data): string
    {
        try {
            return serialize($data);
        } catch (Throwable $exception) {
            throw new SerializationException(
                sprintf(
                    'The given data could not be serialized: %s',
                    $exception->getMessage(),
                ),
                0,
                $exception,
            );
        }
    }

    #[Override]
    public function unserialize(string $data): mixed
    {
        try {
            /** @psalm-suppress MixedAssignment */
            $result = unserialize($data, ['max_depth' => self::getMaxDepth()]);
        } catch (Throwable $exception) {
            throw new SerializationException(
                'Exception thrown when unserializing data',
                0,
                $exception,
            );
        }

        if ($result === false && $data !== serialize(false)) {
            throw new SerializationException('Invalid data provided to unserialize');
        }

        return $result;
    }

    public static function getMaxDepth(): int
    {
        $configured = (int) ini_get('unserialize_max_depth');

        return $configured === 0 || $configured > self::MINIMUM_MAX_DEPTH ? $configured : self::MINIMUM_MAX_DEPTH;
    }
}
