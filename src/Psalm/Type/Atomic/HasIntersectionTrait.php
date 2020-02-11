<?php
namespace Psalm\Type\Atomic;

use function array_map;
use function implode;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic;

trait HasIntersectionTrait
{
    /**
     * @var array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties>|null
     */
    public $extra_types;

    /**
     * @param  array<string, string> $aliased_classes
     */
    private function getNamespacedIntersectionTypes(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ) : string {
        if (!$this->extra_types) {
            return '';
        }

        return '&' . implode(
            '&',
            array_map(
                /**
                 * @param TNamedObject|TTemplateParam|TIterable|TObjectWithProperties $extra_type
                 *
                 * @return string
                 */
                function (Atomic $extra_type) use (
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    $use_phpdoc_format
                ) {
                    return $extra_type->toNamespacedString(
                        $namespace,
                        $aliased_classes,
                        $this_class,
                        $use_phpdoc_format
                    );
                },
                $this->extra_types
            )
        );
    }

    /**
     * @param TNamedObject|TTemplateParam|TIterable|TObjectWithProperties $type
     */
    public function addIntersectionType(Type\Atomic $type) : void
    {
        $this->extra_types[$type->getKey()] = $type;
    }

    /**
     * @return array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties>|null
     */
    public function getIntersectionTypes() : ?array
    {
        return $this->extra_types;
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>  $template_types
     */
    public function replaceIntersectionTemplateTypesWithArgTypes(array $template_types, ?Codebase $codebase) : void
    {
        if (!$this->extra_types) {
            return;
        }

        $new_types = [];

        foreach ($this->extra_types as $extra_type) {
            if ($extra_type instanceof TTemplateParam
                && isset($template_types[$extra_type->param_name][$extra_type->defining_class])
            ) {
                $template_type = clone $template_types[$extra_type->param_name][$extra_type->defining_class][0];

                foreach ($template_type->getAtomicTypes() as $template_type_part) {
                    if ($template_type_part instanceof TNamedObject) {
                        $new_types[$template_type_part->getKey()] = $template_type_part;
                    }
                }
            } else {
                $extra_type->replaceTemplateTypesWithArgTypes($template_types, $codebase);
                $new_types[$extra_type->getKey()] = $extra_type;
            }
        }

        $this->extra_types = $new_types;
    }

    /**
     * @return list<Type\Atomic\TTemplateParam>
     */
    public function getIntersectionTemplateTypes() : array
    {
        $template_types = [];

        if ($this->extra_types) {
            foreach ($this->extra_types as $extra_type) {
                $template_types = \array_merge($template_types, $extra_type->getTemplateTypes());
            }
        }

        return $template_types;
    }

    /**
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     * @param  bool             $inferred
     *
     * @return false|null
     */
    public function checkIntersectionTypes(
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        array $phantom_classes = [],
        bool $inferred = true,
        bool $prevent_template_covariance = false
    ) {
        if ($this->extra_types) {
            $codebase = $source->getCodebase();

            foreach ($this->extra_types as $extra_type) {
                if ($extra_type instanceof TTemplateParam
                    || $extra_type instanceof Type\Atomic\TObjectWithProperties
                ) {
                    continue;
                }

                if ($code_location instanceof CodeLocation\DocblockTypeLocation
                    && $codebase->store_node_types
                    && $extra_type->offset_start !== null
                    && $extra_type->offset_end !== null
                ) {
                    $codebase->analyzer->addOffsetReference(
                        $source->getFilePath(),
                        $code_location->raw_file_start + $extra_type->offset_start,
                        $code_location->raw_file_start + $extra_type->offset_end,
                        $extra_type->value
                    );
                }

                if (!isset($phantom_classes[\strtolower($extra_type->value)]) &&
                    \Psalm\Internal\Analyzer\ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $source,
                        $extra_type->value,
                        $code_location,
                        null,
                        $suppressed_issues,
                        $inferred,
                        false,
                        true,
                        $this->from_docblock
                    ) === false
                ) {
                    return false;
                }
            }
        }
    }
}
