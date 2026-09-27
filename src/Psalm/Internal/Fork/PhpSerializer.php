<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use Override;
use Throwable;

use function serialize;
use function sprintf;
use function unserialize;

/**
 * Like Amp\Serialization\NativeSerializer, but without the `unserialize_max_depth` limit (4096 by
 * default): the AST of a file nested a few thousand levels deep can be serialized, and would then
 * fail to be read back. Recursion is still bounded by the call stack.
 *
 * @internal
 */
final class PhpSerializer implements Serializer
{
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
            $result = unserialize($data, ['max_depth' => 0]);
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
