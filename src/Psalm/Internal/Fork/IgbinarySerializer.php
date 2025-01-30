<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;

/**
 * @internal
 */
final class IgbinarySerializer implements Serializer
{
    public function __construct()
    {
    }

    public function serialize($data): string
    {
        try {
            return \igbinary_serialize($data);
        } catch (\Throwable $exception) {
            throw new SerializationException(
                \sprintf('The given data could not be serialized: %s', $exception->getMessage()),
                0,
                $exception
            );
        }
    }

    public function unserialize(string $data)
    {
        try {
            return \igbinary_unserialize($data);
        } catch (\Throwable $exception) {
            throw new SerializationException('Exception thrown when unserializing data', 0, $exception);
        }
    }
}
