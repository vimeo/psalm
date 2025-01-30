<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use Throwable;

use function igbinary_serialize;
use function igbinary_unserialize;
use function sprintf;

/**
 * @internal
 */
final class IgbinarySerializer implements Serializer
{
    public function serialize(mixed $data): string
    {
        try {
            $data = igbinary_serialize($data);
            if ($data === false) {
                throw new SerializationException("Could not serialize data!");
            }
            return $data;
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

    public function unserialize(string $data): mixed
    {
        try {
            return igbinary_unserialize($data);
        } catch (Throwable $exception) {
            throw new SerializationException(
                'Exception thrown when unserializing data',
                0,
                $exception,
            );
        }
    }
}
