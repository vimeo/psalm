<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\TypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * @internal
 */
class FromDocblockSetter extends TypeVisitor
{
    /**
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof Atomic && !$type instanceof Union && !$type instanceof MutableUnion) {
            return null;
        }
        $type->from_docblock = true;

        if ($type instanceof TTemplateParam
            && $type->as->isMixed()
        ) {
            return TypeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
