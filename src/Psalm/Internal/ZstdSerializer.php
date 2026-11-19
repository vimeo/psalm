<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use Override;

use function error_get_last;
use function zstd_compress;
use function zstd_uncompress;

/** @internal */
final class ZstdSerializer implements Serializer
{
    private const COMPRESSION_LEVEL = 3;

    /**
     * @param string|null $dictionary compresses and decompresses with it, if given
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Serializer $serializer,
        private readonly ?string $dictionary = null,
    ) {
    }

    /** @psalm-mutation-free */
    public function withDictionary(?string $dictionary): self
    {
        return new self($this->serializer, $dictionary);
    }

    #[Override]
    public function serialize(mixed $data): string
    {
        return $this->compress($this->serializer->serialize($data));
    }

    #[Override]
    public function unserialize(string $data): mixed
    {
        return $this->serializer->unserialize($this->uncompress($data));
    }

    /** @throws SerializationException */
    public function compress(string $data): string
    {
        $data = zstd_compress($data, self::COMPRESSION_LEVEL, $this->dictionary);
        if ($data === false) {
            $error = error_get_last();
            throw new SerializationException('Could not compress data: ' . ($error['message'] ?? 'unknown error'));
        }

        return $data;
    }

    /** @throws SerializationException */
    public function uncompress(string $data): string
    {
        $data = zstd_uncompress($data, $this->dictionary);
        if ($data === false) {
            $error = error_get_last();
            throw new SerializationException('Could not decompress data: ' . ($error['message'] ?? 'unknown error'));
        }

        return $data;
    }
}
