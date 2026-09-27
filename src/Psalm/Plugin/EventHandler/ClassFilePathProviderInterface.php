<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

/**
 * @api
 */
interface ClassFilePathProviderInterface
{
    /**
     * @param int $class interned class name, use {@see \Psalm\Interner::str()} to get the string
     */
    public static function getClassFilePath(int $class): ?string;
}
