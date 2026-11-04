<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use Override;

use function error_get_last;
use function gzdeflate;
use function gzinflate;

/** @internal */
final class GzipSerializer implements Serializer
{
    /**
     * The default level 6 takes as long as serializing the data and shrinks storages and ASTs by 10% more than
     * level 1: not worth it for a cache that is written on every cold run.
     */
    private const COMPRESSION_LEVEL = 1;

    /**
     * @psalm-mutation-free
     */
    public function __construct(private readonly Serializer $serializer)
    {
    }

    #[Override]
    public function serialize(mixed $data): string
    {
        $data = $this->serializer->serialize($data);
        $data = gzdeflate($data, self::COMPRESSION_LEVEL);
        if ($data === false) {
            $error = error_get_last();
            throw new SerializationException('Could not compress data: ' . ($error['message'] ?? 'unknown error'));
        }

        return $data;
    }

    #[Override]
    public function unserialize(string $data): mixed
    {
        $data = gzinflate($data);
        if ($data === false) {
            $error = error_get_last();
            throw new SerializationException('Could not decompress data: ' . ($error['message'] ?? 'unknown error'));
        }

        return $this->serializer->unserialize($data);
    }
}
