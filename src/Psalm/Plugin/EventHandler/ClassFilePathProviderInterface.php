<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

interface ClassFilePathProviderInterface
{
    /**
     * @param class-string $class
     */
    public static function getClassFilePath(string $class): ?string;
}
