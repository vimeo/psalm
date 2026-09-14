<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Context\Context;
use Amp\Parallel\Context\ContextException;
use Closure;
use Override;

/**
 * Decorates an amphp {@see Context} to translate worker-death signals into the
 * Psalm-specific diagnostics that the in-tree fork implementation used to emit
 * before the fork logic was upstreamed into amphp/parallel.
 *
 * amphp reports a worker killed by a signal as a {@see ContextException} whose
 * code is the signal number; every other {@see ContextException} carries code 0.
 *
 * @internal
 * @template-covariant TResult
 * @template-covariant TReceive
 * @template TSend
 * @implements Context<TResult, TReceive, TSend>
 */
final class SignalDiagnosticContext implements Context
{
    /**
     * @param Context<TResult, TReceive, TSend> $context
     * @psalm-mutation-free
     */
    public function __construct(private readonly Context $context)
    {
    }

    #[Override]
    public function receive(?Cancellation $cancellation = null): mixed
    {
        try {
            return $this->context->receive($cancellation);
        } catch (ContextException $e) {
            throw self::translate($e);
        }
    }

    #[Override]
    public function send(mixed $data): void
    {
        try {
            $this->context->send($data);
        } catch (ContextException $e) {
            throw self::translate($e);
        }
    }

    #[Override]
    public function join(?Cancellation $cancellation = null): mixed
    {
        try {
            return $this->context->join($cancellation);
        } catch (ContextException $e) {
            throw self::translate($e);
        }
    }

    #[Override]
    public function close(): void
    {
        try {
            $this->context->close();
        } catch (ContextException $e) {
            throw self::translate($e);
        }
    }

    #[Override]
    public function isClosed(): bool
    {
        return $this->context->isClosed();
    }

    #[Override]
    public function onClose(Closure $onClose): void
    {
        $this->context->onClose($onClose);
    }

    /**
     * @psalm-mutation-free
     */
    private static function translate(ContextException $e): ContextException
    {
        $signal = $e->getCode();

        // Non-signal ContextExceptions (connect/fork failures) carry code 0.
        if ($signal <= 0) {
            return $e;
        }

        $detail = match ($signal) {
            11 => "11: THIS IS A PHP BUG, please report this to https://github.com/vimeo/psalm/issues"
                . " AND to https://github.com/php/php-src/issues",
            9 => "9: the process was likely killed by the OOM killer, try increasing the swap space "
                . "or use the arrayCache=\"false\" config to reduce memory usage",
            default => (string) $signal,
        };

        return new ContextException("Worker exited due to signal $detail!");
    }
}
