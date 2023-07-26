<?php

namespace Psalm\Internal\LanguageServer\Client\Progress;

use LanguageServerProtocol\LogMessage;
use LanguageServerProtocol\MessageType;
use LogicException;
use Psalm\Internal\LanguageServer\ClientHandler;

/** @internal */
final class LegacyProgress implements ProgressInterface
{
    private const STATUS_INACTIVE = 'inactive';
    private const STATUS_ACTIVE = 'active';
    private const STATUS_FINISHED = 'finished';

    private string $status = self::STATUS_INACTIVE;

    private ClientHandler $handler;
    private ?string $title = null;

    public function __construct(ClientHandler $handler)
    {
        $this->handler = $handler;
    }

    public function begin(string $title, ?string $message = null, ?int $percentage = null): void
    {

        if ($this->status === self::STATUS_ACTIVE) {
            throw new LogicException('Progress has already been started');
        }

        if ($this->status === self::STATUS_FINISHED) {
            throw new LogicException('Progress has already been finished');
        }

        $this->title = $title;

        $this->notify($message);

        $this->status = self::STATUS_ACTIVE;
    }

    public function update(?string $message = null, ?int $percentage = null): void
    {
        if ($this->status === self::STATUS_FINISHED) {
            throw new LogicException('Progress has already been finished');
        }

        if ($this->status === self::STATUS_INACTIVE) {
            throw new LogicException('Progress has not been started yet');
        }

        $this->notify($message);
    }

    public function end(?string $message = null): void
    {
        if ($this->status === self::STATUS_FINISHED) {
            throw new LogicException('Progress has already been finished');
        }

        if ($this->status === self::STATUS_INACTIVE) {
            throw new LogicException('Progress has not been started yet');
        }

        $this->notify($message);

        $this->status = self::STATUS_FINISHED;
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
