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
    private const ASSERTIONS = 0;
    private const IF_TRUE_ASSERTIONS = 1;
    private const IF_FALSE_ASSERTIONS = 2;

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
        return $this->resolveKind($method_storage, $called_class, self::ASSERTIONS);
    }

    /**
     * @return array<int,Possibilities>
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function resolveIfTrue(
        MethodStorage $method_storage,
        ClassLikeStorage $called_class,
    ): array {
        return $this->resolveKind($method_storage, $called_class, self::IF_TRUE_ASSERTIONS);
    }

    /**
     * @return array<int,Possibilities>
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public function resolveIfFalse(
        MethodStorage $method_storage,
        ClassLikeStorage $called_class,
    ): array {
        return $this->resolveKind($method_storage, $called_class, self::IF_FALSE_ASSERTIONS);
    }

    /**
     * @param self::* $kind
     * @return array<int,Possibilities>
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    private function resolveKind(
        MethodStorage $method_storage,
        ClassLikeStorage $called_class,
        int $kind,
    ): array {
        $method_name_lc = strtolower($method_storage->cased_name ?? '');

        $assertions = self::getAssertionsOfKind($method_storage, $kind);
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
            $assertions += self::getAssertionsOfKind($potential_assertion_providing_method_storage, $kind);
        }

        return $assertions;
    }

    /**
     * @param self::* $kind
     * @return array<int,Possibilities>
     * @psalm-capabilities read-props
     */
    private static function getAssertionsOfKind(MethodStorage $method_storage, int $kind): array
    {
        return match ($kind) {
            self::IF_TRUE_ASSERTIONS => $method_storage->if_true_assertions,
            self::IF_FALSE_ASSERTIONS => $method_storage->if_false_assertions,
            default => $method_storage->assertions,
        };
    }
}
