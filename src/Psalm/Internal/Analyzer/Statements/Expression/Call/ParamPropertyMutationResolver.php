<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use Psalm\Internal\Analyzer\Statements\GlobalAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type\Atomic\TNamedObject;
use WeakMap;

use function explode;
use function is_int;
use function str_starts_with;
use function strtolower;
use function substr;

/**
 * The properties of the objects its parameters hold that a function-like writes, itself or through the calls it
 * passes them to (see FunctionLikeStorage::$param_calls)
 *
 * @internal
 */
final class ParamPropertyMutationResolver
{
    /**
     * Of the file being analyzed ($resolved_file): a function called by name resolves to the declaration that file
     * sees
     *
     * @var ?WeakMap<FunctionLikeStorage, array<string, array<string, bool>>>
     */
    private static ?WeakMap $resolved = null;

    private static ?string $resolved_file = null;

    /**
     * @psalm-capabilities write-globals
     */
    public static function clearCache(): void
    {
        self::$resolved = null;
        self::$resolved_file = null;
    }

    /**
     * By parameter name, the properties written: true when a property itself is assigned, false when only items or
     * properties of what it holds are
     *
     * @return array<string, array<string, bool>>
     */
    public static function getParamPropertyMutations(
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $storage,
    ): array {
        if (!$storage->param_calls) {
            return $storage->param_property_mutations;
        }

        $file_key = $statements_analyzer->getRootFilePath() . "\0" . $statements_analyzer->getFilePath();

        if (self::$resolved === null || self::$resolved_file !== $file_key) {
            /** @var WeakMap<FunctionLikeStorage, array<string, array<string, bool>>> */
            self::$resolved = new WeakMap();
            self::$resolved_file = $file_key;
        }

        /** @var WeakMap<FunctionLikeStorage, true> */
        $in_progress = new WeakMap();

        return self::resolve($statements_analyzer, $storage, self::$resolved, $in_progress);
    }

    /**
     * @param WeakMap<FunctionLikeStorage, array<string, array<string, bool>>> $resolved
     * @param WeakMap<FunctionLikeStorage, true> $in_progress
     * @return array<string, array<string, bool>>
     */
    private static function resolve(
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $storage,
        WeakMap $resolved,
        WeakMap $in_progress,
    ): array {
        $memoized = $resolved[$storage] ?? null;

        if ($memoized !== null) {
            return $memoized;
        }

        // a call back into a function-like being resolved adds nothing it doesn't already write
        if (!$storage->param_calls || isset($in_progress[$storage])) {
            return $storage->param_property_mutations;
        }

        $in_progress[$storage] = true;
        $mutations = $storage->param_property_mutations;

        foreach ($storage->param_calls as [$param_name, $callee_id, $arg]) {
            foreach (self::getCalleeStorages($statements_analyzer, $storage, $callee_id) as $callee_storage) {
                if ($arg === null) {
                    $written = $callee_storage instanceof MethodStorage
                        ? $callee_storage->this_property_mutations ?? []
                        : [];
                } else {
                    $callee_param_name = null;

                    foreach ($callee_storage->params as $offset => $callee_param) {
                        if (is_int($arg) ? $offset === $arg : $callee_param->name === $arg) {
                            $callee_param_name = $callee_param->is_variadic ? null : $callee_param->name;
                            break;
                        }
                    }

                    $written = $callee_param_name === null
                        ? []
                        : self::resolve(
                            $statements_analyzer,
                            $callee_storage,
                            $resolved,
                            $in_progress,
                        )[$callee_param_name] ?? [];
                }

                foreach ($written as $name => $writes_property) {
                    $mutations[$param_name][$name] = $writes_property || ($mutations[$param_name][$name] ?? false);
                }
            }
        }

        unset($in_progress[$storage]);

        return $resolved[$storage] = $mutations;
    }

    /**
     * @param lowercase-string $callee_id
     * @return list<FunctionLikeStorage>
     */
    private static function getCalleeStorages(
        StatementsAnalyzer $statements_analyzer,
        FunctionLikeStorage $storage,
        string $callee_id,
    ): array {
        $codebase = $statements_analyzer->getCodebase();

        if (!str_starts_with($callee_id, '$')) {
            $callee_storage = GlobalAnalyzer::getCalleeStorage($codebase, $statements_analyzer, $callee_id);

            return $callee_storage === null ? [] : [$callee_storage];
        }

        // a method of a parameter: the method of each class the parameter may hold
        $parts = explode('::', substr($callee_id, 1), 2);

        if (!isset($parts[1])) {
            return [];
        }

        [$param_name, $method_name] = $parts;
        $param_type = null;

        foreach ($storage->params as $param) {
            if (strtolower($param->name) === $param_name) {
                $param_type = $param->type;
                break;
            }
        }

        $callee_storages = [];

        foreach ($param_type?->getAtomicTypes() ?? [] as $atomic_type) {
            if (!$atomic_type instanceof TNamedObject
                || !$codebase->classlike_storage_provider->has($atomic_type->value)
            ) {
                continue;
            }

            $declaring_method_id = $codebase->methods->getDeclaringMethodId(
                new MethodIdentifier($atomic_type->value, strtolower($method_name)),
            );

            if ($declaring_method_id !== null) {
                $callee_storages[] = $codebase->methods->getStorage($declaring_method_id);
            }
        }

        return $callee_storages;
    }
}
