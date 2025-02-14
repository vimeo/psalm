<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use FilesystemIterator;
use Psalm\Tests\TestCase;
use RegexIterator;

use function is_file;
use function uksort;

/**
 * @psalm-type TCallMap=array<string, array<int|string, string>>
 * @psalm-type TCallMaps=array<int, array<string, array<int|string, string>>>
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
     * @depends testLoadCallMaps
     * @param TCallMaps $callMaps
     */
    public function testSignatureKeysAreZeroOrStringAndValuesAreTypes(array $callMaps): void
    {
        foreach ($callMaps as $callMap) {
            foreach ($callMap as $function => $signature) {
                self::assertArrayKeysAreZeroOrString($signature, "Function " . $function . " in main CallMap has invalid keys");
                self::assertArrayValuesAreStrings($signature, "Function " . $function . " in main CallMap has non-string values");
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
            foreach ($callMap as $function => $signature) {
                foreach ($signature as $type) {
                    self::assertStringIsParsableType($type, "Function " . $function . " in main CallMap contains invalid type declaration " . $type);
                }
            }
        }
    }
}
