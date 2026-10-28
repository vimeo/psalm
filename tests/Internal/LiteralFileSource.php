<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Override;
use Psalm\Aliases;
use Psalm\FileSource;

/**
 * A file source whose path is a literal, unlike the real ones whose path Psalm's taint analysis treats as user input.
 *
 * @psalm-immutable
 */
final class LiteralFileSource implements FileSource
{
    public function __construct(private readonly string $path)
    {
    }

    #[Override]
    public function getFileName(): string
    {
        return $this->path;
    }

    #[Override]
    public function getFilePath(): string
    {
        return $this->path;
    }

    #[Override]
    public function getRootFileName(): string
    {
        return $this->path;
    }

    #[Override]
    public function getRootFilePath(): string
    {
        return $this->path;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getAliases(): Aliases
    {
        return new Aliases();
    }
}
