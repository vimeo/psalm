<?php

declare(strict_types=1);

namespace Psalm\Storage;

/**
 * @psalm-type _MetadataEntry scalar|scalar[]|scalar[][]|scalar[][][]|scalar[][][][]|scalar[][][][][]
 */
trait CustomMetadataTrait
{
    /** @var array<string,_MetadataEntry> */
    public array $custom_metadata = [];
}
