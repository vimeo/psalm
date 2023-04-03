<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type\Atomic;

use function array_map;
use function array_merge;
use function implode;

/**
 * @psalm-immutable
 */
trait HasIntersectionTrait
{
    /**
     * @var array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject>
     */
    public array $extra_types = [];

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    private function getNamespacedIntersectionTypes(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if (!$this->extra_types) {
            return '';
        }

        return '&' . implode(
            '&',
            array_map(
                /**
                 * @param TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject $extra_type
                 */
                static fn(Atomic $extra_type): string => $extra_type->toNamespacedString(
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    $use_phpdoc_format,
                ),
                $this->extra_types,
            ),
        );
    }

    /**
     * @param TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject $type
     * @return static
     */
    public function addIntersectionType(Atomic $type): self
    {
        return $this->setIntersectionTypes(array_merge(
            $this->extra_types,
            [$type->getKey() => $type],
        ));
    }

    /**
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $types
     * @return static
     */
    public function setIntersectionTypes(array $types): self
    {
        if ($types === $this->extra_types) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->extra_types = $types;
        return $cloned;
    }

    /**
     * @return array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject>
     */
    public function getIntersectionTypes(): array
    {
        return $this->extra_types;
    }

    /**
     * @return array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject>|null
     */
    protected function replaceIntersectionTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): ?array {
        if (!$this->extra_types) {
            return null;
        }

        $new_types = [];

        foreach ($this->extra_types as $extra_type) {
            if ($extra_type instanceof TTemplateParam
                && isset($template_result->lower_bounds[$extra_type->param_name][$extra_type->defining_class])
            ) {
                $template_type = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                    $template_result->lower_bounds[$extra_type->param_name][$extra_type->defining_class],
                    $codebase,
                );

                foreach ($template_type->getAtomicTypes() as $template_type_part) {
                    if ($template_type_part instanceof TNamedObject) {
                        $new_types[$template_type_part->getKey()] = $template_type_part;
                    } elseif ($template_type_part instanceof TTemplateParam) {
                        $new_types[$template_type_part->getKey()] = $template_type_part;
                    }
                }
            } else {
                $extra_type = $extra_type->replaceTemplateTypesWithArgTypes($template_result, $codebase);
                $new_types[$extra_type->getKey()] = $extra_type;
            }
        }

        return $new_types === $this->extra_types ? null : $new_types;
    }

    /**
     * @return array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject>|null
     */
    protected function replaceIntersectionTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer = null,
        ?Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0
    ): ?array {
        if (!$this->extra_types) {
            return null;
        }
        $new_types = [];
        foreach ($this->extra_types as $type) {
            $type = $type->replaceTemplateTypesWithStandins(
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                $depth,
            );
            $new_types[$type->getKey()] = $type;
        }

        return $new_types === $this->extra_types ? null : $new_types;
    }
}
