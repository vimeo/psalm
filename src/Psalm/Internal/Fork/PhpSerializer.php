<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use Fiber;
use Override;
use Psalm\Internal\CliUtils;
use Throwable;

use function ini_get;
use function ini_set;
use function serialize;
use function sprintf;
use function unserialize;

/**
 * Like Amp\Serialization\NativeSerializer, but able to read back deeper payloads than PHP's default
 * `unserialize_max_depth` of 4096: every level of a nested array literal takes about three levels in
 * the serialized AST, so files nested only ~1400 levels deep could be written to the cache and then
 * not be read back.
 *
 * unserialize() has no call stack guard and crashes the process once the stack runs out, and the
 * stack it runs on may be small (a default 2 MiB fiber, a restricted main thread). So payloads are
 * first read with the configured limit, and only if that fails, read again with a limit of
 * DEEP_MAX_DEPTH on a dedicated fiber with a stack of known size. Measured on PHP 8.5 (macOS arm64),
 * serialized ASTs exhaust the 16 MiB fiber stack CliUtils::ensureFiberStackSize() provides at a depth of about 50,000.
 *
 * A non-default `unserialize_max_depth` is used as configured, without the retry.
 *
 * @internal
 */
final class PhpSerializer implements Serializer
{
    public const DEEP_MAX_DEPTH = 10_000;

    private const DEFAULT_MAX_DEPTH = 4096;

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
        $max_depth = (int) ini_get('unserialize_max_depth');

        try {
            /** @psalm-suppress MixedAssignment */
            $result = @unserialize($data, ['max_depth' => $max_depth]);
        } catch (Throwable) {
            $result = false;
        }

        if ($result === false && $data !== serialize(false)) {
            if ($max_depth !== self::DEFAULT_MAX_DEPTH) {
                /** @psalm-suppress MixedAssignment */
                $result = self::unserializeWithoutSuppression($data, $max_depth);
            } else {
                /** @psalm-suppress MixedAssignment */
                $result = self::unserializeDeep($data);
            }
        }

        return $result;
    }

    private static function unserializeDeep(string $data): mixed
    {
        $previous_stack_size = (string) ini_get('fiber.stack_size');
        CliUtils::ensureFiberStackSize();

        try {
            // the stack is allocated when the fiber starts, and it runs to completion right away
            $fiber = new Fiber(
                static fn(): mixed => self::unserializeWithoutSuppression($data, self::DEEP_MAX_DEPTH),
            );
            $fiber->start();
        } finally {
            ini_set('fiber.stack_size', $previous_stack_size);
        }

        return $fiber->getReturn();
    }

    private static function unserializeWithoutSuppression(string $data, int $max_depth): mixed
    {
        try {
            /** @psalm-suppress MixedAssignment */
            $result = unserialize($data, ['max_depth' => $max_depth]);
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
}
