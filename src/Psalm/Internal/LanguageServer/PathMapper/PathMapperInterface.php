<?php

namespace Psalm\Internal\LanguageServer\PathMapper;

interface PathMapperInterface
{
    public function mapFromClient(string $path): string;
    public function mapToClient(string $path): string;
}
