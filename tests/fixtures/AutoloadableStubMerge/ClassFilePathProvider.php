<?php

declare(strict_types=1);

namespace Psalm\Tests\fixtures\AutoloadableStubMerge;

use Override;
use Psalm\Plugin\EventHandler\ClassFilePathProviderInterface;

use function str_starts_with;
use function strlen;
use function substr;

final class ClassFilePathProvider implements ClassFilePathProviderInterface
{
    #[Override]
    public static function getClassFilePath(string $class): ?string
    {
        return str_starts_with($class, 'AutoloadableStubMerge\\')
            ? __DIR__ . '/' . substr($class, strlen('AutoloadableStubMerge\\')) . '.php'
            : null;
    }
}
