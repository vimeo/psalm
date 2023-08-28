<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Codebase;
use Psalm\Storage\Assertion\IsType;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\Possibilities;
use Psalm\Type\Atomic\TTemplateParam;

use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function reset;
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
            $assertions += array_map(
                fn(Possibilities $possibilities) => $this->modifyAssertionsForInheritance(
                    $possibilities,
                    $this->codebase,
                    $called_class,
                    $inherited_classes_and_interfaces,
                ),
                $potential_assertion_providing_method_storage->assertions,
            );
        }

        return $assertions;
    }

    /**
     * In case the called class is either implementing or extending a class/interface which does also has the
     * template we are searching for, we assume that the called method has the same assertions.
     *
     * @param list<class-string> $potential_assertion_providing_classes
     */
    private function modifyAssertionsForInheritance(
        Possibilities $possibilities,
        Codebase $codebase,
        ClassLikeStorage $called_class,
        array $potential_assertion_providing_classes
    ): Possibilities {
        $replacement = new Possibilities($possibilities->var_id, []);
        $extended_params = $called_class->template_extended_params;
        foreach ($possibilities->rule as $assertion) {
            if (!$assertion instanceof IsType
                || !$assertion->type instanceof TTemplateParam) {
                $replacement->rule[] = $assertion;
                continue;
            }

            /** Called class does not extend the template parameter */
            $extended_templates = $called_class->template_extended_params;
            if (!isset($extended_templates[$assertion->type->defining_class][$assertion->type->param_name])) {
                $replacement->rule[] = $assertion;
                continue;
            }

            foreach ($potential_assertion_providing_classes as $potential_assertion_providing_class) {
                if (!isset($extended_params[$potential_assertion_providing_class][$assertion->type->param_name])) {
                    continue;
                }

                if (!$codebase->classlike_storage_provider->has($potential_assertion_providing_class)) {
                    continue;
                }

                $potential_assertion_providing_classlike_storage = $codebase->classlike_storage_provider->get(
                    $potential_assertion_providing_class,
                );
                if (!isset(
                    $potential_assertion_providing_classlike_storage->template_types[$assertion->type->param_name],
                )) {
                    continue;
                }

                $replacement->rule[] = new IsType(new TTemplateParam(
                    $assertion->type->param_name,
                    reset(
                        $potential_assertion_providing_classlike_storage->template_types[$assertion->type->param_name],
                    ),
                    $potential_assertion_providing_class,
                ));

                continue 2;
            }

            $replacement->rule[] = $assertion;
        }

        return $replacement;
    }
}
