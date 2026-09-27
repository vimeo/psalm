<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use FilesystemIterator;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\StrId;
use Psalm\Tests\TestCase;
use RegexIterator;

use function array_is_list;
use function array_keys;
use function count;
use function is_array;
use function is_file;
use function is_int;
use function is_string;
use function uksort;

/**
 * @psalm-import-type CallMap from InternalCallMapHandler as TCallMap
 * @psalm-type TCallMaps=array<string, TCallMap>
 */
final class CallMapTest extends TestCase
{
    protected const DICTIONARY_PATH = 'dictionaries';

    public function testDictionaryPathMustBeAReadableDirectory(): void
    {
        self::assertDirectoryExists(self::DICTIONARY_PATH, self::DICTIONARY_PATH . " is not a valid directory");
        self::assertDirectoryIsReadable(self::DICTIONARY_PATH, self::DICTIONARY_PATH . " is not a readable directory");
    }

    /**
     * @depends testDictionaryPathMustBeAReadableDirectory
     * @return array<int, TCallMap>
     */
    public function testLoadCallMaps(): array
    {
        /** @var iterable<string, string> */
        $deltaFileIterator = new RegexIterator(
            new FilesystemIterator(
                self::DICTIONARY_PATH,
                FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::SKIP_DOTS,
            ),
            '/^CallMap_[\d]{2,}\.php$/i',
            RegexIterator::MATCH,
            RegexIterator::USE_KEY,
        );

        $deltaFiles = [];
        foreach ($deltaFileIterator as $deltaFile => $deltaFilePath) {
            if (!is_file($deltaFilePath)) {
                continue;
            }

            /**
             * @var TCallMap
             */
            $deltaFiles[$deltaFile] = include($deltaFilePath);
        }

        uksort($deltaFiles, 'strnatcasecmp');

        return $deltaFiles;
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    private static function iterateSignatures(array $callMap): iterable
    {
        // every name used in the callmaps must be preloaded
        foreach ($callMap['functions'] as $function => $signatures) {
            self::assertArrayHasKey($function, StrId::STRINGS, "Function id $function is not preloaded");
            yield StrId::STRINGS[$function] => $signatures;
        }
        foreach ($callMap['methods'] as $class => $methods) {
            self::assertArrayHasKey($class, StrId::STRINGS, "Class id $class is not preloaded");
            foreach ($methods as $method => $signatures) {
                self::assertArrayHasKey($method, StrId::STRINGS, "Method id $method is not preloaded");
                yield StrId::STRINGS[$class] . '::' . StrId::STRINGS[$method] => $signatures;
            }
        }
    }

    /**
     * @depends testLoadCallMaps
     * @param TCallMaps $callMaps
     */
    public function testSignaturesHaveTheExpectedFormat(array $callMaps): void
    {
        $flags = InternalCallMapHandler::PARAM_OPTIONAL
            | InternalCallMapHandler::PARAM_VARIADIC
            | InternalCallMapHandler::PARAM_BY_REF
            | InternalCallMapHandler::PARAM_REF_WRITE
            | InternalCallMapHandler::PARAM_REF_READ;
        foreach ($callMaps as $file => $callMap) {
            self::assertSame(['functions', 'methods'], array_keys($callMap), "$file has invalid top-level keys");
            foreach (self::iterateSignatures($callMap) as $function => $signatures) {
                self::assertTrue(array_is_list($signatures), "$function in $file: signatures must be a list");
                self::assertNotEmpty($signatures, "$function in $file has no signatures");
                foreach ($signatures as $signature) {
                    self::assertTrue(array_is_list($signature), "$function in $file: a signature must be a list");
                    self::assertIsString($signature[0], "$function in $file has an invalid return type");
                    for ($i = 1, $count = count($signature); $i < $count; $i++) {
                        $param = $signature[$i];
                        self::assertTrue(
                            is_array($param) && count($param) === 3
                            && is_int($param[0]) && isset(StrId::STRINGS[$param[0]])
                            && is_string($param[1])
                            && is_int($param[2]) && ($param[2] & ~$flags) === 0,
                            "$function in $file has an invalid param #$i",
                        );
                    }
                }
            }
        }
    }

    /**
     * @depends testLoadCallMaps
     * @param TCallMaps $callMaps
     */
    public function testTypesAreParsable(array $callMaps): void
    {
        foreach ($callMaps as $callMap) {
            foreach (self::iterateSignatures($callMap) as $function => $signatures) {
                foreach ($signatures as $signature) {
                    $types = [$signature[0]];
                    for ($i = 1, $count = count($signature); $i < $count; $i++) {
                        $types[] = $signature[$i][1];
                    }
                    foreach ($types as $type) {
                        self::assertStringIsParsableType($type, "Function " . $function . " in main CallMap contains invalid type declaration " . $type);
                    }
                }
            }
        }
    }

    public function testPropertyMapNamesArePreloaded(): void
    {
        /** @var array<int, array<int, string>> */
        $propertyMap = include(self::DICTIONARY_PATH . '/PropertyMap.php');
        foreach ($propertyMap as $class => $properties) {
            self::assertArrayHasKey($class, StrId::STRINGS, "Class id $class is not preloaded");
            foreach ($properties as $property => $type) {
                self::assertArrayHasKey($property, StrId::STRINGS, "Property id $property is not preloaded");
                self::assertStringIsParsableType($type);
            }
        }
    }
}
