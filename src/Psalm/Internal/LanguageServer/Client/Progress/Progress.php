<?php

namespace Psalm\Internal\LanguageServer\Client\Progress;

use LogicException;
use Psalm\Internal\LanguageServer\ClientHandler;

/** @internal */
final class Progress implements ProgressInterface
{
    private ClientHandler $handler;
    private string $token;
    private bool $withPercentage = false;
    private bool $finished = false;

    public function __construct(ClientHandler $handler, string $token)
    {
        $this->handler = $handler;
        $this->token = $token;
    }

    public function begin(
        string $title,
        ?string $message = null,
        ?int $percentage = null
    ): void {
        if ($this->finished) {
            throw new LogicException('Progress has already been finished');
        }

        $notification = [
            'token' => $this->token,
            'value' => [
                'kind' => 'begin',
                'title' => $title,
            ],
        ];

        if ($message !== null) {
            $notification['value']['message'] = $message;
        }

        if ($percentage !== null) {
            $notification['value']['percentage'] = $percentage;
            $this->withPercentage = true;
        }

        $this->handler->notify('$/progress', $notification);
    }

    public function end(?string $message = null): void
    {
        if ($this->finished) {
            throw new LogicException('Progress has already been finished');
        }

        $notification = [
            'token' => $this->token,
            'value' => [
                'kind' => 'end',
            ],
        ];

        if ($message !== null) {
            $notification['value']['message'] = $message;
        }

        $this->handler->notify('$/progress', $notification);

        $this->finished = true;
    }

    public function update(?string $message = null, ?int $percentage = null): void
    {
        if ($this->finished) {
            throw new LogicException('Progress has already been finished');
        }

        $notification = [
            'token' => $this->token,
            'value' => [
                'kind' => 'report',
            ],
        ];

        if ($message !== null) {
            $notification['value']['message'] = $message;
        }

        if ($percentage !== null) {
            if (!$this->withPercentage) {
                throw new LogicException(
                    'Cannot update percentage for progress '
                    . 'that was started without percentage',
                );
            }
            $notification['value']['percentage'] = $percentage;
        }

        $this->handler->notify('$/progress', $notification);
    }
}
