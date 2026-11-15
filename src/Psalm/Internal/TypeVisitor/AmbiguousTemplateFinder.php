<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;

use function count;

/**
 * Finds whether the types the given templates stand for can't be read back from a type they were replaced in: a
 * template sharing a union with other types (`T|int` with T = int|string reads the same as with T = string), or
 * standing in a type derived from it (`key-of<T>`, a conditional type)
 *
 * @internal
 */
final class AmbiguousTemplateFinder extends TypeVisitor
{
    private bool $ambiguous = false;

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
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TConditional
            || $type instanceof TTemplateKeyOf
            || $type instanceof TTemplateValueOf
            || $type instanceof TTemplateIndexedAccess
            || $type instanceof TTemplatePropertiesOf
        ) {
            $this->ambiguous = true;

            return self::STOP_TRAVERSAL;
        }

        if (!$type instanceof Union) {
            return null;
        }

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TTemplateParam
                && isset($this->template_types[$atomic_type->param_name][$atomic_type->defining_class])
                && (count($type->getAtomicTypes()) > 1 || $atomic_type->extra_types !== [])
            ) {
                $this->ambiguous = true;

                return self::STOP_TRAVERSAL;
            }
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->ambiguous;
    }
}
