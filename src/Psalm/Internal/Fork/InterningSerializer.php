<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Serialization\Serializer;
use Override;
use Psalm\Interner;

/**
 * Wraps a serializer used for IPC between the main process and forked workers, shipping all
 * strings interned since the previous message along with each message, so that the receiving
 * process can resolve every interned id it receives.
 *
 * One instance is created per IPC channel before forking, so both ends of the channel start in sync.
 *
 * @internal
 */
final class InterningSerializer implements Serializer
{
    private int $sent;

    /**
     * @psalm-mutation-free
     */
    public function __construct(private readonly Serializer $serializer)
    {
        $this->sent = Interner::count();
    }

    #[Override]
    public function serialize(mixed $data): string
    {
        $count = Interner::count();
        $strings = Interner::getSince($this->sent);
        $this->sent = $count;
        return $this->serializer->serialize([$strings, $data]);
    }

    #[Override]
    public function unserialize(string $data): mixed
    {
        /** @var array{list<string>, mixed} */
        $data = $this->serializer->unserialize($data);
        Interner::import($data[0]);
        return $data[1];
    }
}
