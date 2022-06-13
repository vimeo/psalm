<?php

namespace Psalm\Tests\Internal\Codebase;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use PHPUnit\Framework\SkippedTestError;
use PHPUnit\Framework\SyntheticSkippedError;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\ExpectationFailedException;
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
use SebastianBergmann\RecursionContext\InvalidArgumentException;

class InternalCallMapHandlerTest extends TestCase
{
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
            // Skip functions with alternate signatures
            if (isset($callMap["$function\'1"]) || preg_match("/\'\d$/", $function)) {
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
        $name = $param->getName();
        // $identifier = "Param $functionName - $name";
        $this->assertSame($param->isOptional(), $normalizedEntry['optional'], "Expected param '{$name}' to " . ($param->isOptional() ? "be" : "not be") . " optional");
        $this->assertSame($param->isVariadic(), $normalizedEntry['variadic'], "Expected param '{$name}' to " . ($param->isVariadic() ? "be" : "not be") . " variadic");
        $this->assertSame($param->isPassedByReference(), $normalizedEntry['byRef'], "Expected param '{$name}' to " . ($param->isPassedByReference() ? "be" : "not be") . " by reference");


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
        // Trim leading namespace separator
        $specified = ltrim($specified, "\\");
        if ($reflected->getName() === 'array' && preg_match('/^(array|list)<.*>$/', $specified)) {
            return;
        }
        if ($reflected->getName() === 'float' && $specified === 'int|float') {
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
        if ($reflected->getName() === 'int' && in_array($specified , ['positive-int', 'int'])) {
            return;
        }

        if ($reflected->allowsNull()) {
            $this->assertMatchesRegularExpression("/^\?{$reflected->getName()}|{$reflected->getName()}\|null|null\|{$reflected->getName()}/", $specified, $message);
            return;
        }

        $this->assertEqualsIgnoringCase($reflected->getName(), $specified, $message);
    }
}
