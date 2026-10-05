<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallPurityAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\Capabilities;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function in_array;

/**
 * Resolves the capabilities a call needs when purity templates are involved.
 *
 * A function-like annotated with `@psalm-purity-from-template T` needs, on top of its own
 * capabilities, those of the closures the template `T` is bound to at each call: this is how
 * higher-order functions are polymorphic in the purity of the callbacks they invoke. Inside
 * such a function-like, calling a closure whose purity *is* `T` is not an effect of its own
 * body: the responsibility is deferred to its callers.
 *
 * @internal
 */
final class CallPurityResolver
{
    /**
     * The purity templates the function-like being analysed inherits its purity from, including
     * those of the function-likes a closure is nested in: like their captured variables, closures
     * share the purity templates of their enclosing scope.
     *
     * @return list<string>
     * @psalm-mutation-free
     */
    public static function getEnclosingPurityTemplates(StatementsAnalyzer $statements_analyzer): array
    {
        $templates = [];
        $source = $statements_analyzer->getSource();

        while ($source instanceof FunctionLikeAnalyzer) {
            $templates = [...$templates, ...$source->getStorage()->purity_from_templates];

            $source = $source->getSource();

            if ($source instanceof StatementsAnalyzer) {
                $source = $source->getSource();
            }
        }

        return $templates;
    }

    /**
     * The purity templates of the function-likes a closure is nested in, and of the class of the
     * method it is nested in: a closure calling a closure whose purity is one of them, directly or
     * through a function-like inheriting its purity from it, has that purity itself, which its type
     * carries, whether or not the function-like inherits its purity from the template (a function
     * returning a `Closure[P](): int` that calls a parameter typed `Closure[P](): int` does not call
     * it itself).
     *
     * @return list<string>
     * @psalm-capabilities read-props
     */
    private static function getOuterPurityTemplates(StatementsAnalyzer $statements_analyzer): array
    {
        $templates = [];
        $source = $statements_analyzer->getSource();

        if (!$source instanceof ClosureAnalyzer) {
            return [];
        }

        while ($source instanceof FunctionLikeAnalyzer) {
            $storage = $source->getStorage();
            $template_types = $storage->template_types ?? [];

            if ($storage instanceof MethodStorage && $storage->defining_fqcln !== null) {
                $template_types += $statements_analyzer->getCodebase()->classlike_storage_provider
                    ->get($storage->defining_fqcln)->template_types ?? [];
            }

            foreach ($template_types as $template_name => $bounds) {
                foreach ($bounds as $bound) {
                    if (Capabilities::isPurityType($bound)) {
                        $templates[] = $template_name;
                    }
                }
            }

            $source = $source->getSource();

            if ($source instanceof StatementsAnalyzer) {
                $source = $source->getSource();
            }
        }

        return $templates;
    }

    /**
     * The purity templates that require nothing from the function-like being analysed: those it
     * inherits its purity from, and, in a closure, those of the scopes it is nested in.
     *
     * @return list<string>
     * @psalm-capabilities read-props
     */
    private static function getExemptPurityTemplates(StatementsAnalyzer $statements_analyzer): array
    {
        return [
            ...self::getEnclosingPurityTemplates($statements_analyzer),
            ...self::getOuterPurityTemplates($statements_analyzer),
        ];
    }

    /**
     * The capabilities a purity type (e.g. the purity of a closure being called) requires from
     * the function-like being analysed. Purity templates that function-like inherits its purity
     * from require nothing here; other unresolved templates count as their upper bound.
     *
     * A closure relying on such a template is recorded as depending on it: its own type then
     * carries the template instead of a fixed purity (see {@see FunctionLikeAnalyzer::$used_purity_templates}).
     */
    public static function resolvePurity(Union $purity, StatementsAnalyzer $statements_analyzer): int
    {
        $exempt = self::getExemptPurityTemplates($statements_analyzer);

        if ($exempt !== []) {
            $source = $statements_analyzer->getSource();

            if ($source instanceof FunctionLikeAnalyzer) {
                self::recordUsedTemplates($purity, $exempt, $source);
            }
        }

        return self::resolveWithExemptions($purity, $exempt);
    }

    /**
     * @param list<string> $exempt
     */
    private static function recordUsedTemplates(Union $purity, array $exempt, FunctionLikeAnalyzer $source): void
    {
        foreach ($purity->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TTemplateParam) {
                if (in_array($atomic->param_name, $exempt, true)) {
                    $source->used_purity_templates[$atomic->param_name] = $atomic;
                } else {
                    self::recordUsedTemplates($atomic->as, $exempt, $source);
                }
            } elseif ($atomic instanceof TClosure || $atomic instanceof TCallable) {
                self::recordUsedTemplates($atomic->purity, $exempt, $source);
            }
        }
    }

    /**
     * The capabilities a call to $storage requires from the caller: the callee's own, plus those
     * of the closures its purity templates are bound to at this call (through the method-level
     * template bindings, or the class-level template params of the receiver).
     *
     * A class template bound on the type of a method call's receiver says what the method does, so
     * its writes to `$this` cost the caller what the method's own would (write-props when the
     * receiver is not the caller's `$this`); $receiver_is_this is null for a call without a
     * receiver. They are not waived for a fresh receiver: the `TPurity` of a generator is what its
     * body does to the `$this` of whoever created it, not to the generator.
     *
     * The bound closures' purity is resolved like that of a closure being called
     * ({@see self::resolvePurity()}), so a closure passing on one relying on a purity template
     * carries the template too.
     *
     * @param array<string, array<string, Union>> $class_template_params
     */
    public static function getCallCapabilities(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        FunctionLikeStorage $storage,
        int $capabilities,
        ?TemplateResult $template_result,
        array $class_template_params = [],
        ?bool $receiver_is_this = null,
        bool $receiver_from_global_state = false,
    ): int {
        if ($storage->purity_from_templates === []) {
            return $capabilities;
        }

        foreach ($storage->purity_from_templates as $template_name) {
            $bound = self::resolveMethodTemplateType($template_name, $template_result);
            $on_receiver = false;

            if ($bound === null) {
                $bound = self::resolveClassTemplateType(
                    $template_name,
                    $class_template_params,
                    $codebase,
                    $storage instanceof MethodStorage ? $storage->defining_fqcln : null,
                );
                $on_receiver = $receiver_is_this !== null;
            }

            // an unbound template (e.g. the closure was omitted or null) requires nothing
            if ($bound === null) {
                continue;
            }

            $required = self::resolvePurity($bound, $statements_analyzer);

            $capabilities |= $on_receiver
                ? MethodCallPurityAnalyzer::getCapabilitiesForReceiver(
                    $required,
                    $receiver_is_this === true,
                    $receiver_from_global_state,
                )
                : $required;
        }

        return $capabilities;
    }

    /**
     * @param list<string> $exempt
     * @psalm-mutation-free
     */
    private static function resolveWithExemptions(Union $purity, array $exempt): int
    {
        $capabilities = Capabilities::NONE;

        foreach ($purity->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TCapabilities) {
                $capabilities |= $atomic->capabilities;
            } elseif ($atomic instanceof TTemplateParam) {
                if (!in_array($atomic->param_name, $exempt, true)) {
                    $capabilities |= self::resolveWithExemptions($atomic->as, $exempt);
                }
            } elseif ($atomic instanceof TClosure || $atomic instanceof TCallable) {
                // a type template bound to a closure type carries the closure's purity
                $capabilities |= self::resolveWithExemptions($atomic->purity, $exempt);
            } elseif ($atomic instanceof TNever || $atomic instanceof TNull) {
                // no closure was passed (an omitted or null argument): nothing is required
            } else {
                $capabilities |= Capabilities::ALL;
            }
        }

        return $capabilities;
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function resolveMethodTemplateType(string $template_name, ?TemplateResult $template_result): ?Union
    {
        if ($template_result !== null && isset($template_result->lower_bounds[$template_name])) {
            $atomics = [];

            foreach ($template_result->lower_bounds[$template_name] as $bound_list) {
                foreach ($bound_list as $bound) {
                    // a template no argument bound is defaulted to its upper bound, without an
                    // argument offset: nothing was passed, so nothing is required
                    if ($bound->arg_offset !== null) {
                        // the call requires what every position the template is inferred from
                        // requires: unlike the template's type, no bound is dropped for a deeper one
                        foreach ($bound->type->getAtomicTypes() as $atomic) {
                            $atomics[] = $atomic;
                        }
                    }
                }
            }

            if ($atomics !== []) {
                return new Union($atomics);
            }
        }

        return null;
    }

    /**
     * @param array<string, array<string, Union>> $class_template_params
     * @psalm-external-mutation-free
     */
    private static function resolveClassTemplateType(
        string $template_name,
        array $class_template_params,
        Codebase $codebase,
        ?string $defining_class = null,
    ): ?Union {
        // the template of the class defining the method, which may share its name with the
        // templates of the classes extending it (`TPurity` of IteratorIterator and FilterIterator)
        if ($defining_class !== null && isset($class_template_params[$template_name][$defining_class])) {
            return self::resolveStandins(
                $class_template_params[$template_name][$defining_class],
                $class_template_params,
                $codebase,
            );
        }

        if (isset($class_template_params[$template_name])) {
            $type = null;

            foreach ($class_template_params[$template_name] as $bound_type) {
                $bound_type = self::resolveStandins($bound_type, $class_template_params, $codebase);

                $type = $type === null
                    ? $bound_type
                    : Type::combineUnionTypes($type, $bound_type, $codebase);
            }

            return $type;
        }

        return null;
    }

    /**
     * A class implementing a templated interface has the interface's template bound to its own
     * (`Iterator[TPurity]<TKey, TValue>` on `Generator`): the collected params then map the
     * interface's template to a standin for the class's, which this resolves to what the class
     * binds it to.
     *
     * @param array<string, array<string, Union>> $class_template_params
     * @psalm-external-mutation-free
     */
    private static function resolveStandins(Union $type, array $class_template_params, Codebase $codebase): Union
    {
        $resolved = [];

        foreach ($type->getAtomicTypes() as $atomic) {
            $resolved[] = $atomic instanceof TTemplateParam
                && isset($class_template_params[$atomic->param_name][$atomic->defining_class])
                ? $class_template_params[$atomic->param_name][$atomic->defining_class]
                : new Union([$atomic]);
        }

        return Type::combineUnionTypeArray($resolved, $codebase);
    }
}
