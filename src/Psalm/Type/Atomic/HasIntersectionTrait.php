<?php
namespace Psalm\Type\Atomic;

use Psalm\Type;
use Psalm\Type\Atomic;

trait HasIntersectionTrait
{
    /**
     * @var array<int, TNamedObject|TTemplateParam|TIterable>|null
     */
    public $extra_types;

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     *
     * @return string
     */
    private function getNamespacedIntersectionTypes($namespace, array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        if (!$this->extra_types) {
            return '';
        }

        return '&' . implode(
            '&',
            array_map(
                /**
                 * @param TNamedObject|TTemplateParam|TIterable $extra_type
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
     * @param TNamedObject $type
     *
     * @return void
     */
    public function addIntersectionType(TNamedObject $type)
    {
        $this->extra_types[] = $type;
    }

    /**
     * @return array<int, TNamedObject|TTemplateParam|TIterable>|null
     */
    public function getIntersectionTypes()
    {
        return $this->extra_types;
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>  $template_types
     *
     * @return void
     */
    public function replaceIntersectionTemplateTypesWithArgTypes(array $template_types)
    {
        if (!$this->extra_types) {
            return;
        }

        $new_types = [];

        foreach ($this->extra_types as $extra_type) {
            if ($extra_type instanceof TTemplateParam && isset($template_types[$extra_type->param_name])) {
                $template_type = clone $template_types[$extra_type->param_name][$extra_type->defining_class ?: ''][0];

                foreach ($template_type->getTypes() as $template_type_part) {
                    if ($template_type_part instanceof TNamedObject) {
                        $new_types[] = $template_type_part;
                    }
                }
            } else {
                $new_types[] = $extra_type;
            }
        }

        $this->extra_types = $new_types;
    }
}
