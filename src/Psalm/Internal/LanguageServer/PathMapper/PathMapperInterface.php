<?php

namespace Psalm\Internal\LanguageServer\PathMapper;

/** @internal */
interface PathMapperInterface
{
    public function mapFromClient(string $path, string $client_root): string;
    public function mapToClient(string $path, string $client_root): string;
}
