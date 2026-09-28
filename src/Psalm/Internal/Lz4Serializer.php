<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use Override;

use function error_get_last;
use function lz4_compress;
use function lz4_uncompress;

/** @internal */
final class Lz4Serializer implements Serializer
{
    public function __construct(private readonly Serializer $serializer)
    {
    }

    #[Override]
    public function serialize(mixed $data): string
    {
        $data = $this->serializer->serialize($data);
        $data = lz4_compress($data, 4);
        if ($data === false) {
            $error = error_get_last();
            throw new SerializationException('Could not compress data: ' . ($error['message'] ?? 'unknown error'));
        }

        return $data;
    }

    #[Override]
    public function unserialize(string $data): mixed
    {
        $data = lz4_uncompress($data);
        if ($data === false) {
            $error = error_get_last();
            throw new SerializationException('Could not decompress data: ' . ($error['message'] ?? 'unknown error'));
        }

        return $this->serializer->unserialize($data);
    }
}
