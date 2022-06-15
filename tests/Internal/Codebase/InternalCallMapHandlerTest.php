<?php

namespace Psalm\Tests\Internal\Codebase;

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
        'sprintf', 'printf', 'ctype_print', 'date_sunrise' /** deprecated in 8.1 */,
        'file_put_contents',
        'dom_import_simplexml', 'imagegd', 'imagegd2', 'mysqli_execute', 'array_multisort',
        'intlcal_from_date_time', 'simplexml_import_dom', 'imagefilledpolygon',
        /** deprecated in 8.0 */
        'zip_entry_close',
        'date_time_set',
        'curl_unescape',
        'extract',
        'enum_exists',
        'igbinary_unserialize',
        'count',
        'lzf_compress',
        'long2ip',
        'array_column',
        'preg_replace_callback_array',
        'preg_filter',
        'zlib_encode',
        'inotify_rm_watch',
        'mail',
        'easter_date',
        'date_isodate_set',
        'snmpset',
        'get_class_methods',
        'filter_var_array',
        'deflate_add',
        'bzdecompress',
        'substr_replace',
        'lzf_decompress',
        'mongodb\bson\tophp',
        'fputcsv',
        'get_headers',
        'get_parent_class',
        'filter_var',
        'array_key_exists',
        'sapi_windows_cp_get',
        'stream_select',
        'hash_hmac_file'


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
     * @return iterable<string, list>
     */
    public function callMapEntryProvider(): iterable
    {

        $project_analyzer = new ProjectAnalyzer(
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
     * This function will test functions that are in the callmap AND currently defined
     * @coversNothing
     * @depends testGetcallmapReturnsAValidCallmap
     * @dataProvider callMapEntryProvider
     */
    public function testCallMapCompliesWithReflection(string $functionName, array $callMapEntry): void
    {
        if (!function_exists($functionName)) {
            $this->markTestSkipped("Function $functionName does not exist");
        }
        if (in_array($functionName, self::$ignoredFunctions)) {
            $this->markTestSkipped("Function $functionName is ignored in config");
        }
        if (preg_match(self::$prefixRegex, $functionName)) {
            $this->markTestSkipped("Function $functionName has ignored prefix");
        }
        $this->assertEntryIsCorrect($callMapEntry, $functionName);
    }

    private function assertEntryIsCorrect(array $callMapEntry, string $functionName): void
    {
        $rF = new ReflectionFunction($functionName);

        // For now, ignore return types.
        unset($callMapEntry[0]);

        /**
         * Parse the parameter names from the map.
         * @var array<string, array{byRef: bool, refMode: 'rw'|'w', variadic: bool, optional: bool, type: string}
         */
        $normalizedEntries = [];

        foreach ($callMapEntry as $key => $entry) {
            $normalizedKey = $key;
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
            $this->assertParameter($normalizedEntries[$parameter->getName()], $parameter, $functionName);
        }
    }

    /**
     *
     * @param array{byRef: bool, refMode: 'rw'|'w', variadic: bool, optional: bool, type: string} $normalizedEntry
     */
    private function assertParameter(array $normalizedEntry, ReflectionParameter $param, string $functionName): void
    {
        $name = $param->getName();
        // $identifier = "Param $functionName - $name";
        try {
            $this->assertSame($param->isOptional(), $normalizedEntry['optional'], "Expected param '{$name}' to " . ($param->isOptional() ? "be" : "not be") . " optional");
            $this->assertSame($param->isVariadic(), $normalizedEntry['variadic'], "Expected param '{$name}' to " . ($param->isVariadic() ? "be" : "not be") . " variadic");
            $this->assertSame($param->isPassedByReference(), $normalizedEntry['byRef'], "Expected param '{$name}' to " . ($param->isPassedByReference() ? "be" : "not be") . " by reference");
        } catch (Throwable $t) {
            $this->markTestSkipped("Exception: " . $t->getMessage());
        }

        $expectedType = $param->getType();

        if (isset($expectedType)) {
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

        $this->assertTrue(UnionTypeComparator::isContainedBy(self::$codebase, $parsedType, $expectedType), $message);
    }
}
