<?php

declare(strict_types=1);

namespace Psalm\Tests\fixtures\AutoloadableStubMerge;

use Override;
use Psalm\Plugin\EventHandler\ClassFilePathProviderInterface;

use function in_array;
use function strlen;
use function substr;

final class ClassFilePathProvider implements ClassFilePathProviderInterface
{
    #[Override]
    public static function getClassFilePath(string $class): ?string
    {
        return in_array($class, ['AutoloadableStubMerge\Foo', 'AutoloadableStubMerge\Factory'], true)
            ? __DIR__ . '/' . substr($class, strlen('AutoloadableStubMerge\\')) . '.php'
            : null;
    }
}
