<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use InvalidArgumentException;
use Override;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Interner;
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

use function array_is_list;
use function array_keys;
use function array_shift;
use function class_exists;
use function count;
use function enum_exists;
use function function_exists;
use function in_array;
use function interface_exists;
use function is_array;
use function is_int;
use function json_encode;
use function preg_match;
use function print_r;
use function strcasecmp;
use function strpos;
use function substr;
use function version_compare;

use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_VERSION;
use const PHP_VERSION_ID;

/** @group callmap */
final class InternalCallMapHandlerTest extends TestCase
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
        '/^redis/i', // redis extension
        '/^imagick/i', // imagick extension
        '/^uopz/i', // uopz extension
        '/^memcache[_:]/i', // memcache extension
        '/^memcachepool/i', // memcache extension
        '/^gnupg/i', // gnupg extension
    ];

    /**
     * Specify a function name as value, or a function name as key and
     * an array containing the PHP versions in which to ignore this function as values.
     *
     * @var array<int|string, string|list<string>>
     */
    private static array $ignoredFunctions = [
        'datefmt_create' => ['8.0'],
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
        'Memcached::cas', // memcached 3.2.0 has incorrect reflection
        'Memcached::casByKey', // memcached 3.2.0 has incorrect reflection
        'OAuth::fetch',
        'OAuth::getAccessToken',
        'OAuth::setCAPath',
        'OAuth::setTimeout',
        'OAuth::setTimestamp',
        'OAuthProvider::consumerHandler',
        'OAuthProvider::isRequestTokenEndpoint',
        'OAuthProvider::timestampNonceHandler',
        'OAuthProvider::tokenHandler',
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
        'pg_close_stmt' => ['8.5'], // PHP 8.5 reflection declares the wrongly-cased Pgsql\Connection
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
        'DateTime::add' => ['8.1', '8.2', '8.3', '8.4', '8.5'], // DateTime does not contain static
        'DateTime::modify' => ['8.1', '8.2', '8.3', '8.4', '8.5'], // DateTime does not contain static
        'DateTime::createFromFormat' => ['8.1', '8.2', '8.3', '8.4', '8.5'], // DateTime does not contain static
        'DateTime::createFromImmutable' => ['8.1'],
        'DateTime::createFromInterface',
        'DateTimeImmutable::createFromInterface',
        'DateTime::setDate' => ['8.1', '8.2', '8.3', '8.4', '8.5'], // DateTime does not contain static
        'DateTime::setISODate' => ['8.1', '8.2', '8.3', '8.4', '8.5'], // DateTime does not contain static
        'DateTime::setTime' => ['8.1', '8.2', '8.3', '8.4', '8.5'], // DateTime does not contain static
        'DateTime::setTimestamp' => ['8.1', '8.2', '8.3', '8.4', '8.5'], // DateTime does not contain static
        'DateTime::setTimezone' => ['8.1', '8.2', '8.3', '8.4', '8.5'], // DateTime does not contain static
        'DateTime::sub' => ['8.1', '8.2', '8.3', '8.4', '8.5'], // DateTime does not contain static
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
        'Closure::__invoke',
        'DOMImplementation::__construct',
        'IntlIterator::__construct',
        'PDO::cubrid_schema',
        'PDO::pgsqlCopyFromArray',
        'PDO::pgsqlCopyFromFile',
        'PDO::pgsqlCopyToArray',
        'PDO::pgsqlCopyToFile',
        'PDO::pgsqlGetNotify',
        'PDO::pgsqlGetPid',
        'PDO::pgsqlLOBCreate',
        'PDO::pgsqlLOBOpen',
        'PDO::pgsqlLOBUnlink',
        'PDO::sqliteCreateAggregate',
        'PDO::sqliteCreateCollation',
        'PDO::sqliteCreateFunction',
        'SimpleXMLElement::__get',
        'SimpleXMLElement::offsetExists',
        'SimpleXMLElement::offsetGet',
        'SimpleXMLElement::offsetSet',
        'SimpleXMLElement::offsetUnset',
        'SplDoublyLinkedList::__construct',
        'SplHeap::__construct',
        'SplMaxHeap::__construct',
        'SplObjectStorage::__construct',
        'SplPriorityQueue::__construct',
        'SplStack::__construct',
    ];

    private static Codebase $codebase;

    #[Override]
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

            $diff = strcasecmp($function, $previousFunction);
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
        self::assertSame(['functions', 'methods'], array_keys($callMap));
        foreach (self::iterateCallMap($callMap) as $function => $signatures) {
            self::assertTrue(array_is_list($signatures), "Function " . $function . " in returned CallMap has invalid signatures");
            self::assertNotEmpty($signatures, "Function " . $function . " in returned CallMap has no signatures");
            foreach ($signatures as $signature) {
                self::assertIsString($signature[0], "Function " . $function . " in returned CallMap has an invalid return type");
                self::assertStringIsParsableType($signature[0], "Function " . $function . " in returned CallMap contains invalid type declaration " . $signature[0]);
                for ($i = 1, $count = count($signature); $i < $count; $i++) {
                    [$name, $type, $flags] = $signature[$i];
                    self::assertIsInt($name, "Function " . $function . " in returned CallMap has an invalid param name");
                    self::assertIsInt($flags, "Function " . $function . " in returned CallMap has invalid param flags");
                    self::assertStringIsParsableType($type, "Function " . $function . " in returned CallMap contains invalid type declaration " . $type);
                }
            }
        }
    }

    /**
     * @param array{functions: array<int, list<list<mixed>>>, methods: array<int, array<int, list<list<mixed>>>>} $callMap
     * @return iterable<string, list<list<mixed>>> function or method name => signatures
     */
    private static function iterateCallMap(array $callMap): iterable
    {
        foreach ($callMap['functions'] as $function => $signatures) {
            yield Interner::str($function) => $signatures;
        }
        foreach ($callMap['methods'] as $class => $methods) {
            foreach ($methods as $method => $signatures) {
                yield Interner::str($class) . '::' . Interner::str($method) => $signatures;
            }
        }
    }

    public function testGetCallablesFromCallmapRemovesRwPrefixFromParameterNames(): void
    {
        $entries = InternalCallMapHandler::getCallablesFromCallMap(Interner::intern('collator_sort')); // has &rw_array parameter as second parameter
        $this->assertNotNull($entries);
        $collator_sort_entry = $entries[0];
        $this->assertIsArray($collator_sort_entry->params);
        $this->assertArrayHasKey(1, $collator_sort_entry->params);
        $this->assertEquals('arr', Interner::str($collator_sort_entry->params[1]->name));
    }

    public function testGetCallablesFromCallmapRemovesWPrefixFromParameterNames(): void
    {
        $entries = InternalCallMapHandler::getCallablesFromCallMap(Interner::intern('curl_multi_exec')); // has &w_still_running parameter as second parameter
        $this->assertNotNull($entries);
        $curl_multi_exec_entry = $entries[0];
        $this->assertIsArray($curl_multi_exec_entry->params);
        $this->assertArrayHasKey(1, $curl_multi_exec_entry->params);
        $this->assertEquals('still_running', Interner::str($curl_multi_exec_entry->params[1]->name));
    }

    /**
     * @return iterable<string, array{string, list<mixed>}>
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
        foreach (self::iterateCallMap($callMap) as $function => $signatures) {
            foreach (static::$skippedPatterns as $skipPattern) {
                if (preg_match($skipPattern, $function)) {
                    continue 2;
                }
            }

            // Skip functions with alternate signatures
            if (count($signatures) > 1) {
                continue;
            }
            $entry = $signatures[0];

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

    /**
     * @psalm-external-mutation-free
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
     * @psalm-external-mutation-free
     */
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

    /**
     * @psalm-external-mutation-free
     */
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
     * @param list<mixed> $callMapEntry a signature: [return type, ...[param name id, type, flags]]
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
                /** @var list<array{int, string, int}> $callMapEntry */
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
     * @param list<mixed> $callMapEntry a signature: [return type, ...[param name id, type, flags]]
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

        /** @var list<array{int, string, int}> $callMapEntry */
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
                if (PHP_VERSION_ID < 8_03_00) {
                    return new ReflectionMethod($functionName);
                }

                return ReflectionMethod::createFromMethodName($functionName);
            }

            /** @var callable-string $functionName */
            return new ReflectionFunction($functionName);
        } catch (ReflectionException $e) {
            return null;
        }
    }

    /**
     * @param list<array{int, string, int}> $entryParameters [param name id, type, InternalCallMapHandler::PARAM_* flags]
     */
    private function assertEntryParameters(ReflectionFunctionAbstract $function, array $entryParameters): void
    {
        /**
         * Parse the parameters from the map.
         *
         * @var array<string, array{byRef: bool, refMode: 'rw'|'w'|'r', variadic: bool, optional: bool, type: string}>
         */
        $normalizedEntries = [];

        foreach ($entryParameters as [$name, $type, $flags]) {
            $name = Interner::str($name);
            $normalizedEntries[$name] = [
                'variadic' => ($flags & InternalCallMapHandler::PARAM_VARIADIC) !== 0,
                'byRef' => ($flags & InternalCallMapHandler::PARAM_BY_REF) !== 0,
                'refMode' => ($flags & InternalCallMapHandler::PARAM_REF_WRITE) !== 0
                    ? 'w'
                    : (($flags & InternalCallMapHandler::PARAM_REF_READ) !== 0 ? 'r' : 'rw'),
                'optional' => ($flags & InternalCallMapHandler::PARAM_OPTIONAL) !== 0,
                'type' => $type,
                'name' => $name,
            ];
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
                && !interface_exists($matches[1])
                && !enum_exists($matches[1])
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
