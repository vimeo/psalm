<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
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
