<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Exception;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Closure as ClosureNode;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallPurityAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\DynamicFunctionStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\FunctionExistenceProvider;
use Psalm\Internal\Provider\FunctionParamsProvider;
use Psalm\Internal\Provider\FunctionReturnTypeProvider;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\Interner;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionStorage;
use Psalm\Storage\Mutations;
use Psalm\StrId;
use Psalm\Type\Atomic\TNamedObject;
use UnexpectedValueException;

use function array_shift;
use function count;
use function end;
use function explode;
use function implode;
use function is_bool;
use function max;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function substr;

/**
 * @internal
 */
final class Functions
{
    /**
     * @var array<int, FunctionStorage> lowercase function id => storage
     */
    private static array $stubbed_functions;

    public FunctionReturnTypeProvider $return_type_provider;

    public FunctionExistenceProvider $existence_provider;

    public FunctionParamsProvider $params_provider;

    public DynamicFunctionStorageProvider $dynamic_storage_provider;

    public function __construct(
        private readonly FileStorageProvider $file_storage_provider,
        private readonly Reflection $reflection,
    ) {
        $this->return_type_provider = new FunctionReturnTypeProvider();
        $this->existence_provider = new FunctionExistenceProvider();
        $this->params_provider = new FunctionParamsProvider();
        $this->dynamic_storage_provider = new DynamicFunctionStorageProvider();

        self::$stubbed_functions = [];
    }

    /**
     * @param int $function_id lowercase function id
     * @psalm-external-mutation-free
     */
    public function getStorage(
        ?StatementsAnalyzer $statements_analyzer,
        int $function_id,
        ?string $root_file_path = null,
        ?string $checked_file_path = null,
    ): FunctionStorage {
        if (isset(self::$stubbed_functions[$function_id])) {
            return self::$stubbed_functions[$function_id];
        }

        $file_storage = null;

        if ($statements_analyzer) {
            $root_file_path = $statements_analyzer->getRootFilePath();
            $checked_file_path = $statements_analyzer->getFilePath();

            $file_storage = $this->file_storage_provider->get($root_file_path);

            $function_analyzers = $statements_analyzer->getFunctionAnalyzers();

            if (isset($function_analyzers[$function_id])) {
                $function_id = $function_analyzers[$function_id]->getFunctionId();

                if (isset($file_storage->functions[$function_id])) {
                    return $file_storage->functions[$function_id];
                }
            }

            // closures can be returned here
            if (isset($file_storage->functions[$function_id])) {
                return $file_storage->functions[$function_id];
            }
        }

        if (!$root_file_path || !$checked_file_path) {
            if ($this->reflection->hasFunction($function_id)) {
                return $this->reflection->getFunctionStorage($function_id);
            }

            throw new UnexpectedValueException(
                'Expecting non-empty $root_file_path and $checked_file_path',
            );
        }

        if ($this->reflection->hasFunction($function_id)) {
            return $this->reflection->getFunctionStorage($function_id);
        }

        if (!isset($file_storage->declaring_function_ids[$function_id])) {
            if ($checked_file_path !== $root_file_path) {
                $file_storage = $this->file_storage_provider->get($checked_file_path);

                if (isset($file_storage->functions[$function_id])) {
                    return $file_storage->functions[$function_id];
                }
            }

            throw new UnexpectedValueException(
                'Expecting ' . Interner::str($function_id) . ' to have storage in ' . $checked_file_path,
            );
        }

        $declaring_file_path = $file_storage->declaring_function_ids[$function_id];

        $declaring_file_storage = $this->file_storage_provider->get($declaring_file_path);

        if (!isset($declaring_file_storage->functions[$function_id])) {
            throw new UnexpectedValueException(
                'Not expecting ' . Interner::str($function_id) . ' to not have storage in ' . $declaring_file_path,
            );
        }

        return $declaring_file_storage->functions[$function_id];
    }

    /**
     * @param int $function_id function id (any casing)
     * @psalm-external-mutation-free
     */
    public function addGlobalFunction(int $function_id, FunctionStorage $storage): void
    {
        self::$stubbed_functions[Interner::lower($function_id)] = $storage;
    }

    /**
     * @param array<int, FunctionStorage> $stubs lowercase function id => storage
     * @psalm-external-mutation-free
     */
    public function addGlobalFunctions(array $stubs): void
    {
        self::$stubbed_functions += $stubs;
    }

    /**
     * @param int $function_id function id (any casing)
     * @psalm-external-mutation-free
     */
    public function hasStubbedFunction(int $function_id): bool
    {
        return isset(self::$stubbed_functions[Interner::lower($function_id)]);
    }

    /**
     * @return array<int, FunctionStorage> lowercase function id => storage
     * @psalm-external-mutation-free
     */
    public function getAllStubbedFunctions(): array
    {
        return self::$stubbed_functions;
    }

    /**
     * @param int $function_id lowercase function id
     */
    public function functionExists(
        StatementsAnalyzer $statements_analyzer,
        int $function_id,
    ): bool {
        if ($this->existence_provider->has($function_id)) {
            $function_exists = $this->existence_provider->doesFunctionExist($statements_analyzer, $function_id);

            if ($function_exists !== null) {
                return $function_exists;
            }
        }

        $file_storage = $this->file_storage_provider->get($statements_analyzer->getRootFilePath());

        if (isset($file_storage->declaring_function_ids[$function_id])) {
            return true;
        }

        if ($this->reflection->hasFunction($function_id)) {
            return true;
        }

        if (isset(self::$stubbed_functions[$function_id])) {
            return true;
        }

        if (isset($statements_analyzer->getFunctionAnalyzers()[$function_id])) {
            return true;
        }

        $predefined_functions = $statements_analyzer->getCodebase()->config->getPredefinedFunctions();

        if (isset($predefined_functions[$function_id])) {
            if ($this->reflection->registerFunction($function_id) === false) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Resolves a function name as written in the source to the (cased) fully qualified function name id
     *
     * @param  non-empty-string $function_name
     */
    public function getFullyQualifiedFunctionNameFromString(string $function_name, StatementsSource $source): int
    {
        if ($function_name[0] === '\\') {
            $function_name = substr($function_name, 1);

            if ($function_name === '') {
                throw new UnexpectedValueException('Malformed function name');
            }

            return Interner::intern($function_name);
        }

        $aliases = $source->getAliases();

        $imported_function_namespaces = $aliases->functions;
        $imported_namespaces = $aliases->uses;

        if (str_contains($function_name, '\\')) {
            $function_name_parts = explode('\\', $function_name);
            $first_namespace = array_shift($function_name_parts);
            $first_namespace_lcase = Interner::internLower($first_namespace);

            if (isset($imported_namespaces[$first_namespace_lcase])) {
                return Interner::intern(
                    Interner::str($imported_namespaces[$first_namespace_lcase])
                        . '\\' . implode('\\', $function_name_parts),
                );
            }

            if (isset($imported_function_namespaces[$first_namespace_lcase])) {
                return Interner::intern(
                    Interner::str($imported_function_namespaces[$first_namespace_lcase])
                        . '\\' . implode('\\', $function_name_parts),
                );
            }
        } else {
            $function_name_lcase = Interner::internLower($function_name);

            if (isset($imported_function_namespaces[$function_name_lcase])) {
                return $imported_function_namespaces[$function_name_lcase];
            }
        }

        $namespace = $source->getNamespace();

        return Interner::intern(($namespace !== null ? Interner::str($namespace) . '\\' : '') . $function_name);
    }

    /**
     * @return array<int, FunctionStorage> lowercase function id => storage
     */
    public function getMatchingFunctionNames(
        string $stub,
        int $offset,
        string $file_path,
        Codebase $codebase,
    ): array {
        if ($stub[0] === '*') {
            $stub = substr($stub, 1);
        }

        $fully_qualified = false;

        if ($stub[0] === '\\') {
            $fully_qualified = true;
            $stub = substr($stub, 1);
            $stub_namespace = '';
        } else {
            // functions can reference either the current namespace or root-namespaced
            // equivalents. We therefore want to make both candidates.
            [$stub_namespace, $stub] = explode('-', $stub);
        }

        /** @var array<int, FunctionStorage> */
        $matching_functions = [];

        $file_storage = $this->file_storage_provider->get($file_path);

        $current_namespace_aliases = null;
        foreach ($file_storage->namespace_aliases as $namespace_start => $namespace_aliases) {
            if ($namespace_start < $offset) {
                $current_namespace_aliases = $namespace_aliases;
                break;
            }
        }

        // We will search all functions for several patterns. This will
        // be for all used namespaces, the global namespace and matched
        // used functions.
        $match_function_patterns = [
            $stub . '*',
        ];

        if ($stub_namespace) {
            $match_function_patterns[] = $stub_namespace . '\\' . $stub . '*';
        }

        if ($current_namespace_aliases) {
            foreach ($current_namespace_aliases->functions as $alias_name => $function_name) {
                if (str_starts_with(Interner::str($alias_name), $stub)) {
                    $match_function_patterns[] = Interner::str($function_name);
                }
            }

            if (!$fully_qualified) {
                foreach ($current_namespace_aliases->uses as $namespace_name) {
                    $match_function_patterns[] = Interner::str($namespace_name) . '\\' . $stub . '*';
                }
            }
        }

        $function_map = $file_storage->functions
            + $this->getAllStubbedFunctions()
            + $this->reflection->getFunctions()
            + $codebase->config->getPredefinedFunctions();

        foreach ($function_map as $function_id => $function) {
            $function_name = Interner::str($function_id);

            foreach ($match_function_patterns as $pattern) {
                $pattern_lc = strtolower($pattern);

                if (str_ends_with($pattern, '*')) {
                    if (!str_starts_with($function_name, rtrim($pattern_lc, '*'))) {
                        continue;
                    }
                } elseif ($function_name !== $pattern) {
                    continue;
                }
                if (is_bool($function)) {
                    if ($this->reflection->registerFunction($function_id) === false) {
                        continue;
                    }
                    $function = $this->reflection->getFunctionStorage($function_id);
                }

                if ($function->cased_name !== null) {
                    $cased_name_parts = explode('\\', Interner::str($function->cased_name));
                    $pattern_parts = explode('\\', $pattern);

                    if (end($cased_name_parts)[0] !== end($pattern_parts)[0]) {
                        continue;
                    }
                }

                $matching_functions[$function_id] = $function;
            }
        }

        return $matching_functions;
    }

    /**
     * @param int $function_id lowercase function id
     * @psalm-external-mutation-free
     */
    public static function isVariadic(Codebase $codebase, int $function_id, string $file_path): bool
    {
        $file_storage = $codebase->file_storage_provider->get($file_path);

        if (!isset($file_storage->declaring_function_ids[$function_id])) {
            return false;
        }

        $declaring_file_path = $file_storage->declaring_function_ids[$function_id];

        $file_storage = $declaring_file_path === $file_path
            ? $file_storage
            : $codebase->file_storage_provider->get($declaring_file_path);

        return isset($file_storage->functions[$function_id]) && $file_storage->functions[$function_id]->variadic;
    }

    /**
     * @param int $function_id lowercase function id
     * @param ?list<Arg> $args
     * @return Mutations::LEVEL_*
     */
    public function getCallMapFunctionMutations(
        ?StatementsAnalyzer $statements_analyzer,
        ?Context $context,
        Codebase $codebase,
        int $function_id,
        ?array $args,
        bool &$must_use = true,
    ): int {
        if (ImpureFunctionsList::isImpure($function_id)) {
            return Mutations::LEVEL_ALL;
        }

        $type_provider = $statements_analyzer?->node_data;
        if ($function_id === StrId::serialize && isset($args[0]) && $type_provider) {
            $serialize_type = $type_provider->getType($args[0]->value);

            if ($serialize_type && $serialize_type->canContainObjectType($codebase)) {
                return Mutations::LEVEL_ALL;
            }
        }

        $function_id_str = Interner::str($function_id);

        if (str_starts_with($function_id_str, 'image')) {
            return Mutations::LEVEL_ALL;
        }

        if (str_starts_with($function_id_str, 'readline')) {
            return Mutations::LEVEL_ALL;
        }

        if (($function_id === StrId::var_export || $function_id === StrId::print_r) && !isset($args[1])) {
            return Mutations::LEVEL_ALL;
        }

        if ($function_id === StrId::assert) {
            $must_use = false;
            return Mutations::LEVEL_NONE;
        }

        if ($function_id === StrId::func_num_args || $function_id === StrId::func_get_args) {
            return Mutations::LEVEL_NONE;
        }

        if ((
                $function_id === StrId::count
                || $function_id === StrId::sizeof
            )
            && isset($args[0]) && $type_provider
            && $statements_analyzer
            && $context
        ) {
            $var = $args[0]->value;
            $count_type = $type_provider->getType($var);

            if ($count_type) {
                $mutations = Mutations::LEVEL_NONE;
                foreach ($count_type->getAtomicTypes() as $atomic_count_type) {
                    if ($atomic_count_type instanceof TNamedObject) {
                        $count_method_id = new MethodIdentifier(
                            $atomic_count_type->value,
                            StrId::count,
                        );

                        try {
                            $storage = $codebase->methods->getStorage($count_method_id);
                        } catch (Exception) {
                            continue;
                        }
                        $mutations = max($mutations, MethodCallPurityAnalyzer::getMethodAllowedMutations(
                            $statements_analyzer,
                            $var,
                            $count_method_id,
                            $storage,
                            $context,
                        ));

                        $statements_analyzer->signalMutationOnlyInferred(
                            $storage->allowed_mutations,
                            $storage,
                            MethodCallPurityAnalyzer::receiverAllowsInternalMutations(
                                $statements_analyzer,
                                $var,
                                $count_method_id,
                                $context,
                            ),
                        );
                    }
                }
                return $mutations;
            }
        }

        $function_callable = InternalCallMapHandler::getCallableFromCallMapById(
            $codebase,
            $function_id,
            $args ?: [],
            null,
        );

        if (!isset($function_callable->params)
            || ($args !== null && count($args) === 0)
            || ($function_callable->return_type && $function_callable->return_type->isVoid())
        ) {
            return Mutations::LEVEL_ALL;
        }

        $must_use = $function_id !== StrId::array_map
            || (isset($args[0]) && !$args[0]->value instanceof ClosureNode);

        $mutations = Mutations::LEVEL_NONE;
        foreach ($function_callable->params as $i => $param) {
            if ($type_provider && $param->type && $param->type->hasCallableType() && isset($args[$i])) {
                $arg_type = $type_provider->getType($args[$i]->value);

                if ($arg_type) {
                    foreach ($arg_type->getAtomicTypes() as $possible_callable) {
                        $possible_callable = CallableTypeComparator::getCallableFromAtomic(
                            $codebase,
                            $possible_callable,
                        );

                        if ($possible_callable && $possible_callable->allowed_mutations !== Mutations::LEVEL_NONE) {
                            $mutations = max($mutations, $possible_callable->allowed_mutations);
                        }
                    }
                }
            }

            if ($param->by_ref && isset($args[$i])) {
                $must_use = false;
            }
        }

        return $mutations;
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function clearCache(): void
    {
        self::$stubbed_functions = [];
    }
}
