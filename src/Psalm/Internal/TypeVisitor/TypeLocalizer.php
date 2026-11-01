<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Internal\Codebase\Methods;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\MutableUnion;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function array_merge;
use function array_shift;
use function array_values;
use function count;

/**
 * @internal
 */
final class TypeLocalizer extends MutableTypeVisitor
{
    /**
     * @param array<string, array<string, Union>> $extends
     * @psalm-mutation-free
     */
    public function __construct(
        private array $extends,
        private readonly string $base_fq_class_name,
    ) {
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TTemplateParamClass) {
            if ($type->defining_class === $this->base_fq_class_name) {
                if (isset($this->extends[$this->base_fq_class_name][$type->param_name])) {
                    $extended_param = $this->extends[$this->base_fq_class_name][$type->param_name];

                    $types = array_values($extended_param->getAtomicTypes());

                    if (count($types) === 1 && $types[0] instanceof TNamedObject) {
                        $type = $type->setAs(
                            $type->as,
                            $types[0],
                        );
                    } elseif ($type->as_type !== null) {
                        $type = $type->setAs(
                            $type->as,
                            null,
                        );
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
                    $this->extends,
                );

                if ($types_to_add) {
                    $union->removeType($key);

                    foreach ($types_to_add as $extra_added_type) {
                        $union->addType(
                            $extra_added_type === $atomic_type
                                ? $extra_added_type
                                : self::withIntersectionTypes($extra_added_type, $atomic_type->extra_types),
                        );
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

    /**
     * Re-applies the intersection constraints of a replaced template param (`T&Countable`) to its replacement.
     *
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     * @psalm-pure
     */
    private static function withIntersectionTypes(Atomic $replacement, array $extra_types): Atomic
    {
        if (!$extra_types) {
            return $replacement;
        }

        if ($replacement instanceof TNamedObject
            || $replacement instanceof TTemplateParam
            || $replacement instanceof TIterable
            || $replacement instanceof TObjectWithProperties
        ) {
            return $replacement->setIntersectionTypes(array_merge($extra_types, $replacement->extra_types));
        }

        if ($replacement instanceof TObject) {
            $first_extra_type = array_shift($extra_types);

            return $extra_types
                ? $first_extra_type->setIntersectionTypes($extra_types)
                : $first_extra_type;
        }

        return $replacement;
    }
}
