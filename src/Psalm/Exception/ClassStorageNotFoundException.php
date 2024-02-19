<?php

declare(strict_types=1);

namespace Psalm\Exception;

use InvalidArgumentException;

/**
 * To be thrown when a `\Psalm\Storage\ClassLikeStorage` for a given name could not be resolved.
 */
final class ClassStorageNotFoundException extends InvalidArgumentException
{
    private ?string $name = null;

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
