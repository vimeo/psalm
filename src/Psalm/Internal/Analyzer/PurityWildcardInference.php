<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use PhpParser\Node\Expr;
use Psalm\Codebase;
use Psalm\Internal\Type\PurityWildcardPaths;
use Psalm\Storage\Capabilities;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Atomic\TCapabilities;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_keys;
use function count;
use function is_string;
use function strtolower;

/**
 * What a function or method does through its parameters that the `_` purity
 * ({@see \Psalm\Internal\Type\PurityWildcard}) would charge to its callers instead: calling the
 * closures found in them (`$f()`, `$fs[0]()`, `foreach ($fs as $f) { $f(); }`), iterating them, and
 * calling methods of theirs that depend on a purity argument (`Traversable[_]`, `Doer[_]`).
 *
 * Only while its purity is inferred, and only where the `_` can be written into the type of the
 * parameter as it is written: that purity is left out, and `--alter` adds the `_`
 * ({@see PurityWildcardPaths}). Closures are left alone: the purity inferred for a closure is also
 * the purity of its type, which must keep what calling its parameters does.
 *
 * @internal
 */
final class PurityWildcardInference
{
    /**
     * The defining class of the purity templates standing for the purity arguments of a receiver
     * a parameter's `_` could take, while a call on it is inferred.
     */
    public const MARKER_CLASS = 'purity-wildcard-candidate';

    private const MAX_DEPTH = 10;

    /**
     * The function-like being analysed, the parameter the expression reaches into, and the steps
     * from the parameter's type to the expression's type (`e` into an element, `r` into what a
     * closure returns), if the function-like may give the parameter the `_`.
     *
     * @return array{FunctionLikeAnalyzer, string, string}|null
     */
    public static function getParamPath(StatementsAnalyzer $statements_analyzer, Expr $expr): ?array
    {
        $source = $statements_analyzer->getSource();

        if (!$source instanceof FunctionLikeAnalyzer
            || $source instanceof ClosureAnalyzer
            || !$source->track_mutations
        ) {
            return null;
        }

        $found = self::findParamPath($source, $expr, 0);

        return $found === null ? null : [$source, $found[0], $found[1]];
    }

    /**
     * Records that the parameter would take the `_` at the path, if it can be written there.
     */
    public static function record(FunctionLikeAnalyzer $source, string $param_name, string $path): bool
    {
        if (isset($source->purity_wildcard_candidates[$param_name][$path])) {
            return true;
        }

        // the type Psalm reads (`@psalm-param` over `@param`) must take the `_`; the others are
        // changed if they can be
        $type_string = $source->getParamTypeStrings($param_name)[0] ?? null;
        $paths = [...array_keys($source->purity_wildcard_candidates[$param_name] ?? []), $path];

        if ($type_string === null || PurityWildcardPaths::addToTypeString($type_string, $paths) === null) {
            return false;
        }

        $source->purity_wildcard_candidates[$param_name][$path] = true;

        return true;
    }

    /**
     * The class template params of a call on the receiver, with the receiver's own purity
     * templates that are left to their default replaced by markers, if the receiver is in a
     * parameter that may take the `_`; null otherwise. Whatever depends on them then depends on
     * the markers, which {@see claimMarkers()} resolves.
     *
     * @param array<string, array<string, Union>> $class_template_params
     * @return array<string, array<string, Union>>|null
     */
    public static function markReceiverTemplates(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        Expr $receiver,
        string $receiver_class,
        array $class_template_params,
    ): ?array {
        $found = self::getParamPath($statements_analyzer, $receiver);

        if ($found === null || !$codebase->classlike_storage_provider->has($receiver_class)) {
            return null;
        }

        [$source, $param_name, $steps] = $found;

        $receiver_storage = $codebase->classlike_storage_provider->get($receiver_class);

        // for each purity template of the receiver's class, the class template params standing for it
        $purity_templates = [];
        // what the purity arguments are here, written for those that don't take the `_` when the
        // type has none written
        $current_purities = [];
        // whether the type must have its type arguments written to take the `_`, see
        // PurityWildcardPaths::purityArgument()
        $type_args_required = false;
        $template_index = 0;

        foreach ($receiver_storage->template_types ?? [] as $template_name => $bounds) {
            foreach ($bounds as $bound) {
                if (!Capabilities::isPurityType($bound)) {
                    $type_args_required = $type_args_required
                        || !($receiver_storage->template_covariants[$template_index] ?? false);
                } else {
                    $standing_for = self::getTemplatesStandingFor($receiver_storage, $template_name);
                    $purity_templates[] = $standing_for;
                    $current = null;

                    foreach ($standing_for as [$name, $class]) {
                        $current ??= self::getClassTemplateParam($class_template_params, $name, $class);
                    }

                    $current_purities[] = Capabilities::toString(Capabilities::fromType(
                        $current !== null && Capabilities::isPurityType($current) ? $current : $bound,
                    ));
                }

                break;
            }

            $template_index++;
        }

        $marked = false;

        foreach ($purity_templates as $index => $standing_for) {
            foreach ($standing_for as [$template_name, $class]) {
                foreach ($class_template_params[$template_name] ?? [] as $defining_class => $type) {
                    if (strtolower($defining_class) !== strtolower($class) || !self::isDefaultPurity($type)) {
                        continue;
                    }

                    $marker_name = '_$' . $param_name . '#' . count($source->purity_wildcard_markers);

                    $source->purity_wildcard_markers[$marker_name] = [
                        $param_name,
                        PurityWildcardPaths::purityArgument(
                            $steps,
                            $receiver_storage->name,
                            $index,
                            $current_purities,
                            $type_args_required,
                        ),
                    ];

                    $class_template_params[$template_name][$defining_class] = new Union([
                        new TTemplateParam(
                            $marker_name,
                            new Union([new TCapabilities(Capabilities::ALL)]),
                            self::MARKER_CLASS,
                        ),
                    ]);

                    $marked = true;
                }
            }
        }

        return $marked ? $class_template_params : null;
    }

    /**
     * The template of the class and those of its ancestors it is given as is, also through other
     * ancestors (`@extends Doer[P]<T>`): the class template params of a call on the class are those
     * of the class declaring the method (template name and class).
     *
     * @return non-empty-array<string, array{string, string}>
     * @psalm-mutation-free
     */
    private static function getTemplatesStandingFor(ClassLikeStorage $storage, string $template_name): array
    {
        $templates = [$template_name . '@' . strtolower($storage->name) => [$template_name, $storage->name]];

        do {
            $count = count($templates);

            foreach ($storage->template_extended_params ?? [] as $ancestor => $extended_params) {
                foreach ($extended_params as $ancestor_template_name => $type) {
                    $atomic = $type->isSingle() ? $type->getSingleAtomic() : null;

                    if ($atomic instanceof TTemplateParam
                        && isset($templates[$atomic->param_name . '@' . strtolower($atomic->defining_class)])
                    ) {
                        $templates[$ancestor_template_name . '@' . strtolower($ancestor)] = [
                            $ancestor_template_name,
                            $ancestor,
                        ];
                    }
                }
            }
        } while (count($templates) > $count);

        return $templates;
    }

    /**
     * The purity with the markers of receivers whose `_` can be written kept (and recorded), and
     * the others back to `impure`. A marker kept costs nothing.
     */
    public static function claimMarkers(StatementsAnalyzer $statements_analyzer, Union $purity): Union
    {
        $source = $statements_analyzer->getSource();

        if (!$source instanceof FunctionLikeAnalyzer) {
            return $purity;
        }

        $atomics = [];
        $changed = false;

        foreach ($purity->getAtomicTypes() as $key => $atomic) {
            if ($atomic instanceof TTemplateParam
                && $atomic->defining_class === self::MARKER_CLASS
                && !(isset($source->purity_wildcard_markers[$atomic->param_name])
                    && self::record($source, ...$source->purity_wildcard_markers[$atomic->param_name]))
            ) {
                $atomic = new TCapabilities(Capabilities::ALL);
                $changed = true;
            }

            $atomics[$key] = $atomic;
        }

        return $changed ? $purity->setTypes($atomics) : $purity;
    }

    /**
     * Whether a purity is the default one, `impure`, which the `_` may replace: also when the
     * purity argument is left out of the type, and collected as `mixed`.
     *
     * @psalm-pure
     */
    public static function isDefaultPurity(Union $purity): bool
    {
        foreach ($purity->getAtomicTypes() as $atomic) {
            if (!($atomic instanceof TCapabilities && $atomic->capabilities === Capabilities::ALL)
                && $atomic::class !== TMixed::class
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, array<string, Union>> $class_template_params
     * @psalm-pure
     */
    private static function getClassTemplateParam(
        array $class_template_params,
        string $template_name,
        string $class,
    ): ?Union {
        foreach ($class_template_params[$template_name] ?? [] as $defining_class => $type) {
            if (strtolower($defining_class) === strtolower($class)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @return array{string, string}|null
     */
    private static function findParamPath(FunctionLikeAnalyzer $source, Expr $expr, int $depth): ?array
    {
        if ($depth > self::MAX_DEPTH) {
            return null;
        }

        if ($expr instanceof Expr\Variable && is_string($expr->name)) {
            foreach ($source->getStorage()->params as $param) {
                if ($param->name === $expr->name) {
                    return !$param->by_ref && $param->type !== null && !$source->isParamReassigned($param->name)
                        ? [$param->name, '']
                        : null;
                }
            }

            // the value variable of a foreach loop over a parameter holds one of its elements
            $foreach_source = $source->getForeachSource($expr->name);

            if ($foreach_source === null) {
                return null;
            }

            $found = self::findParamPath($source, $foreach_source, $depth + 1);

            return $found === null ? null : [$found[0], $found[1] . 'e'];
        }

        if ($expr instanceof Expr\ArrayDimFetch) {
            $found = self::findParamPath($source, $expr->var, $depth + 1);

            return $found === null ? null : [$found[0], $found[1] . 'e'];
        }

        if ($expr instanceof Expr\FuncCall && $expr->name instanceof Expr) {
            $found = self::findParamPath($source, $expr->name, $depth + 1);

            return $found === null ? null : [$found[0], $found[1] . 'r'];
        }

        return null;
    }
}
