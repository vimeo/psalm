<?php

declare(strict_types=1);

namespace Psalm\Plugin\Hook;

use Psalm\Codebase;

interface AfterCodebasePopulatedInterface
{
    /**
     * Called after codebase has been populated
     *
     * @return void
     */
    public static function afterCodebasePopulated(Codebase $codebase);
}
