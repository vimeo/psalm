<?php
namespace Psalm\Plugin\Hook;

use Psalm\Codebase;

/** @deprecated going to be removed in Psalm 5 */
interface AfterCodebasePopulatedInterface
{
    /**
     * Called after codebase has been populated
     */
    public static function afterCodebasePopulated(Codebase $codebase): void;
}
