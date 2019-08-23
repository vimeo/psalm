<?php
namespace Psalm\Internal\Codebase;

use function array_shift;
use function explode;
use function implode;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Provider\FunctionExistenceProvider;
use Psalm\Internal\Provider\FunctionParamsProvider;
use Psalm\Internal\Provider\FunctionReturnTypeProvider;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeStorage;
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
     * @var array<string, FunctionLikeStorage>
     */
    private static $stubbed_functions;

    /** @var FunctionReturnTypeProvider */
    public $return_type_provider;

    /** @var FunctionExistenceProvider */
    public $existence_provider;

    /** @var FunctionParamsProvider */
    public $params_provider;

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

        self::$stubbed_functions = [];
    }

    public function getStorage(
        ?StatementsAnalyzer $statements_analyzer,
        string $function_id,
        ?string $root_file_path = null,
        ?string $checked_file_path = null
    ) : FunctionLikeStorage {
        if (isset(self::$stubbed_functions[strtolower($function_id)])) {
            return self::$stubbed_functions[strtolower($function_id)];
        }

        if ($this->reflection->hasFunction($function_id)) {
            return $this->reflection->getFunctionStorage($function_id);
        }

        if ($statements_analyzer) {
            $root_file_path = $statements_analyzer->getRootFilePath();
            $checked_file_path = $statements_analyzer->getFilePath();
        } elseif (!$root_file_path || !$checked_file_path) {
            throw new \UnexpectedValueException(
                'Expecting non-empty $root_file_path and $checked_file_path'
            );
        }

        $file_storage = $this->file_storage_provider->get($root_file_path);

        if ($statements_analyzer) {
            $function_analyzers = $statements_analyzer->getFunctionAnalyzers();

            if (isset($function_analyzers[$function_id])) {
                $function_id = $function_analyzers[$function_id]->getMethodId();

                if (isset($file_storage->functions[$function_id])) {
                    return $file_storage->functions[$function_id];
                }
            }

            // closures can be returned here
            if (isset($file_storage->functions[$function_id])) {
                return $file_storage->functions[$function_id];
            }
        }

        if (!isset($file_storage->declaring_function_ids[$function_id])) {
            if ($checked_file_path !== $root_file_path) {
                $file_storage = $this->file_storage_provider->get($checked_file_path);

                if (isset($file_storage->functions[$function_id])) {
                    return $file_storage->functions[$function_id];
                }
            }

            throw new \UnexpectedValueException(
                'Expecting ' . $function_id . ' to have storage in ' . $checked_file_path
            );
        }

        $declaring_file_path = $file_storage->declaring_function_ids[$function_id];

        $declaring_file_storage = $this->file_storage_provider->get($declaring_file_path);

        if (!isset($declaring_file_storage->functions[$function_id])) {
            throw new \UnexpectedValueException(
                'Not expecting ' . $function_id . ' to not have storage in ' . $declaring_file_path
            );
        }

        return $declaring_file_storage->functions[$function_id];
    }

    /**
     * @param string $function_id
     * @param FunctionLikeStorage $storage
     *
     * @return void
     */
    public function addGlobalFunction($function_id, FunctionLikeStorage $storage)
    {
        self::$stubbed_functions[strtolower($function_id)] = $storage;
    }

    /**
     * @param  string  $function_id
     *
     * @return bool
     */
    public function hasStubbedFunction($function_id)
    {
        return isset(self::$stubbed_functions[strtolower($function_id)]);
    }

    /**
     * @return bool
     */
    public function functionExists(
        StatementsAnalyzer $statements_analyzer,
        string $function_id
    ) {
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

        if (isset(self::$stubbed_functions[strtolower($function_id)])) {
            return true;
        }

        if (isset($statements_analyzer->getFunctionAnalyzers()[$function_id])) {
            return true;
        }

        $predefined_functions = $statements_analyzer->getCodebase()->config->getPredefinedFunctions();

        if (isset($predefined_functions[$function_id])) {
            /** @psalm-suppress TypeCoercion */
            if ($this->reflection->registerFunction($function_id) === false) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param  string                   $function_name
     * @param  StatementsSource         $source
     *
     * @return string
     */
    public function getFullyQualifiedFunctionNameFromString($function_name, StatementsSource $source)
    {
        if (empty($function_name)) {
            throw new \InvalidArgumentException('$function_name cannot be empty');
        }

        if ($function_name[0] === '\\') {
            return substr($function_name, 1);
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
     * @param  string $function_id
     * @param  string $file_path
     *
     * @return bool
     */
    public static function isVariadic(Codebase $codebase, $function_id, $file_path)
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
     * @param array<int, \PhpParser\Node\Arg> $args
     */
    public function isCallMapFunctionPure(Codebase $codebase, string $function_id, array $args) : bool
    {
        $impure_functions = [
            // file io
            'chdir', 'chgrp', 'chmod', 'chown', 'chroot', 'closedir', 'copy', 'file_put_contents',
            'fopen', 'fread', 'fwrite', 'fclose', 'touch', 'fpassthru', 'fputs', 'fscanf', 'fseek',
            'ftruncate', 'fprintf', 'symlink', 'mkdir', 'unlink', 'rename', 'rmdir', 'popen', 'pclose',
            'fputcsv', 'umask', 'finfo_close',

            // stream/socket io
            'stream_context_set_option', 'socket_write', 'stream_set_blocking', 'socket_close',
            'socket_set_option', 'stream_set_write_buffer',

            // meta calls
            'call_user_func', 'call_user_func_array', 'define', 'create_function',

            // http
            'header', 'header_remove', 'http_response_code', 'setcookie',

            // output buffer
            'ob_start', 'ob_end_clean', 'readfile', 'var_dump', 'printf', 'print_r', 'phpinfo',

            // internal optimisation
            'opcache_compile_file', 'clearstatcache',

            // process-related
            'pcntl_signal', 'posix_kill', 'cli_set_process_title', 'pcntl_async_signals', 'proc_close',

            // curl
            'curl_setopt', 'curl_close', 'curl_multi_add_handle', 'curl_multi_remove_handle',
            'curl_multi_select', 'curl_multi_close', 'curl_setopt_array',

            // apc, apcu
            'apc_store', 'apc_delete', 'apc_clear_cache', 'apc_add', 'apc_inc', 'apc_dec', 'apc_cas',
            'apcu_store', 'apcu_delete', 'apcu_clear_cache', 'apcu_add', 'apcu_inc', 'apcu_dec', 'apcu_cas',

            // newrelic
            'newrelic_start_transaction', 'newrelic_name_transaction', 'newrelic_add_custom_parameter',
            'newrelic_add_custom_tracer', 'newrelic_background_job', 'newrelic_end_transaction',
            'newrelic_set_appname',

            // execution
            'shell_exec', 'exec', 'system', 'passthru', 'pcntl_exec',

            // well-known functions
            'libxml_use_internal_errors', 'array_map', 'curl_exec',
            'mt_srand', 'openssl_pkcs7_sign', 'mysqli_select_db', 'preg_replace_callback',

            // php environment
            'ini_set', 'sleep', 'usleep', 'register_shutdown_function',
            'error_reporting', 'register_tick_function', 'unregister_tick_function',
            'set_error_handler', 'user_error', 'trigger_error', 'restore_error_handler',
            'date_default_timezone_set',  'assert', 'assert_options', 'setlocale',
            'set_exception_handler', 'set_time_limit', 'putenv', 'spl_autoload_register',

            // logging
            'openlog', 'syslog', 'error_log', 'define_syslog_variables',

            // session
            'session_id', 'session_name', 'session_set_cookie_params',

            // ldap
            'ldap_set_option',

            // iterators
            'rewind',
        ];

        if (\in_array(strtolower($function_id), $impure_functions, true)) {
            return false;
        }

        if (strpos($function_id, 'image') === 0) {
            return false;
        }

        if ($function_id === 'var_export' && !isset($args[1])) {
            return false;
        }

        $function_callable = \Psalm\Internal\Codebase\CallMap::getCallableFromCallMapById(
            $codebase,
            $function_id,
            $args
        );

        if (!$function_callable->params || !$args) {
            return false;
        }

        foreach ($function_callable->params as $i => $param) {
            if ($param->by_ref && isset($args[$i])) {
                return false;
            }
        }

        return true;
    }

    public static function clearCache() : void
    {
        self::$stubbed_functions = [];
    }
}
