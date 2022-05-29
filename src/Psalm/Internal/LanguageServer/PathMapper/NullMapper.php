<?php

namespace Psalm\Internal\LanguageServer\PathMapper;

/** @internal */
final class NullMapper implements PathMapperInterface
{
    public function mapFromClient(string $path, string $client_root): string
    {
        return $path;
    }

    public function mapToClient(string $path, string $client_root): string
    {
        return $path;
    }
}
