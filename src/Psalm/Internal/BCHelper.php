<?php

namespace Psalm\Internal;

use PhpParser;
use PhpParser\Node;

use function class_exists;

/**
 * @internal
 */
final class BCHelper
{
    public static function usePHPParserV4(): bool
    {
        return class_exists('\PhpParser\Node\Stmt\Throw');
    }

    public static function isThrow(Node $stmt): bool
    {
        if (self::usePHPParserV4()) {
            return $stmt instanceof PhpParser\Node\Stmt\Throw_;
        }

        return $stmt instanceof PhpParser\Node\Stmt\Expression
            && $stmt->expr instanceof PhpParser\Node\Expr\Throw_;
    }
}
