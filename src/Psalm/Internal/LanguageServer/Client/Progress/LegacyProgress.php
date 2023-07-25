<?php

namespace Psalm\Internal\LanguageServer\Client\Progress;

use LanguageServerProtocol\LogMessage;
use LanguageServerProtocol\MessageType;
use LogicException;
use Psalm\Internal\LanguageServer\ClientHandler;

/** @internal */
final class LegacyProgress implements ProgressInterface
{
    private ClientHandler $handler;
    private ?string $title = null;

    public function __construct(ClientHandler $handler)
    {
        $this->handler = $handler;
    }

    public function begin(string $title, ?string $message = null, ?int $percentage = null): void
    {
        if ($this->title !== null) {
            throw new LogicException('Progress has already been started');
        }

        $this->title = $title;

        $this->notify($message);
    }

    public function update(?string $message = null, ?int $percentage = null): void
    {
        if ($this->title === null) {
            throw new LogicException('The progress has not been started yet');
        }

        $this->notify($message);
    }

    public function end(?string $message = null): void
    {
        if ($this->title === null) {
            throw new LogicException('The progress has not been started yet');
        }

        $this->notify($message);
    }

    private function notify(?string $message): void
    {
        $this->handler->notify(
            'telemetry/event',
            new LogMessage(
                MessageType::INFO,
                $this->title . (empty($message) ? '' : (': ' . $message)),
            ),
        );
    }
}
