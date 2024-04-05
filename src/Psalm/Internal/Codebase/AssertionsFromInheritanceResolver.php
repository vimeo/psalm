<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Codebase;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\Possibilities;

use function array_filter;
use function array_merge;
use function array_values;
use function strtolower;

/**
 * @internal
 */
final class AssertionsFromInheritanceResolver
{
    private Codebase $codebase;

    public function __construct(
        Codebase $codebase
    ) {
        $this->codebase = $codebase;
    }

    /**
     * @return array<int,Possibilities>
     */
    public function resolve(
        MethodStorage $method_storage,
        ClassLikeStorage $called_class
    ): array {
        $method_name_lc = strtolower($method_storage->cased_name ?? '');

        $assertions = $method_storage->assertions;
        $inherited_classes_and_interfaces = array_values(array_filter(array_merge(
            $called_class->parent_classes,
            $called_class->class_implements,
        ), fn(string $classOrInterface) => $this->codebase->classOrInterfaceOrEnumExists($classOrInterface)));

        foreach ($inherited_classes_and_interfaces as $potential_assertion_providing_class) {
            $potential_assertion_providing_classlike_storage = $this->codebase->classlike_storage_provider->get(
                $potential_assertion_providing_class,
            );
            if (!isset($potential_assertion_providing_classlike_storage->methods[$method_name_lc])) {
                continue;
            }

            $potential_assertion_providing_method_storage = $potential_assertion_providing_classlike_storage
                ->methods[$method_name_lc];

            /**
             * Since the inheritance does not provide its own assertions, we have to detect those
             * from inherited classes
             */
            $assertions += $potential_assertion_providing_method_storage->assertions;
        }

        return $assertions;
    }
}
