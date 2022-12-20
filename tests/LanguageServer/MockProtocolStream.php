<?php

declare(strict_types=1);

namespace Psalm\Tests\LanguageServer;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Psalm\Internal\LanguageServer\EmitterInterface;
use Psalm\Internal\LanguageServer\EmitterTrait;
use Psalm\Internal\LanguageServer\Message;
use Psalm\Internal\LanguageServer\ProtocolReader;
use Psalm\Internal\LanguageServer\ProtocolWriter;

/**
 * A fake duplex protocol stream
 */
class MockProtocolStream implements ProtocolReader, ProtocolWriter, EmitterInterface
{
    use EmitterTrait;
    /**
     * Sends a Message to the client
     *
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function write(Message $msg): Promise
    {
        Loop::defer(function () use ($msg): void {
            $this->emit('message', [Message::parse((string)$msg)]);
        });

        // Create a new promisor
        $deferred = new Deferred;

        $deferred->resolve(null);

        return $deferred->promise();
    }
}
