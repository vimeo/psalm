<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Type\Atomic;

use function array_map;
use function array_merge;
use function array_values;
use function count;
use function implode;

/**
 * @psalm-immutable
 * @api
 */
trait HasIntersectionTrait
{
    /**
     * @var array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject>
     */
    public array $extra_types = [];

    /**
     * A template param is parenthesised so that `(T as A)&B` doesn't read as `T as A&B`
     */
    protected function getIntersectionTypesId(bool $exact): string
    {
        if (!$this->extra_types) {
            return '';
        }

        return '&' . implode(
            '&',
            array_map(
                static fn(Atomic $extra_type): string => $exact
                    && $extra_type instanceof TTemplateParam
                    && !$extra_type->extra_types
                        ? '(' . $extra_type->getId() . ')'
                        : $extra_type->getId($exact),
                $this->extra_types,
            ),
        );
    }

    /**
     * The intersected types are compared in order, like the ids that Union::equals() compares when they are cached,
     * and by key like the atomic types of a union
     *
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $other_types
     * @psalm-mutation-free
     */
    protected function intersectionTypesEqual(array $other_types, bool $ensure_source_equality): bool
    {
        if (count($this->extra_types) !== count($other_types)) {
            return false;
        }

        $other_types = array_values($other_types);

        foreach (array_values($this->extra_types) as $i => $extra_type) {
            if ($extra_type->getKey() !== $other_types[$i]->getKey()
                || !$extra_type->equals($other_types[$i], $ensure_source_equality)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    private function getNamespacedIntersectionTypes(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        if (!$this->extra_types) {
            return '';
        }

        $type_strings = [];

        foreach ($this->extra_types as $extra_type) {
            $type_string = $extra_type->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                $use_phpdoc_format,
            );

            // in phpdoc format, a template param is written as its bound, which can be a union
            $type_strings[] = TypeTokenizer::hasTopLevelOperator($type_string, '|')
                ? '(' . $type_string . ')'
                : $type_string;
        }

        return '&' . implode('&', $type_strings);
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
        ?Codebase $codebase,
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
        int $depth = 0,
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
