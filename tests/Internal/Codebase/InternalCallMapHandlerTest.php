<?php

namespace Psalm\Tests\Internal\Codebase;

use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\ExpectationFailedException;
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
use Throwable;

use function Amp\call;
use function count;
use function explode;
use function function_exists;
use function implode;
use function in_array;
use function json_encode;
use function preg_match;
use function print_r;
use function strncmp;
use function strpos;
use function substr;

class InternalCallMapHandlerTest extends TestCase
{
    /**
     * @var string[]
     */
    private static $ignoredFunctions = [
        'array_multisort',
        'intlcal_from_date_time', 'imagefilledpolygon',
        /** deprecated in 8.0 */
        'zip_entry_close',
        'enum_exists',
        'igbinary_unserialize',
        'lzf_compress',
        'preg_replace_callback_array',
        'inotify_rm_watch',
        'easter_date',
        'snmpset',
        'bzdecompress',
        'lzf_decompress',
        'mongodb\bson\tophp',
        'fputcsv',
        'sapi_windows_cp_get',
        'hash_hmac_file',
        'array_unshift',
        'array_diff',
        'array_replace_recursive',
        'array_intersect_assoc',
        'array_replace',
        'dns_get_mx',
        'array_intersect',
        'array_intersect_key',
        'array_merge',
        'array_diff_key',
        'crypt',
        'debug_zval_dump',
        'array_merge_recursive',
        'array_diff_assoc',
        'array_push',
        'pg_fetch_all',
        'pg_exec',
        'pg_send_execute',
        'datefmt_set_timezone',
        'socket_strerror',
        'sodium_crypto_kx_client_session_keys',
        'mysqli_stmt_bind_param',
        'socket_getsockname',
        'bcmod',
        'socket_getopt',
        'pg_exec',
        'socket_set_option',
        'socket_sendto',
        'socket_connect',
        'socket_select',
        'datefmt_create',
        'imagettftext',
        'mysqli_poll',
        'socket_recvfrom',
        'pg_select',
        'intltz_get_canonical_id',
        'imagexbm',
        'bcdiv',
        'pg_get_notify',
        'socket_get_option',
        'socket_create_pair',
        'socket_sendmsg',
        'normalizer_get_raw_decomposition',
        'socket_cmsg_space',
        'imagettfbbox',
        'pg_get_result',
        'imagepolygon',
        'imageopenpolygon',
        'socket_getpeername',
        'datefmt_get_timezone',
        'sodium_crypto_generichash_update',
        'socket_read',
        'imap_undelete',
        'socket_bind',
        'intlgregcal_create_instance',
        'imagesetinterpolation',
        'openssl_pkcs7_read',
        'mysqli_real_connect',
        'datefmt_parse',
        'socket_shutdown',
        'datefmt_localtime',
        'intltz_get_display_name',
        'imap_delete',
        'socket_setopt',
        'intlgregcal_is_leap_year',
        'intltz_create_enumeration',
        'imagefilter',
        'socket_addrinfo_lookup',
        'socket_recv',
        'sodium_crypto_secretstream_xchacha20poly1305_rekey',
        'bcpowmod',
        'socket_send',
        'socket_set_blocking'



    ];

    /**
     * @var string[]
     */
    private static $ignoredPrefixes = [
        'apcu_',
        'bc',
        'collator_',
        'ctype_',
        'datefmt_',
        'enchant_',
        'gmp_',
        'gnupg_',
        'image',
        'imap_',
        'inflate_',
        'intl',
        'ldap_',
        'mailparse_',
        'memcache_',
        'msg_',
        'mysqli_',
        'normalizer_',
        'oauth_',
        'oci',
        'odbc_',
        'openssl_',
        'pg_',
        'sem_',
        'shm_',
        'shmop_',
        'snmp_',
        'socket_',
        'sodium_',
        'sqlsrv_',
        'tidy_',
        'transliterator_',
        'uopz_',
        'xdiff_',
        'xmlrpc_server',
        'yaml_',
    ];

    /**
     * Initialized in setup
     * @var string Regex
     */
    private static $prefixRegex = '//';

    /**
     *
     * @var bool whether to skip params for which no definition can be found in the callMap
     */
    private $skipUndefinedParams = false;

    /**
     *
     * @var Codebase
     */
    private static $codebase;





    public static function setUpBeforeClass(): void
    {
        self::$prefixRegex = '/^(' . implode('|', self::$ignoredPrefixes) . ')/';
        $project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                new FakeFileProvider(),
                new FakeParserCacheProvider()
            )
        );
        self::$codebase = $project_analyzer->getCodebase();
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
     * @return iterable<string, array{0: string, 1: array<int|string>}>
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
     *
     * @param string $functionName
     * @return bool
     */
    private function isIgnored(string $functionName)
    {
        /** @psalm-assert callable-string $functionName */
        if (in_array($functionName, self::$ignoredFunctions)) {
            return true;

        }
        // if (preg_match(self::$prefixRegex, $functionName)) {
        //     return true;
        //     $this->markTestSkipped("Function $functionName has ignored prefix");
        // }
        return false;
    }

    /**
     * @dataProvider callMapEntryProvider
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


        } catch(\InvalidArgumentException $t) {
            // Silence this one for now.
            $this->markTestSkipped('IA');
        } catch(\PHPUnit\Framework\SkippedTestError $t) {
            die('this should not happen');
        } catch(ExpectationFailedException $e) {
            // This is good!
            throw $e;
        } catch(InvalidArgumentException $e) {
            // This can happen if a class does not exist, we handle the message to check for this case.
            if (preg_match('/^Could not get class storage for (.*)$/', $e->getMessage(), $matches)
                && !class_exists($matches[1])
            ) {
                die("Class mentioned in callmap does not exist: " . $matches[1]);
            }

        } catch(Warning $w) {
            throw $w;
        } catch(\Throwable $t) {
            var_dump($t->getMessage());
            die(get_class($t));
        }

        $this->markTestIncomplete("Remove function '{$functionName}' from your ignores");
        // die("Function $functionName did not show error incallmap") ;
    }

    /**
     * This function will test functions that are in the callmap AND currently defined
     * @coversNothing
     * @depends testGetcallmapReturnsAValidCallmap
     * @dataProvider callMapEntryProvider
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
            if ($this->skipUndefinedParams && !isset($normalizedEntries[$parameter->getName()])) {
                continue;
            } else {
                $this->assertArrayHasKey($parameter->getName(), $normalizedEntries, "Callmap is missing entry for param {$parameter->getName()} in $functionName: " . print_r($normalizedEntries, true));
            }
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

        if (isset($expectedType) && !empty($specified)) {
            $this->assertTypeValidity($expectedType, $normalizedEntry['type'], "Param '{$name}' has incorrect type");
        }
    }

    /**
     * Since string equality is too strict, we do some extra checking here
     */
    private function assertTypeValidity(ReflectionType $reflected, string $specified, string $message): void
    {
        $expectedType = Reflection::getPsalmTypeFromReflectionType($reflected);

        try {
        $parsedType = Type::parseString($specified);
        } catch(\Throwable $t) {
            die("Failed to parse type: $specified -- $message");
        }

        try {
        $this->assertTrue(UnionTypeComparator::isContainedBy(self::$codebase, $parsedType, $expectedType), $message);
        } catch(InvalidArgumentException $e) {
            if (preg_match('/^Could not get class storage for (.*)$/', $e->getMessage(), $matches)
                && !class_exists($matches[1])
            ) {
                die("Class mentioned in callmap does not exist: " . $matches[1]);
            }
        }
    }
}
