<?php

namespace Psalm\Internal\LanguageServer\PathMapper;

final class NullMapper implements PathMapperInterface
{
    public function mapFromClient(string $path): string
    {
        return $path;
    }

    public function mapToClient(string $path): string
    {
        return $path;
    }
}
