<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableUnion;
use Psalm\Type\TypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * @internal
 */
class FromDocblockSetter extends TypeVisitor
{
    private bool $from_docblock;
    public function __construct(bool $from_docblock)
    {
        $this->from_docblock = $from_docblock;
    }
    /**
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof Atomic && !$type instanceof Union && !$type instanceof MutableUnion) {
            return null;
        }
        if ($type->from_docblock === $this->from_docblock) {
            return null;
        }
        $type = clone $type;
        /** @psalm-suppress InaccessibleProperty Acting on clone */
        $type->from_docblock = $this->from_docblock;

        if ($type instanceof TTemplateParam
            && $type->as->isMixed()
        ) {
            return TypeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
