<?php

namespace Psalm\Internal\Codebase;

use Exception;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Closure as ClosureNode;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\DynamicFunctionStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\FunctionExistenceProvider;
use Psalm\Internal\Provider\FunctionParamsProvider;
use Psalm\Internal\Provider\FunctionReturnTypeProvider;
use Psalm\Internal\Type\Comparator\CallableTypeComparator;
use Psalm\NodeTypeProvider;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionStorage;
use Psalm\Type\Atomic\TNamedObject;
use UnexpectedValueException;

use function array_shift;
use function count;
use function end;
use function explode;
use function implode;
use function in_array;
use function is_bool;
use function rtrim;
use function strpos;
use function strtolower;
use function substr;

/**
 * @internal
 */
class Functions
{
    /**
     * @var FileStorageProvider
     */
    private $file_storage_provider;

    /**
     * @var array<lowercase-string, FunctionStorage>
     */
    private static $stubbed_functions;

    /** @var FunctionReturnTypeProvider */
    public $return_type_provider;

    /** @var FunctionExistenceProvider */
    public $existence_provider;

    /** @var FunctionParamsProvider */
    public $params_provider;

    /** @var DynamicFunctionStorageProvider */
    public $dynamic_storage_provider;

    /**
     * @var Reflection
     */
    private $reflection;

    public function __construct(FileStorageProvider $storage_provider, Reflection $reflection)
    {
        $this->file_storage_provider = $storage_provider;
        $this->reflection = $reflection;
        $this->return_type_provider = new FunctionReturnTypeProvider();
        $this->existence_provider = new FunctionExistenceProvider();
        $this->params_provider = new FunctionParamsProvider();
        $this->dynamic_storage_provider = new DynamicFunctionStorageProvider();

        self::$stubbed_functions = [];
    }

    /**
     * @param non-empty-lowercase-string $function_id
     */
    public function getStorage(
        ?StatementsAnalyzer $statements_analyzer,
        string $function_id,
        ?string $root_file_path = null,
        ?string $checked_file_path = null
    ): FunctionStorage {
        if ($function_id[0] === '\\') {
            $function_id = substr($function_id, 1);
        }

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
                'Expecting non-empty $root_file_path and $checked_file_path'
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
                'Expecting ' . $function_id . ' to have storage in ' . $checked_file_path
            );
        }

        $declaring_file_path = $file_storage->declaring_function_ids[$function_id];

        $declaring_file_storage = $this->file_storage_provider->get($declaring_file_path);

        if (!isset($declaring_file_storage->functions[$function_id])) {
            throw new UnexpectedValueException(
                'Not expecting ' . $function_id . ' to not have storage in ' . $declaring_file_path
            );
        }

        return $declaring_file_storage->functions[$function_id];
    }

    public function addGlobalFunction(string $function_id, FunctionStorage $storage): void
    {
        self::$stubbed_functions[strtolower($function_id)] = $storage;
    }

    public function hasStubbedFunction(string $function_id): bool
    {
        return isset(self::$stubbed_functions[strtolower($function_id)]);
    }

    /**
     * @return array<string, FunctionStorage>
     */
    public function getAllStubbedFunctions(): array
    {
        return self::$stubbed_functions;
    }

    /**
     * @param lowercase-string $function_id
     */
    public function functionExists(
        StatementsAnalyzer $statements_analyzer,
        string $function_id
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
            /** @psalm-suppress ArgumentTypeCoercion */
            if ($this->reflection->registerFunction($function_id) === false) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param  non-empty-string         $function_name
     *
     * @return non-empty-string
     */
    public function getFullyQualifiedFunctionNameFromString(string $function_name, StatementsSource $source): string
    {
        if ($function_name[0] === '\\') {
            $function_name = substr($function_name, 1);

            if ($function_name === '') {
                throw new UnexpectedValueException('Malformed function name');
            }

            return $function_name;
        }

        $function_name_lcase = strtolower($function_name);

        $aliases = $source->getAliases();

        $imported_function_namespaces = $aliases->functions;
        $imported_namespaces = $aliases->uses;

        if (strpos($function_name, '\\') !== false) {
            $function_name_parts = explode('\\', $function_name);
            $first_namespace = array_shift($function_name_parts);
            $first_namespace_lcase = strtolower($first_namespace);

            if (isset($imported_namespaces[$first_namespace_lcase])) {
                return $imported_namespaces[$first_namespace_lcase] . '\\' . implode('\\', $function_name_parts);
            }

            if (isset($imported_function_namespaces[$first_namespace_lcase])) {
                return $imported_function_namespaces[$first_namespace_lcase] . '\\' .
                    implode('\\', $function_name_parts);
            }
        } elseif (isset($imported_function_namespaces[$function_name_lcase])) {
            return $imported_function_namespaces[$function_name_lcase];
        }

        $namespace = $source->getNamespace();

        return ($namespace ? $namespace . '\\' : '') . $function_name;
    }

    /**
     * @return array<lowercase-string,FunctionStorage>
     */
    public function getMatchingFunctionNames(
        string $stub,
        int $offset,
        string $file_path,
        Codebase $codebase
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

        /** @var array<lowercase-string, FunctionStorage> */
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
                if (strpos($alias_name, $stub) === 0) {
                    try {
                        $match_function_patterns[] = $function_name;
                    } catch (Exception $e) {
                    }
                }
            }

            if (!$fully_qualified) {
                foreach ($current_namespace_aliases->uses as $namespace_name) {
                    $match_function_patterns[] = $namespace_name . '\\' . $stub . '*';
                }
            }
        }

        $function_map = $file_storage->functions
            + $this->getAllStubbedFunctions()
            + $this->reflection->getFunctions()
            + $codebase->config->getPredefinedFunctions();

        foreach ($function_map as $function_name => $function) {
            foreach ($match_function_patterns as $pattern) {
                $pattern_lc = strtolower($pattern);

                if (substr($pattern, -1, 1) === '*') {
                    if (strpos($function_name, rtrim($pattern_lc, '*')) !== 0) {
                        continue;
                    }
                } elseif ($function_name !== $pattern) {
                    continue;
                }
                if (is_bool($function)) {
                    /** @var callable-string $function_name */
                    if ($this->reflection->registerFunction($function_name) === false) {
                        continue;
                    }
                    $function = $this->reflection->getFunctionStorage($function_name);
                }

                if ($function->cased_name) {
                    $cased_name_parts = explode('\\', $function->cased_name);
                    $pattern_parts = explode('\\', $pattern);

                    if (end($cased_name_parts)[0] !== end($pattern_parts)[0]) {
                        continue;
                    }
                }

                /** @var lowercase-string $function_name */
                $matching_functions[$function_name] = $function;
            }
        }

        return $matching_functions;
    }

    public static function isVariadic(Codebase $codebase, string $function_id, string $file_path): bool
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
     * @param ?list<Arg> $args
     */
    public function isCallMapFunctionPure(
        Codebase $codebase,
        ?NodeTypeProvider $type_provider,
        string $function_id,
        ?array $args,
        bool &$must_use = true
    ): bool {
        $impure_functions = [
            // file io
            'chdir', 'chgrp', 'chmod', 'chown', 'chroot', 'copy', 'file_get_contents', 'file_put_contents',
            'opendir', 'readdir', 'closedir', 'rewinddir', 'scandir',
            'fopen', 'fread', 'fwrite', 'fclose', 'touch', 'fpassthru', 'fputs', 'fscanf', 'fseek', 'flock',
            'ftruncate', 'fprintf', 'symlink', 'mkdir', 'unlink', 'rename', 'rmdir', 'popen', 'pclose',
            'fgetcsv', 'fputcsv', 'umask', 'finfo_open', 'finfo_close', 'finfo_file',
            'stream_set_timeout', 'fgets', 'fflush', 'move_uploaded_file', 'file_exists', 'realpath', 'glob',
            'is_readable', 'is_dir', 'is_file',

            // stream/socket io
            'stream_context_set_option', 'socket_write', 'stream_set_blocking', 'socket_close',
            'socket_set_option', 'stream_set_write_buffer', 'stream_socket_enable_crypto', 'stream_copy_to_stream',
            'stream_wrapper_register',

            // meta calls
            'call_user_func', 'call_user_func_array', 'define', 'create_function',

            // http
            'header', 'header_remove', 'http_response_code', 'setcookie',

            // output buffer
            'ob_start', 'ob_end_clean', 'ob_get_clean', 'readfile', 'printf', 'var_dump', 'phpinfo',
            'ob_implicit_flush', 'vprintf',

            // mcrypt
            'mcrypt_generic_init', 'mcrypt_generic_deinit', 'mcrypt_module_close',

            // internal optimisation
            'opcache_compile_file', 'clearstatcache',

            // process-related
            'pcntl_signal', 'pcntl_alarm', 'posix_kill', 'cli_set_process_title', 'pcntl_async_signals', 'proc_close',
            'proc_nice', 'proc_open', 'proc_terminate',

            // curl
            'curl_setopt', 'curl_close', 'curl_multi_add_handle', 'curl_multi_remove_handle',
            'curl_multi_select', 'curl_multi_close', 'curl_setopt_array',

            // apc, apcu
            'apc_store', 'apc_delete', 'apc_clear_cache', 'apc_add', 'apc_inc', 'apc_dec', 'apc_cas',
            'apcu_store', 'apcu_delete', 'apcu_clear_cache', 'apcu_add', 'apcu_inc', 'apcu_dec', 'apcu_cas',

            // gz
            'gzwrite', 'gzrewind', 'gzseek', 'gzclose',

            // newrelic
            'newrelic_start_transaction', 'newrelic_name_transaction', 'newrelic_add_custom_parameter',
            'newrelic_add_custom_tracer', 'newrelic_background_job', 'newrelic_end_transaction',
            'newrelic_set_appname',

            // execution
            'shell_exec', 'exec', 'system', 'passthru', 'pcntl_exec',

            // well-known functions
            'libxml_use_internal_errors', 'libxml_disable_entity_loader', 'curl_exec',
            'mt_srand', 'openssl_pkcs7_sign', 'openssl_sign',
            'mt_rand', 'rand', 'random_int', 'random_bytes',
            'wincache_ucache_delete', 'wincache_ucache_set', 'wincache_ucache_inc',
            'class_alias',
            'class_exists', // impure by virtue of triggering autoloader
            'enum_exists', // impure by virtue of triggering autoloader

            // php environment
            'ini_set', 'sleep', 'usleep', 'register_shutdown_function',
            'error_reporting', 'register_tick_function', 'unregister_tick_function',
            'set_error_handler', 'user_error', 'trigger_error', 'restore_error_handler',
            'date_default_timezone_set', 'assert_options', 'setlocale',
            'set_exception_handler', 'set_time_limit', 'putenv', 'spl_autoload_register',
            'spl_autoload_unregister', 'microtime', 'array_rand', 'set_include_path',

            // logging
            'openlog', 'syslog', 'error_log', 'define_syslog_variables',

            // session
            'session_id', 'session_decode', 'session_name', 'session_set_cookie_params',
            'session_set_save_handler', 'session_regenerate_id', 'mb_internal_encoding',
            'session_start', 'session_cache_limiter',

            // ldap
            'ldap_set_option',

            // iterators
            'rewind', 'iterator_apply', 'iterator_to_array',

            // mysqli
            'mysqli_select_db', 'mysqli_dump_debug_info', 'mysqli_kill', 'mysqli_multi_query',
            'mysqli_next_result', 'mysqli_options', 'mysqli_ping', 'mysqli_query', 'mysqli_report',
            'mysqli_rollback', 'mysqli_savepoint', 'mysqli_set_charset', 'mysqli_ssl_set', 'mysqli_close',

            // script execution
            'ignore_user_abort',

            // ftp
            'ftp_close', 'ftp_pasv',

            // bcmath
            'bcscale',

            // json
            'json_last_error',

            // opcache
            'opcache_compile_file', 'opcache_get_configuration', 'opcache_get_status',
            'opcache_invalidate', 'opcache_is_script_cached', 'opcache_reset',

            //gettext
            'bindtextdomain',
        ];

        if (in_array(strtolower($function_id), $impure_functions, true)) {
            return false;
        }

        if (strpos($function_id, 'image') === 0) {
            return false;
        }

        if (strpos($function_id, 'readline') === 0) {
            return false;
        }

        if (($function_id === 'var_export' || $function_id === 'print_r') && !isset($args[1])) {
            return false;
        }

        if ($function_id === 'assert') {
            $must_use = false;
            return true;
        }

        if ($function_id === 'func_num_args' || $function_id === 'func_get_args') {
            return true;
        }

        if ($function_id === 'count' && isset($args[0]) && $type_provider) {
            $count_type = $type_provider->getType($args[0]->value);

            if ($count_type) {
                foreach ($count_type->getAtomicTypes() as $atomic_count_type) {
                    if ($atomic_count_type instanceof TNamedObject) {
                        $count_method_id = new MethodIdentifier(
                            $atomic_count_type->value,
                            'count'
                        );

                        try {
                            return $codebase->methods->getStorage($count_method_id)->mutation_free;
                        } catch (Exception $e) {
                            // do nothing
                        }
                    }
                }
            }
        }

        $function_callable = InternalCallMapHandler::getCallableFromCallMapById(
            $codebase,
            $function_id,
            $args ?: [],
            null
        );

        if (!$function_callable->params
            || ($args !== null && count($args) === 0)
            || ($function_callable->return_type && $function_callable->return_type->isVoid())
        ) {
            return false;
        }

        $must_use = $function_id !== 'array_map'
            || (isset($args[0]) && !$args[0]->value instanceof ClosureNode);

        foreach ($function_callable->params as $i => $param) {
            if ($type_provider && $param->type && $param->type->hasCallableType() && isset($args[$i])) {
                $arg_type = $type_provider->getType($args[$i]->value);

                if ($arg_type) {
                    foreach ($arg_type->getAtomicTypes() as $possible_callable) {
                        $possible_callable = CallableTypeComparator::getCallableFromAtomic(
                            $codebase,
                            $possible_callable
                        );

                        if ($possible_callable && !$possible_callable->is_pure) {
                            return false;
                        }
                    }
                }
            }

            if ($param->by_ref && isset($args[$i])) {
                $must_use = false;
            }
        }

        return true;
    }

    public static function clearCache(): void
    {
        self::$stubbed_functions = [];
    }
}
