<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;

/**
 * @internal
 */
final class TemplateTypeCollector extends TypeVisitor
{
    /**
     * @var list<TTemplateParam>
     */
    private array $template_types = [];

    /**
     * @param bool $with_derived whether the templates of `key-of<T>`, `value-of<T>`, `properties-of<T>` and `T[K]`
     *     count too
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly bool $with_derived = false,
    ) {
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TTemplateParam) {
            $this->template_types[] = $type;
        } elseif ($type instanceof TTemplateParamClass) {
            $extends = $type->as_type;

            $this->template_types[] = new TTemplateParam(
                $type->param_name,
                $extends ? new Union([$extends]) : Type::getMixed(),
                $type->defining_class,
            );
        } elseif ($type instanceof TConditional) {
            $this->template_types[] = new TTemplateParam(
                $type->param_name,
                Type::getMixed(),
                $type->defining_class,
            );
        } elseif ($this->with_derived
            && ($type instanceof TTemplateKeyOf
                || $type instanceof TTemplateValueOf
                || $type instanceof TTemplatePropertiesOf)
        ) {
            $this->template_types[] = new TTemplateParam($type->param_name, Type::getMixed(), $type->defining_class);
        } elseif ($this->with_derived && $type instanceof TTemplateIndexedAccess) {
            $this->template_types[] = new TTemplateParam(
                $type->array_param_name,
                Type::getMixed(),
                $type->defining_class,
            );
            $this->template_types[] = new TTemplateParam(
                $type->offset_param_name,
                Type::getMixed(),
                $type->defining_class,
            );
        }

        return null;
    }

    /**
     * @return list<TTemplateParam>
     */
    public function getTemplateTypes(): array
    {
        return $this->template_types;
    }
}
