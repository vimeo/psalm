<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use InvalidArgumentException;
use PHPUnit\Framework\AssertionFailedError;
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
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;

use function array_shift;
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
use function version_compare;

use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_VERSION;

/** @group callmap */
class InternalCallMapHandlerTest extends TestCase
{
    /**
     * Regex patterns for callmap entries that should be skipped.
     *
     * These will not be checked against reflection. This prevents a
     * large ignore list for extension functions have invalid reflection
     * or are not maintained.
     *
     * @var list<non-empty-string>
     */
    private static array $skippedPatterns = [
        '/\'\d$/', // skip alternate signatures
        '/^redis/', // redis extension
        '/^imagick/', // imagick extension
        '/^uopz/', // uopz extension
        '/^memcache[_:]/', // memcache extension
        '/^memcachepool/', // memcache extension
        '/^gnupg/', // gnupg extension
    ];

    /**
     * Specify a function name as value, or a function name as key and
     * an array containing the PHP versions in which to ignore this function as values.
     *
     * @var array<int|string, string|list<string>>
     */
    private static array $ignoredFunctions = [
        'array_multisort',
        'datefmt_create' => ['8.0'],
        'fiber::start',
        'imagefilledpolygon',
        'imagegd',
        'imagegd2',
        'imageopenpolygon',
        'imagepolygon',
        'intlgregoriancalendar::__construct',
        'lzf_compress',
        'lzf_decompress',
        'mailparse_msg_extract_part',
        'mailparse_msg_extract_part_file',
        'mailparse_msg_extract_whole_part_file',
        'mailparse_msg_free',
        'mailparse_msg_get_part',
        'mailparse_msg_get_part_data',
        'mailparse_msg_get_structure',
        'mailparse_msg_parse',
        'mailparse_stream_encode',
        'memcached::cas', // memcached 3.2.0 has incorrect reflection
        'memcached::casbykey', // memcached 3.2.0 has incorrect reflection
        'oauth::fetch',
        'oauth::getaccesstoken',
        'oauth::setcapath',
        'oauth::settimeout',
        'oauth::settimestamp',
        'oauthprovider::consumerhandler',
        'oauthprovider::isrequesttokenendpoint',
        'oauthprovider::timestampnoncehandler',
        'oauthprovider::tokenhandler',
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
        'recursiveiteratoriterator::__construct', // Class used in CallMap does not exist: recursiveiterator
        'sqlsrv_fetch_array',
        'sqlsrv_fetch_object',
        'sqlsrv_get_field',
        'sqlsrv_prepare',
        'sqlsrv_query',
        'sqlsrv_server_info',
        'ssh2_forward_accept',
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
    ];

    /**
     * List of function names to ignore only for return type checks.
     *
     * @var array<int|string, string|list<string>>
     */
    private static array $ignoredReturnTypeOnlyFunctions = [
        'appenditerator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'appenditerator::getiteratorindex' => ['8.1', '8.2', '8.3'],
        'arrayobject::getiterator' => ['8.1', '8.2', '8.3'],
        'cachingiterator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'callbackfilteriterator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'curl_multi_getcontent',
        'datetime::add' => ['8.1', '8.2', '8.3'], // DateTime does not contain static
        'datetime::modify' => ['8.1', '8.2', '8.3'], // DateTime does not contain static
        'datetime::createfromformat' => ['8.1', '8.2', '8.3'], // DateTime does not contain static
        'datetime::createfromimmutable' => ['8.1'],
        'datetime::createfrominterface',
        'datetime::setdate' => ['8.1', '8.2', '8.3'], // DateTime does not contain static
        'datetime::setisodate' => ['8.1', '8.2', '8.3'], // DateTime does not contain static
        'datetime::settime' => ['8.1', '8.2', '8.3'], // DateTime does not contain static
        'datetime::settimestamp' => ['8.1', '8.2', '8.3'], // DateTime does not contain static
        'datetime::settimezone' => ['8.1', '8.2', '8.3'], // DateTime does not contain static
        'datetime::sub' => ['8.1', '8.2', '8.3'], // DateTime does not contain static
        'datetimeimmutable::createfrominterface',
        'fiber::getcurrent',
        'filteriterator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'get_cfg_var', // Ignore array return type
        'infiniteiterator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'iteratoriterator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'limititerator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'locale::canonicalize' => ['8.1', '8.2', '8.3'],
        'locale::getallvariants' => ['8.1', '8.2', '8.3'],
        'locale::getkeywords' => ['8.1', '8.2', '8.3'],
        'locale::getprimarylanguage' => ['8.1', '8.2', '8.3'],
        'locale::getregion' => ['8.1', '8.2', '8.3'],
        'locale::getscript' => ['8.1', '8.2', '8.3'],
        'locale::parselocale' => ['8.1', '8.2', '8.3'],
        'messageformatter::create' => ['8.1', '8.2', '8.3'],
        'multipleiterator::current' => ['8.1', '8.2', '8.3'],
        'mysqli::get_charset' => ['8.1', '8.2', '8.3'],
        'mysqli_stmt::get_warnings' => ['8.1', '8.2', '8.3'],
        'mysqli_stmt_get_warnings',
        'mysqli_stmt_insert_id',
        'norewinditerator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'passthru',
        'recursivecachingiterator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'recursivecallbackfilteriterator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'recursivefilteriterator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'recursiveregexiterator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'reflectionclass::getstaticproperties' => ['8.1', '8.2'],
        'reflectionclass::newinstanceargs' => ['8.1', '8.2', '8.3'],
        'reflectionfunction::getclosurescopeclass' => ['8.1', '8.2', '8.3'],
        'reflectionfunction::getclosurethis' => ['8.1', '8.2', '8.3'],
        'reflectionmethod::getclosurescopeclass' => ['8.1', '8.2', '8.3'],
        'reflectionmethod::getclosurethis' => ['8.1', '8.2', '8.3'],
        'reflectionobject::getstaticproperties' => ['8.1', '8.2'],
        'reflectionobject::newinstanceargs' => ['8.1', '8.2', '8.3'],
        'regexiterator::getinneriterator' => ['8.1', '8.2', '8.3'],
        'register_shutdown_function' => ['8.0', '8.1'],
        'splfileobject::fscanf' => ['8.1', '8.2', '8.3'],
        'spltempfileobject::fscanf' => ['8.1', '8.2', '8.3'],
        'xsltprocessor::transformtoxml' => ['8.1', '8.2', '8.3'],
    ];

    /**
     * List of function names to ignore because they cannot be reflected.
     *
     * These could be truly inaccessible, or they could be functions removed in newer PHP versions.
     * Removed functions should be removed from CallMap and added to the appropriate delta.
     *
     * @var array<int|string, string|list<string>>
     */
    private static array $ignoredUnreflectableFunctions = [
        'closure::__invoke',
        'domimplementation::__construct',
        'intliterator::__construct',
        'pdo::cubrid_schema',
        'pdo::pgsqlcopyfromarray',
        'pdo::pgsqlcopyfromfile',
        'pdo::pgsqlcopytoarray',
        'pdo::pgsqlcopytofile',
        'pdo::pgsqlgetnotify',
        'pdo::pgsqlgetpid',
        'pdo::pgsqllobcreate',
        'pdo::pgsqllobopen',
        'pdo::pgsqllobunlink',
        'pdo::sqlitecreateaggregate',
        'pdo::sqlitecreatecollation',
        'pdo::sqlitecreatefunction',
        'simplexmlelement::__get',
        'simplexmlelement::offsetexists',
        'simplexmlelement::offsetget',
        'simplexmlelement::offsetset',
        'simplexmlelement::offsetunset',
        'spldoublylinkedlist::__construct',
        'splheap::__construct',
        'splmaxheap::__construct',
        'splobjectstorage::__construct',
        'splpriorityqueue::__construct',
        'splstack::__construct',
    ];

    private static Codebase $codebase;

    public static function setUpBeforeClass(): void
    {
        $project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers(
                new FakeFileProvider(),
                new FakeParserCacheProvider(),
            ),
        );
        self::$codebase = $project_analyzer->getCodebase();
    }

    public function testIgnoresAreSortedAndUnique(): void
    {
        $previousFunction = "";
        foreach (self::$ignoredFunctions as $key => $value) {
            /** @var string */
            $function = is_int($key) ? $value : $key;

            $diff = strcmp($function, $previousFunction);
            $this->assertGreaterThan(0, $diff, "'{$function}' should come before '{$previousFunction}' in InternalCallMapHandlerTest::\$ignoredFunctions");

            $previousFunction = $function;
        }
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
     * @return iterable<string, array{string, array<int|string, string>}>
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
                new FakeParserCacheProvider(),
            ),
        );
        $callMap = InternalCallMapHandler::getCallMap();
        foreach ($callMap as $function => $entry) {
            foreach (static::$skippedPatterns as $skipPattern) {
                if (preg_match($skipPattern, $function)) {
                    continue 2;
                }
            }

            // Skip functions with alternate signatures
            if (isset($callMap["$function'1"])) {
                continue;
            }

            $classNameEnd = strpos($function, '::');
            if ($classNameEnd !== false) {
                $className = substr($function, 0, $classNameEnd);
                if (!class_exists($className, false)) {
                    continue;
                }
            } elseif (!function_exists($function)) {
                continue;
            }

            yield "$function: " . (string) json_encode($entry) => [$function, $entry];
        }
    }

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

    private function isReturnTypeOnlyIgnored(string $functionName): bool
    {
        if (in_array($functionName, static::$ignoredReturnTypeOnlyFunctions, true)) {
            return true;
        }

        if (isset(self::$ignoredReturnTypeOnlyFunctions[$functionName])
            && is_array(self::$ignoredReturnTypeOnlyFunctions[$functionName])
            && in_array(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, self::$ignoredReturnTypeOnlyFunctions[$functionName])) {
            return true;
        }

        return false;
    }

    private function isUnreflectableIgnored(string $functionName): bool
    {
        if (in_array($functionName, static::$ignoredUnreflectableFunctions, true)) {
            return true;
        }

        if (isset(self::$ignoredUnreflectableFunctions[$functionName])
            && is_array(self::$ignoredUnreflectableFunctions[$functionName])
            && in_array(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, self::$ignoredUnreflectableFunctions[$functionName])) {
            return true;
        }

        return false;
    }

    /**
     * @depends testIgnoresAreSortedAndUnique
     * @depends testGetcallmapReturnsAValidCallmap
     * @dataProvider callMapEntryProvider
     * @coversNothing
     * @psalm-param string $functionName
     * @param array<int|string, string> $callMapEntry
     */
    public function testIgnoredFunctionsStillFail(string $functionName, array $callMapEntry): void
    {
        $functionIgnored = $this->isIgnored($functionName);
        $unreflectableIgnored = $this->isUnreflectableIgnored($functionName);
        if (!$functionIgnored && !$this->isReturnTypeOnlyIgnored($functionName) && !$unreflectableIgnored) {
            // Dummy assertion to mark it as passed
            $this->assertTrue(true);
            return;
        }

        $function = $this->getReflectionFunction($functionName);
        if ($unreflectableIgnored && $function !== null) {
            $this->fail("Remove '{$functionName}' from InternalCallMapHandlerTest::\$ignoredUnreflectableFunctions");
        } elseif ($function === null) {
            $this->assertTrue(true);
            return;
        }

        /** @var string $entryReturnType */
        $entryReturnType = array_shift($callMapEntry);

        if ($functionIgnored) {
            try {
                /** @var array<string, string> $callMapEntry */
                $this->assertEntryParameters($function, $callMapEntry);
                $this->assertEntryReturnType($function, $entryReturnType);
            } catch (AssertionFailedError $e) {
                $this->assertTrue(true);
                return;
            } catch (ExpectationFailedException $e) {
                $this->assertTrue(true);
                return;
            }
            $this->fail("Remove '{$functionName}' from InternalCallMapHandlerTest::\$ignoredFunctions");
        }

        try {
            $this->assertEntryReturnType($function, $entryReturnType);
        } catch (AssertionFailedError $e) {
            $this->assertTrue(true);
            return;
        } catch (ExpectationFailedException $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail("Remove '{$functionName}' from InternalCallMapHandlerTest::\$ignoredReturnTypeOnlyFunctions");
    }

    /**
     * This function will test functions that are in the callmap AND currently defined
     *
     * @coversNothing
     * @depends testGetcallmapReturnsAValidCallmap
     * @depends testIgnoresAreSortedAndUnique
     * @dataProvider callMapEntryProvider
     * @psalm-param string $functionName
     * @param array<int|string, string> $callMapEntry
     */
    public function testCallMapCompliesWithReflection(string $functionName, array $callMapEntry): void
    {
        if ($this->isIgnored($functionName)) {
            $this->markTestSkipped("Function $functionName is ignored in config");
        }

        $function = $this->getReflectionFunction($functionName);
        if ($function === null) {
            if (!$this->isUnreflectableIgnored($functionName)) {
                $this->fail('Unable to reflect method. Add name to $ignoredUnreflectableFunctions if exists in latest PHP version.');
            }
            return;
        }

        /** @var string $entryReturnType */
        $entryReturnType = array_shift($callMapEntry);

        /** @var array<string, string> $callMapEntry */
        $this->assertEntryParameters($function, $callMapEntry);

        if (!$this->isReturnTypeOnlyIgnored($functionName)) {
            $this->assertEntryReturnType($function, $entryReturnType);
        }
    }

    /**
     * Returns the correct reflection type for function or method name.
     */
    private function getReflectionFunction(string $functionName): ?ReflectionFunctionAbstract
    {
        try {
            if (strpos($functionName, '::') !== false) {
                return new ReflectionMethod($functionName);
            }

            /** @var callable-string $functionName */
            return new ReflectionFunction($functionName);
        } catch (ReflectionException $e) {
            return null;
        }
    }

    /**
     * @param array<string, string> $entryParameters
     */
    private function assertEntryParameters(ReflectionFunctionAbstract $function, array $entryParameters): void
    {
        /**
         * Parse the parameter names from the map.
         *
         * @var array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
         */
        $normalizedEntries = [];

        foreach ($entryParameters as $key => $entry) {
            $normalizedKey = $key;
            /**
             * @var array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string} $normalizedEntry
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
                    if (!($parts[0] === 'rw' || $parts[0] === 'w' || $parts[0] === 'r')) {
                        throw new InvalidArgumentException('Invalid refMode: '.$parts[0]);
                    }
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

            //$this->assertTrue($this->hasParameter($function, $normalizedKey), "Calmap has extra param entry {$normalizedKey}");

            $normalizedEntry['name'] = $normalizedKey;
            $normalizedEntries[$normalizedKey] = $normalizedEntry;
        }

        foreach ($function->getParameters() as $parameter) {
            $this->assertArrayHasKey($parameter->getName(), $normalizedEntries, "Callmap is missing entry for param {$parameter->getName()} in {$function->getName()}: " . print_r($normalizedEntries, true));
            $this->assertParameter($normalizedEntries[$parameter->getName()], $parameter);
        }
    }

    /* Used by above assert
    private function hasParameter(ReflectionFunctionAbstract $function, string $name): bool
    {
        foreach ($function->getParameters() as $parameter)
        {
            if ($parameter->getName() === $name) {
                return true;
            }
        }

        return false;
    }
    */

    /**
     * @param array{byRef: bool, name?: string, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string} $normalizedEntry
     */
    private function assertParameter(array $normalizedEntry, ReflectionParameter $param): void
    {
        $name = $param->getName();
        $this->assertSame($param->isOptional(), $normalizedEntry['optional'], "Expected param '{$name}' to " . ($param->isOptional() ? "be" : "not be") . " optional");
        $this->assertSame($param->isVariadic(), $normalizedEntry['variadic'], "Expected param '{$name}' to " . ($param->isVariadic() ? "be" : "not be") . " variadic");
        $this->assertSame($param->isPassedByReference(), $normalizedEntry['byRef'], "Expected param '{$name}' to " . ($param->isPassedByReference() ? "be" : "not be") . " by reference");

        $expectedType = $param->getType();

        if (isset($expectedType) && !empty($normalizedEntry['type'])) {
            $this->assertTypeValidity($expectedType, $normalizedEntry['type'], "Param '{$name}'");
        }
    }

    public function assertEntryReturnType(ReflectionFunctionAbstract $function, string $entryReturnType): void
    {
        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            $expectedType = $function->hasTentativeReturnType() ? $function->getTentativeReturnType() : $function->getReturnType();
        } else {
            $expectedType = $function->getReturnType();
        }

        $this->assertNotEmpty($entryReturnType, 'CallMap entry has empty return type');
        if ($expectedType !== null) {
            $this->assertTypeValidity($expectedType, $entryReturnType, 'Return');
        }
    }

    /**
     * Since string equality is too strict, we do some extra checking here
     */
    private function assertTypeValidity(ReflectionType $reflected, string $specified, string $msgPrefix): void
    {
        $expectedType = Reflection::getPsalmTypeFromReflectionType($reflected);
        $callMapType = Type::parseString($specified);

        try {
            $this->assertTrue(UnionTypeComparator::isContainedBy(self::$codebase, $callMapType, $expectedType, false, false, null, false, false), "{$msgPrefix} type '{$specified}' is not contained by reflected type '{$reflected}'");
        } catch (InvalidArgumentException $e) {
            if (preg_match('/^Could not get class storage for (.*)$/', $e->getMessage(), $matches)
                && !class_exists($matches[1])
            ) {
                $this->fail("Class used in CallMap does not exist: {$matches[1]}");
            }
        }

        // Reflection::getPsalmTypeFromReflectionType adds |null to mixed types so skip comparison
        if (!$expectedType->hasMixed()) {
            $this->assertSame($expectedType->isNullable(), $callMapType->isNullable(), "{$msgPrefix} type '{$specified}' missing null from reflected type '{$reflected}'");
            //$this->assertSame($expectedType->hasBool(), $callMapType->hasBool(), "{$msgPrefix} type '{$specified}' missing bool from reflected type '{$reflected}'");
            $this->assertSame($expectedType->hasArray(), $callMapType->hasArray(), "{$msgPrefix} type '{$specified}' missing array from reflected type '{$reflected}'");
            $this->assertSame($expectedType->hasInt(), $callMapType->hasInt(), "{$msgPrefix} type '{$specified}' missing int from reflected type '{$reflected}'");
            $this->assertSame($expectedType->hasFloat(), $callMapType->hasFloat(), "{$msgPrefix} type '{$specified}' missing float from reflected type '{$reflected}'");
        }
    }
}
