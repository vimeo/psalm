<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type\Union;

/**
 * Storage for property hooks ('get' & 'set') introduced in PHP 8.4
 *
 * @psalm-external-mutation-free
 * @api
 */
final class PropertyHookStorage
{
    use UnserializeMemoryUsageSuppressionTrait;

    public ?Union $param_type = null;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public bool $is_get,
        public bool $is_final,
        public bool $by_ref,
        public ?CodeLocation $location,
        ?Union $param_type = null,
    ) {
        $this->param_type = $param_type;
    }
}
