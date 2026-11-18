<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function count;

/**
 * Replaces with mixed the types the given templates can't be read back from once they stand for their bounds: a
 * template sharing a union with other types (`T|int` reads the same with T = int|string as with T = string), or
 * standing in a type derived from it (`key-of<T>`, a conditional type)
 *
 * @internal
 */
final class AmbiguousTemplateRemover extends MutableTypeVisitor
{
    /**
     * @param array<string, array<string, Union>> $template_types
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly array $template_types,
    ) {
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof Union) {
            return null;
        }

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TConditional
                || $atomic_type instanceof TTemplateKeyOf
                || $atomic_type instanceof TTemplateValueOf
                || $atomic_type instanceof TTemplateIndexedAccess
                || $atomic_type instanceof TTemplatePropertiesOf
                || ($atomic_type instanceof TTemplateParam
                    && isset($this->template_types[$atomic_type->param_name][$atomic_type->defining_class])
                    && (count($type->getAtomicTypes()) > 1 || $atomic_type->extra_types !== []))
            ) {
                $type = Type::getMixed();

                return self::DONT_TRAVERSE_CHILDREN;
            }
        }

        return null;
    }
}
