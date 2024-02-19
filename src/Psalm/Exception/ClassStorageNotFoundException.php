<?php

declare(strict_types=1);

namespace Psalm\Exception;

use InvalidArgumentException;
use Throwable;

/**
 * To be thrown when a `\Psalm\Storage\ClassLikeStorage` for a given name could not be resolved.
 */
final class ClassStorageNotFoundException extends InvalidArgumentException
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public readonly ?string $name;

    public function __construct(string $message = '', int $code = 0, Throwable $previous = null, string $name = null)
    {
        parent::__construct($message, $code, $previous);
        $this->name = $name;
    }
}
