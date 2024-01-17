<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer\Client\Progress;

use LogicException;
use Psalm\Internal\LanguageServer\ClientHandler;

/** @internal */
final class Progress implements ProgressInterface
{
    private const STATUS_INACTIVE = 'inactive';
    private const STATUS_ACTIVE = 'active';
    private const STATUS_FINISHED = 'finished';

    private string $status = self::STATUS_INACTIVE;
    private bool $withPercentage = false;

    public function __construct(
        private readonly ClientHandler $handler,
        private readonly string $token,
    ) {
    }

    public function begin(
        string $title,
        ?string $message = null,
        ?int $percentage = null,
    ): void {
        if ($this->status === self::STATUS_ACTIVE) {
            throw new LogicException('Progress has already been started');
        }

        if ($this->status === self::STATUS_FINISHED) {
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

        $this->status = self::STATUS_ACTIVE;
    }

    public function end(?string $message = null): void
    {
        if ($this->status === self::STATUS_FINISHED) {
            throw new LogicException('Progress has already been finished');
        }

        if ($this->status === self::STATUS_INACTIVE) {
            throw new LogicException('Progress has not been started yet');
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

        $this->status = self::STATUS_FINISHED;
    }

    public function update(?string $message = null, ?int $percentage = null): void
    {
        if ($this->status === self::STATUS_FINISHED) {
            throw new LogicException('Progress has already been finished');
        }

        if ($this->status === self::STATUS_INACTIVE) {
            throw new LogicException('Progress has not been started yet');
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
