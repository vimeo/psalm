<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Storage\Capabilities;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

use function str_starts_with;

/**
 * Replaces the purity templates of function-likes in a type with their bounds, for a type that
 * outlives the call they are bound by (the property a constructor parameter is promoted to).
 *
 * @internal
 */
final class FunctionPurityTemplateReplacer extends MutableTypeVisitor
{
    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TTemplateParam
            && str_starts_with($type->defining_class, 'fn-')
            && Capabilities::isPurityType($type->as)
        ) {
            $type = new TCapabilities(Capabilities::fromType($type->as));

            return self::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
