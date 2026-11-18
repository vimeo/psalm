<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @internal
 * @psalm-immutable
 */
final class KeyValuePair extends UnresolvedConstantComponent
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly ?UnresolvedConstantComponent $key,
        public readonly UnresolvedConstantComponent $value,
    ) {
    }
}
