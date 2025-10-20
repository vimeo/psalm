<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;

/**
 * Storage for property hooks ('get' & 'set') introduced in PHP 8.4
 */
final class PropertyHookStorage
{
    use UnserializeMemoryUsageSuppressionTrait;

    public bool $is_final = false;

    /**
     * Whether the hook returns by reference
     */
    public bool $by_ref = false;

    public ?CodeLocation $location = null;

    /**
     * @param 'get'|'set' $name
     */
    public function __construct(public string $name)
    {
    }
}
