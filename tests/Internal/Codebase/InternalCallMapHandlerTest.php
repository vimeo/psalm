<?php

namespace Psalm\Tests\Internal\Codebase;

use InvalidArgumentException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\SkippedTestError;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use Psalm\Type;
use ReflectionFunction;
use ReflectionParameter;
use ReflectionType;

use function class_exists;
use function count;
use function explode;
use function function_exists;
use function in_array;
use function is_array;
use function is_int;
use function json_encode;
use function preg_match;
use function print_r;
use function strcmp;
use function strncmp;
use function strpos;
use function substr;

use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;

class InternalCallMapHandlerTest extends TestCase
{
    /**
     * Specify a function name as value, or a function name as key and
     * an array containing the PHP versions in which to ignore this function as values.
     * @var array<int|string, string|list<string>>
     */
    private static $ignoredFunctions = [
        'apcu_entry',
        'array_multisort',
        'bcdiv',
        'bcmod',
        'bcpowmod',
        'bzdecompress',
        'crypt',
        'date_isodate_set',
        'debug_zval_dump',
        'deflate_add',
        'dns_get_mx',
        'easter_date',
        'enchant_broker_describe',
        'enchant_broker_dict_exists',
        'enchant_broker_free',
        'enchant_broker_free_dict',
        'enchant_broker_get_dict_path',
        'enchant_broker_get_error',
        'enchant_broker_list_dicts',
        'enchant_broker_request_dict',
        'enchant_broker_request_pwl_dict',
        'enchant_broker_set_dict_path',
        'enchant_broker_set_ordering',
        'enchant_dict_add_to_personal',
        'enchant_dict_add_to_session',
        'enchant_dict_check',
        'enchant_dict_describe',
        'enchant_dict_get_error',
        'enchant_dict_is_in_session',
        'enchant_dict_quick_check',
        'enchant_dict_store_replacement',
        'enchant_dict_suggest',
        'enum_exists',
        'extract',
        'get_class_methods',
        'get_headers',
        'get_parent_class',
        'gmp_clrbit',
        'gmp_div',
        'gmp_setbit',
        'gnupg_adddecryptkey',
        'gnupg_addencryptkey',
        'gnupg_addsignkey',
        'gnupg_cleardecryptkeys',
        'gnupg_clearencryptkeys',
        'gnupg_clearsignkeys',
        'gnupg_decrypt',
        'gnupg_decryptverify',
        'gnupg_encrypt',
        'gnupg_encryptsign',
        'gnupg_export',
        'gnupg_geterror',
        'gnupg_getprotocol',
        'gnupg_import',
        'gnupg_init',
        'gnupg_keyinfo',
        'gnupg_setarmor',
        'gnupg_seterrormode',
        'gnupg_setsignmode',
        'gnupg_sign',
        'gnupg_verify',
        'hash_hmac_file',
        'igbinary_unserialize',
        'imagefilledpolygon',
        'imagefilter',
        'imagegd',
        'imagegd2',
        'imageinterlace',
        'imageopenpolygon',
        'imagepolygon',
        'imagerotate',
        'imagesetinterpolation',
        'imagettfbbox',
        'imagettftext',
        'imagexbm',
        'imap_delete',
        'imap_open',
        'imap_rfc822_write_address',
        'imap_sort',
        'imap_undelete',
        'inflate_add',
        'inflate_get_read_len',
        'inflate_get_status',
        'inotify_rm_watch',
        'intlcal_from_date_time',
        'intlcal_get_weekend_transition',
        'intlgregcal_create_instance',
        'intlgregcal_is_leap_year',
        'intltz_create_enumeration',
        'intltz_get_canonical_id',
        'intltz_get_display_name',
        'long2ip',
        'lzf_compress',
        'lzf_decompress',
        'mail',
        'mailparse_msg_extract_part',
        'mailparse_msg_extract_part_file',
        'mailparse_msg_extract_whole_part_file',
        'mailparse_msg_free',
        'mailparse_msg_get_part',
        'mailparse_msg_get_part_data',
        'mailparse_msg_get_structure',
        'mailparse_msg_parse',
        'mailparse_stream_encode',
        'memcache_add',
        'memcache_add_server',
        'memcache_append',
        'memcache_cas',
        'memcache_close',
        'memcache_connect',
        'memcache_decrement',
        'memcache_delete',
        'memcache_flush',
        'memcache_get_extended_stats',
        'memcache_get_server_status',
        'memcache_get_stats',
        'memcache_get_version',
        'memcache_increment',
        'memcache_pconnect',
        'memcache_prepend',
        'memcache_replace',
        'memcache_set',
        'memcache_set_compress_threshold',
        'memcache_set_failure_callback',
        'memcache_set_server_params',
        'mongodb\bson\tophp',
        'msg_receive',
        'msg_remove_queue',
        'msg_send',
        'msg_set_queue',
        'msg_stat_queue',
        'mysqli_poll',
        'mysqli_real_connect',
        'mysqli_stmt_bind_param',
        'normalizer_get_raw_decomposition',
        'oauth_get_sbs',
        'oci_collection_append',
        'oci_collection_assign',
        'oci_collection_element_assign',
        'oci_collection_element_get',
        'oci_collection_max',
        'oci_collection_size',
        'oci_collection_trim',
        'oci_fetch_object',
        'oci_field_is_null',
        'oci_field_name',
        'oci_field_precision',
        'oci_field_scale',
        'oci_field_size',
        'oci_field_type',
        'oci_field_type_raw',
        'oci_free_collection',
        'oci_free_descriptor',
        'oci_lob_append',
        'oci_lob_eof',
        'oci_lob_erase',
        'oci_lob_export',
        'oci_lob_flush',
        'oci_lob_import',
        'oci_lob_load',
        'oci_lob_read',
        'oci_lob_rewind',
        'oci_lob_save',
        'oci_lob_seek',
        'oci_lob_size',
        'oci_lob_tell',
        'oci_lob_truncate',
        'oci_lob_write',
        'oci_register_taf_callback',
        'oci_result',
        'ocigetbufferinglob',
        'ocisetbufferinglob',
        'odbc_procedurecolumns',
        'odbc_procedures',
        'odbc_result',
        'openssl_pkcs7_read',
        'pg_exec',
        'pg_fetch_all',
        'pg_get_notify',
        'pg_get_result',
        'pg_pconnect',
        'pg_select',
        'pg_send_execute',
        'preg_filter',
        'preg_replace_callback_array',
        'sapi_windows_cp_get',
        'sem_acquire',
        'sem_get',
        'sem_release',
        'sem_remove',
        'shm_detach',
        'shm_get_var',
        'shm_has_var',
        'shm_put_var',
        'shm_remove',
        'shm_remove_var',
        'shmop_close',
        'shmop_delete',
        'shmop_read',
        'shmop_size',
        'shmop_write',
        'snmp_set_enum_print',
        'snmp_set_valueretrieval',
        'snmpset',
        'socket_addrinfo_lookup',
        'socket_bind',
        'socket_cmsg_space',
        'socket_connect',
        'socket_create_pair',
        'socket_get_option',
        'socket_getopt',
        'socket_getpeername',
        'socket_getsockname',
        'socket_read',
        'socket_recv',
        'socket_recvfrom',
        'socket_recvmsg',
        'socket_select',
        'socket_send',
        'socket_sendmsg',
        'socket_sendto',
        'socket_set_blocking',
        'socket_set_option',
        'socket_setopt',
        'socket_shutdown',
        'socket_strerror',
        'sodium_crypto_generichash',
        'sodium_crypto_generichash_final',
        'sodium_crypto_generichash_init',
        'sodium_crypto_generichash_update',
        'sodium_crypto_kx_client_session_keys',
        'sodium_crypto_secretstream_xchacha20poly1305_rekey',
        'sqlsrv_connect',
        'sqlsrv_errors',
        'sqlsrv_fetch_array',
        'sqlsrv_fetch_object',
        'sqlsrv_get_field',
        'sqlsrv_prepare',
        'sqlsrv_query',
        'sqlsrv_server_info',
        'stomp_abort',
        'stomp_ack',
        'stomp_begin',
        'stomp_commit',
        'stomp_read_frame',
        'stomp_send',
        'stomp_set_read_timeout',
        'stomp_subscribe',
        'stomp_unsubscribe',
        'stream_select' => ['8.0'],
        'substr_replace',
        'tidy_getopt',
        'uopz_allow_exit',
        'uopz_get_mock',
        'uopz_get_property',
        'uopz_get_return',
        'uopz_get_static',
        'uopz_set_mock',
        'uopz_set_property',
        'uopz_set_static',
        'uopz_unset_mock',
        'xdiff_file_bdiff',
        'xdiff_file_bdiff_size',
        'xdiff_file_diff',
        'xdiff_file_diff_binary',
        'xdiff_file_merge3',
        'xdiff_file_rabdiff',
        'xdiff_string_bdiff',
        'xdiff_string_bdiff_size',
        'xdiff_string_bpatch',
        'xdiff_string_diff',
        'xdiff_string_diff_binary',
        'xdiff_string_merge3',
        'xdiff_string_patch',
        'xdiff_string_patch_binary',
        'xdiff_string_rabdiff',
        'xmlrpc_server_add_introspection_data',
        'xmlrpc_server_call_method',
        'xmlrpc_server_destroy',
        'xmlrpc_server_register_introspection_callback',
        'xmlrpc_server_register_method',
        'yaml_emit',
        'yaml_emit_file',
        'zip_entry_close',
        'zlib_encode',
    ];

    /**
     *
     * @var Codebase
     */
    private static $codebase;

    public static function setUpBeforeClass(): void
    {
        $project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                new FakeFileProvider(),
                new FakeParserCacheProvider()
            )
        );
        self::$codebase = $project_analyzer->getCodebase();
    }


    public function testIgnoresAreSortedAndUnique(): void
    {
        $previousFunction = "";
        foreach (self::$ignoredFunctions as $key => $value) {
            /** @var string */
            $function = is_int($key) ? $value : $key;

            $this->assertGreaterThan(0, strcmp($function, $previousFunction));
            $previousFunction = $function;
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::$codebase = null;
    }

    /**
     * @covers \Psalm\Internal\Codebase\InternalCallMapHandler::getCallMap
     */
    public function testGetcallmapReturnsAValidCallmap(): void
    {
        $callMap = InternalCallMapHandler::getCallMap();
        self::assertArrayKeysAreStrings($callMap, "Returned CallMap has non-string keys");
        self::assertArrayValuesAreArrays($callMap, "Returned CallMap has non-array values");
        foreach ($callMap as $function => $signature) {
            self::assertArrayKeysAreZeroOrString($signature, "Function " . $function . " in returned CallMap has invalid keys");
            self::assertArrayValuesAreStrings($signature, "Function " . $function . " in returned CallMap has non-string values");
            foreach ($signature as $type) {
                self::assertStringIsParsableType($type, "Function " . $function . " in returned CallMap contains invalid type declaration " . $type);
            }
        }
    }

    /**
     *
     * @return iterable<string, array{0: callable-string, 1: array<int|string>}>
     */
    public function callMapEntryProvider(): iterable
    {
        /**
         * This call is needed since InternalCallMapHandler uses the singleton that is initialized by it.
         **/
        new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                new FakeFileProvider(),
                new FakeParserCacheProvider()
            )
        );
        $callMap = InternalCallMapHandler::getCallMap();
        foreach ($callMap as $function => $entry) {
            // Skip class methods
            if (strpos($function, '::') !== false || !function_exists($function)) {
                continue;
            }
            // Skip functions with alternate signatures
            if (isset($callMap["$function'1"]) || preg_match("/\'\d$/", $function)) {
                continue;
            }
            // if ($function != 'fprintf') continue;
            yield "$function: " . json_encode($entry) => [$function, $entry];
        }
    }

    /**
     */
    private function isIgnored(string $functionName): bool
    {
        if (in_array($functionName, self::$ignoredFunctions)) {
            return true;
        }

        if (isset(self::$ignoredFunctions[$functionName])
            && is_array(self::$ignoredFunctions[$functionName])
            && in_array(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, self::$ignoredFunctions[$functionName])) {
            return true;
        }

        return false;
    }

    /**
     * @depends testIgnoresAreSortedAndUnique
     * @depends testGetcallmapReturnsAValidCallmap
     * @dataProvider callMapEntryProvider
     * @coversNothing
     * @psalm-param callable-string $functionName
     */
    public function testIgnoredFunctionsStillFail(string $functionName, array $callMapEntry): void
    {
        if (!$this->isIgnored($functionName)) {
            // Dummy assertion to mark it as passed
            $this->assertTrue(true);
            return;
        }

        $this->expectException(ExpectationFailedException::class);

        try {
            unset($callMapEntry[0]);
            /** @var array<string, string> $callMapEntry */
            $this->assertEntryIsCorrect($callMapEntry, $functionName);
        } catch (InvalidArgumentException $t) {
            // Silence this one for now.
            $this->markTestSkipped('IA');
        } catch (SkippedTestError $t) {
            die('this should not happen');
        } catch (ExpectationFailedException $e) {
            // This is good!
            throw $e;
        } catch (InvalidArgumentException $e) {
            // This can happen if a class does not exist, we handle the message to check for this case.
            if (preg_match('/^Could not get class storage for (.*)$/', $e->getMessage(), $matches)
                && !class_exists($matches[1])
            ) {
                die("Class mentioned in callmap does not exist: " . $matches[1]);
            }
        }

        $this->markTestIncomplete("Remove function '{$functionName}' from your ignores");
    }

    /**
     * This function will test functions that are in the callmap AND currently defined
     * @coversNothing
     * @depends testGetcallmapReturnsAValidCallmap
     * @depends testIgnoresAreSortedAndUnique
     * @dataProvider callMapEntryProvider
     * @psalm-param callable-string $functionName
     * @param array $callMapEntry
     */
    public function testCallMapCompliesWithReflection(string $functionName, array $callMapEntry): void
    {
        if ($this->isIgnored($functionName)) {
            $this->markTestSkipped("Function $functionName is ignored in config");
        }

        unset($callMapEntry[0]);
        /** @var array<string, string> $callMapEntry */
        $this->assertEntryIsCorrect($callMapEntry, $functionName);
    }

    /**
     *
     * @param array<string, string> $callMapEntryWithoutReturn
     * @psalm-param callable-string $functionName
     */
    private function assertEntryIsCorrect(array $callMapEntryWithoutReturn, string $functionName): void
    {
        $rF = new ReflectionFunction($functionName);

        /**
         * Parse the parameter names from the map.
         * @var array<string, array{byRef: bool, refMode: 'rw'|'w', variadic: bool, optional: bool, type: string}>
         */
        $normalizedEntries = [];

        foreach ($callMapEntryWithoutReturn as $key => $entry) {
            $normalizedKey = $key;
            /**
             *
             * @var array{byRef: bool, refMode: 'rw'|'w', variadic: bool, optional: bool, type: string} $normalizedEntry
             */
            $normalizedEntry = [
                'variadic' => false,
                'byRef' => false,
                'optional' => false,
                'type' => $entry,
            ];
            if (strncmp($normalizedKey, '&', 1) === 0) {
                $normalizedEntry['byRef'] = true;
                $normalizedKey = substr($normalizedKey, 1);
            }

            if (strncmp($normalizedKey, '...', 3) === 0) {
                $normalizedEntry['variadic'] = true;
                $normalizedKey = substr($normalizedKey, 3);
            }

            // Read the reference mode
            if ($normalizedEntry['byRef']) {
                $parts = explode('_', $normalizedKey, 2);
                if (count($parts) === 2) {
                    $normalizedEntry['refMode'] = $parts[0];
                    $normalizedKey = $parts[1];
                } else {
                    $normalizedEntry['refMode'] = 'rw';
                }
            }

            // Strip prefixes.
            if (substr($normalizedKey, -1, 1) === "=") {
                $normalizedEntry['optional'] = true;
                $normalizedKey = substr($normalizedKey, 0, -1);
            }

            $normalizedEntry['name'] = $normalizedKey;
            $normalizedEntries[$normalizedKey] = $normalizedEntry;
        }
        foreach ($rF->getParameters() as $parameter) {
            $this->assertArrayHasKey($parameter->getName(), $normalizedEntries, "Callmap is missing entry for param {$parameter->getName()} in $functionName: " . print_r($normalizedEntries, true));
            $this->assertParameter($normalizedEntries[$parameter->getName()], $parameter);
        }
    }

    /**
     *
     * @param array{byRef: bool, refMode: 'rw'|'w', variadic: bool, optional: bool, type: string} $normalizedEntry
     */
    private function assertParameter(array $normalizedEntry, ReflectionParameter $param): void
    {
        $name = $param->getName();
        $this->assertSame($param->isOptional(), $normalizedEntry['optional'], "Expected param '{$name}' to " . ($param->isOptional() ? "be" : "not be") . " optional");
        $this->assertSame($param->isVariadic(), $normalizedEntry['variadic'], "Expected param '{$name}' to " . ($param->isVariadic() ? "be" : "not be") . " variadic");
        $this->assertSame($param->isPassedByReference(), $normalizedEntry['byRef'], "Expected param '{$name}' to " . ($param->isPassedByReference() ? "be" : "not be") . " by reference");

        $expectedType = $param->getType();

        if (isset($expectedType) && !empty($normalizedEntry['type'])) {
            $this->assertTypeValidity($expectedType, $normalizedEntry['type'], "Param '{$name}' has incorrect type");
        }
    }

    /**
     * Since string equality is too strict, we do some extra checking here
     */
    private function assertTypeValidity(ReflectionType $reflected, string $specified, string $message): void
    {
        $expectedType = Reflection::getPsalmTypeFromReflectionType($reflected);

        $parsedType = Type::parseString($specified);

        try {
            $this->assertTrue(UnionTypeComparator::isContainedBy(self::$codebase, $parsedType, $expectedType), $message);
        } catch (InvalidArgumentException $e) {
            if (preg_match('/^Could not get class storage for (.*)$/', $e->getMessage(), $matches)
                && !class_exists($matches[1])
            ) {
                die("Class mentioned in callmap does not exist: " . $matches[1]);
            }
        }
    }
}
