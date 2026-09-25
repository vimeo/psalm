<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Storage\Capabilities;
use Psalm\Storage\FunctionLikeStorage;
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
     * The capabilities a purity type (e.g. the purity of a closure being called) requires from
     * the function-like being analysed. Purity templates that function-like inherits its purity
     * from require nothing here; other unresolved templates count as their upper bound.
     *
     * A closure relying on such a template is recorded as depending on it: its own type then
     * carries the template instead of a fixed purity (see {@see FunctionLikeAnalyzer::$used_purity_templates}).
     */
    public static function resolvePurity(Union $purity, StatementsAnalyzer $statements_analyzer): int
    {
        $exempt = self::getEnclosingPurityTemplates($statements_analyzer);

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
     * @param array<string, array<string, Union>> $class_template_params
     * @psalm-external-mutation-free
     */
    public static function getCallCapabilities(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        FunctionLikeStorage $storage,
        int $capabilities,
        ?TemplateResult $template_result,
        array $class_template_params = [],
    ): int {
        if ($storage->purity_from_templates === []) {
            return $capabilities;
        }

        $exempt = self::getEnclosingPurityTemplates($statements_analyzer);

        foreach ($storage->purity_from_templates as $template_name) {
            $bound = self::resolveTemplateType($template_name, $template_result, $class_template_params, $codebase);

            // an unbound template (e.g. the closure was omitted or null) requires nothing
            if ($bound !== null) {
                $capabilities |= self::resolveWithExemptions($bound, $exempt);
            }
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
     * @param array<string, array<string, Union>> $class_template_params
     * @psalm-external-mutation-free
     */
    private static function resolveTemplateType(
        string $template_name,
        ?TemplateResult $template_result,
        array $class_template_params,
        Codebase $codebase,
    ): ?Union {
        if ($template_result !== null && isset($template_result->lower_bounds[$template_name])) {
            $bounds = [];

            foreach ($template_result->lower_bounds[$template_name] as $bound_list) {
                foreach ($bound_list as $bound) {
                    // a template no argument bound is defaulted to its upper bound, without an
                    // argument offset: nothing was passed, so nothing is required
                    if ($bound->arg_offset !== null) {
                        $bounds[] = $bound;
                    }
                }
            }

            if ($bounds !== []) {
                return TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds($bounds, $codebase);
            }
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
     * (`Iterator<TKey, TValue, TPurity>` on `Generator`): the collected params then map the
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
