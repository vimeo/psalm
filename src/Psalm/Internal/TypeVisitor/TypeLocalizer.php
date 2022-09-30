<?php

namespace Psalm\Internal\TypeVisitor;

use Psalm\Internal\Codebase\Methods;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\MutableUnion;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;

use function array_values;
use function count;

/**
 * @internal
 */
class TypeLocalizer extends TypeVisitor
{
    /**
     * @var array<string, array<string, Union>>
     */
    private array $extends;
    private string $base_fq_class_name;

    /**
     * @param array<string, array<string, Union>> $extends
     */
    public function __construct(
        array $extends,
        string $base_fq_class_name
    ) {
        $this->extends = $extends;
        $this->base_fq_class_name = $base_fq_class_name;
    }

    /**
     * @psalm-suppress InaccessibleProperty Acting on clones
     */
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TTemplateParamClass) {
            if ($type->defining_class === $this->base_fq_class_name) {
                if (isset($this->extends[$this->base_fq_class_name][$type->param_name])) {
                    $extended_param = $this->extends[$this->base_fq_class_name][$type->param_name];

                    $types = array_values($extended_param->getAtomicTypes());

                    if (count($types) === 1 && $types[0] instanceof TNamedObject) {
                        $type = clone $type;
                        $type->as_type = $types[0];
                    } elseif ($type->as_type !== null) {
                        $type = clone $type;
                        $type->as_type = null;
                    }
                }
            }
        }

        if ($type instanceof Union) {
            $union = $type->getBuilder();
        } elseif ($type instanceof MutableUnion) {
            $union = $type;
        } else {
            return null;
        }

        foreach ($union->getAtomicTypes() as $key => $atomic_type) {
            if ($atomic_type instanceof TTemplateParam
                && ($atomic_type->defining_class === $this->base_fq_class_name
                    || isset($this->extends[$atomic_type->defining_class]))
            ) {
                $types_to_add = Methods::getExtendedTemplatedTypes(
                    $atomic_type,
                    $this->extends
                );

                if ($types_to_add) {
                    $union->removeType($key);

                    foreach ($types_to_add as $extra_added_type) {
                        $union->addType($extra_added_type);
                    }
                }
            }
        }

        if ($type instanceof Union) {
            $type = $union->freeze();
        } else {
            $type = $union;
        }

        return null;
    }
}
