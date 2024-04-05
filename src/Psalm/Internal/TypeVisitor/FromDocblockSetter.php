<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\MutableUnion;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * @internal
 */
final class FromDocblockSetter extends MutableTypeVisitor
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
        if ($type instanceof MutableUnion) {
            $type->from_docblock = true;
        } elseif ($type instanceof Union) {
            $type = $type->setProperties(['from_docblock' => $this->from_docblock]);
        } else {
            $type = $type->setFromDocblock($this->from_docblock);
        }

        if ($type instanceof TTemplateParam
            && $type->as->isMixed()
        ) {
            return self::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }
}
