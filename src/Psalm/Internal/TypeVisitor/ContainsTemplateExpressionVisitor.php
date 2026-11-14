<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * Finds a type computed from templates, as `T[K]` or `(T is string ? A : B)`, rather than a template itself
 *
 * @internal
 */
final class ContainsTemplateExpressionVisitor extends TypeVisitor
{
    private bool $contains_template_expression = false;

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TConditional
            || $type instanceof TTemplateIndexedAccess
            || $type instanceof TTemplateKeyOf
            || $type instanceof TTemplateValueOf
            || $type instanceof TTemplatePropertiesOf
        ) {
            $this->contains_template_expression = true;
            return self::STOP_TRAVERSAL;
        }

        return null;
    }

    /**
     * @psalm-mutation-free
     */
    public function matches(): bool
    {
        return $this->contains_template_expression;
    }
}
