<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

trait HasIntersectionTrait
{
    /**
     * @var array<int, TNamedObject|TGenericParam|TIterable>|null
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
                 * @param TNamedObject|TGenericParam|TIterable $extra_type
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
}
