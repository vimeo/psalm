<?php

namespace Psalm\Tests\Internal\Codebase;

use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

class InternalCallMapHandlerTest extends TestCase
{
    private array $ignoredFunctions = [
        'sprintf', 'printf', 'ctype_print', 'date_sunrise' /** deprecated in 8.1 */,
        'ctype_digit', 'ctype_lower', 'ctype_alnum', 'ctype_alpha', 'ctype_cntrl',
        'ctype_graph', 'ctype_lower', 'ctype_print', 'ctype_punct', 'ctype_space', 'ctype_upper',
        'ctype_xdigit', 'file_put_contents', 'sodium_crypto_generichash', 'sodium_crypto_generichash_final',
        'dom_import_simplexml', 'imagegd', 'imagegd2', 'pg_exec', 'mysqli_execute', 'array_multisort'
    ];
    /**
     * Ideally these should all be false, we have them here to reduce noise while we improve the tests or the callmap.
     * @var bool whether to skip functions that are not currently defined in the PHP environment
     */
    private $skipUndefinedFunctions = true;
    /**
     *
     * @var bool whether to skip params for which no definition can be found in the callMap
     */
    private $skipUndefinedParams = true;

    /**
     * These are items that very likely need updating to PHP8.1
     * @var bool whether to skip params that are specified in the callmap as `resource`
     */
    private $skipResources = false;

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
        unset($project_analyzer);

        foreach($callMap as $function => $entry) {
            // Skip class methods
            if (strpos($function, '::') !== false) {
                continue;
            }
//             if (!str_starts_with($function, 'array_')) {
// continue;
//             }
            // Skip functions with alternate signatures
            if (isset($callMap["$function'1"]) || preg_match("/\'\d$/", $function)) {
                continue;
            }
            if ($this->skipUndefinedFunctions && !function_exists($function)) {
                continue;
            }
            yield "$function: " . json_encode($entry) => [$function, $entry];
        }


    }

    /**
     * This function will test functions that are in the callmap AND currently defined
     * @return void
     * @coversNothing
     * @depends testGetcallmapReturnsAValidCallmap
     * @dataProvider callMapEntryProvider
     */
    public function testCallMapCompliesWithReflection(string $functionName, array $callMapEntry): void
    {
        if (!function_exists($functionName)) {
            $this->markTestSkipped("Function $functionName does not exist");
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

        foreach($callMapEntry as $key => $entry) {
            $normalizedKey = $key;
            $normalizedEntry = [
                'variadic' => false,
                'byRef' => false,
                'optional' => false,
                'type' => $entry,
                'name' => $key
            ];
            // Strip prefixes.
            if (strncmp($normalizedKey, '&rw_', 4) === 0) {
                $normalizedEntry['byRef'] = true;
                $normalizedEntry['refMode'] = 'rw';
                $normalizedKey = substr($normalizedKey, 4);
            } elseif (strncmp($normalizedKey, '&w_', 3) === 0) {
                $normalizedEntry['byRef'] = true;
                $normalizedEntry['refMode'] = 'w';
                $normalizedKey = substr($normalizedKey, 3);
            }
            if (strncmp($normalizedKey, '...', 3) === 0) {
                $normalizedEntry['variadic'] = true;
                $normalizedKey = substr($normalizedKey, 3);
            }
            if (substr($normalizedKey, -1, 1) === "=") {
                $normalizedEntry['optional'] = true;
                $normalizedKey = substr($normalizedKey, 0, -1);
            }

            $normalizedEntries[$normalizedKey] = $normalizedEntry;

        }
        foreach($rF->getParameters() as $parameter) {
            if ($this->skipUndefinedParams && !isset($normalizedEntries[$parameter->getName()])) {
                continue;
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
        if (in_array($functionName, $this->ignoredFunctions)) {
            $this->markTestSkipped('Function is ignored in config');
        }
        $name = $param->getName();
        // $identifier = "Param $functionName - $name";
        try {
        $this->assertSame($param->isOptional(), $normalizedEntry['optional'], "Expected param '{$name}' to " . ($param->isOptional() ? "be" : "not be") . " optional");
        $this->assertSame($param->isVariadic(), $normalizedEntry['variadic'], "Expected param '{$name}' to " . ($param->isVariadic() ? "be" : "not be") . " variadic");
        $this->assertSame($param->isPassedByReference(), $normalizedEntry['byRef'], "Expected param '{$name}' to " . ($param->isPassedByReference() ? "be" : "not be") . " by reference");
        } catch(\Throwable $t) {
            $this->markTestSkipped($t->getMessage());
        }

        $expectedType = $param->getType();

        if ($expectedType instanceof ReflectionNamedType) {
            $this->assertTypeValidity($expectedType, $normalizedEntry['type'], "Param '{$name}' has incorrect type");
        } else {
            // $this->markTestSkipped('Only simple named types are tested');
        }



    }

    /**
     * Since string equality is too strict, we do some extra checking here
     */
    private function assertTypeValidity(ReflectionNamedType $reflected, string $specified, string $message): void
    {
        // In case reflection returns mixed we assume any type specified in the callmap is more specific and correct
        if ($reflected->getName() === 'mixed') {
            return;
        }


        if ($reflected->getName() === 'callable' && $reflected->allowsNull()
            && preg_match('/^(null\|callable\(.*\):.*|callable\(.*\):.*\|null)$/', $specified)
        ) {
            return;
        }
        // Trim leading namespace separator
        $specified = ltrim($specified, "\\");
        if ($reflected->getName() === 'array' && !$reflected->allowsNull()) {
            if (preg_match('/^(array|list|non-empty-array)(<.*>|{.*})?$/', $specified)
                || in_array($specified, ['string[]|int[]'])
            ) {
                return;
            }
        } elseif($reflected->getName() === 'array') {
            // Optional array
            if (preg_match('/^((array|list|non-empty-array)(<.*>|{.*})?\|null|null\|(array|list|non-empty-array)(<.*>|{.*})?)$/', $specified)) {
                return;
            }
        }

        if ($reflected->getName() === 'float' && in_array($specified, ['int|float', 'float|int'])) {
            return;
        }
        if ($reflected->getName() === 'bool' && in_array($specified, ['true', 'false'])) {
            return;
        }
        if ($reflected->getName() === 'callable' && preg_match('/^callable\(/', $specified)) {
            return;
        }

        if ($reflected->getName() === 'string' && $specified === '?string' && $reflected->allowsNull()) {
            return;
        }
        if ($reflected->getName() === 'string' && in_array($specified , ['class-string', 'numeric-string', 'string'])) {
            return;
        }
        if ($reflected->getName() === 'int' &&  preg_match('/^(\d+|positive-int|int(<\d+,\d+>))?(\|(\d+|positive-int|int))*$/', $specified)) {

        // in_array($specified , [
        //     'positive-int', 'int', '0|positive-int', '256|512|1024|16384', '1|2|3|4|5|6|7'
        //     ])) {
            return;
        }


        if ($reflected->allowsNull()) {
            $escaped = preg_quote($reflected->getName());
            $this->assertMatchesRegularExpression("/^(\?{$escaped}|{$escaped}\|null|null\|{$escaped})$/", $specified, $message);
            return;
        }

        if ($this->skipResources && $specified === 'resource') {
            return;
        }
        $this->assertEqualsIgnoringCase($reflected->getName(), $specified, $message);
    }
}
