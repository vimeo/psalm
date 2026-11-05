<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Codebase;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\Possibilities;

use function array_filter;
use function array_values;
use function strtolower;

/**
 * @internal
 * @psalm-capabilities read-props|write-this-props|write-props|write-refs
 */
final class AssertionsFromInheritanceResolver
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Codebase $codebase,
    ) {
    }

    /**
     * @return array<int,Possibilities>
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function resolve(
        MethodStorage $method_storage,
        ClassLikeStorage $called_class,
    ): array {
        return $this->resolveKind($method_storage, $called_class, 'assertions');
    }

    /**
     * @return array<int,Possibilities>
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function resolveIfTrue(
        MethodStorage $method_storage,
        ClassLikeStorage $called_class,
    ): array {
        return $this->resolveKind($method_storage, $called_class, 'if_true_assertions');
    }

    /**
     * @return array<int,Possibilities>
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function resolveIfFalse(
        MethodStorage $method_storage,
        ClassLikeStorage $called_class,
    ): array {
        return $this->resolveKind($method_storage, $called_class, 'if_false_assertions');
    }

    /**
     * @param 'assertions'|'if_true_assertions'|'if_false_assertions' $kind
     * @return array<int,Possibilities>
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    private function resolveKind(
        MethodStorage $method_storage,
        ClassLikeStorage $called_class,
        string $kind,
    ): array {
        $method_name_lc = strtolower($method_storage->cased_name ?? '');

        $pick = static fn(MethodStorage $storage): array => match ($kind) {
            'assertions' => $storage->assertions,
            'if_true_assertions' => $storage->if_true_assertions,
            'if_false_assertions' => $storage->if_false_assertions,
        };

        $assertions = $pick($method_storage);
        $inherited_classes_and_interfaces = array_values(array_filter([
            ...$called_class->parent_classes,
            ...$called_class->class_implements,
        ], fn(string $classOrInterface) => $this->codebase->classOrInterfaceOrEnumExists($classOrInterface)));

        foreach ($inherited_classes_and_interfaces as $potential_assertion_providing_class) {
            $potential_assertion_providing_classlike_storage = $this->codebase->classlike_storage_provider->get(
                $this->codebase->classlikes->getUnAliasedName($potential_assertion_providing_class),
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
            $assertions += $pick($potential_assertion_providing_method_storage);
        }

        return $assertions;
    }
}
