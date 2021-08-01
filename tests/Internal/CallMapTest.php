<?php
namespace Psalm\Tests\Internal;

use FilesystemIterator;
use RegexIterator;
use Throwable;

use function array_diff;
use function array_diff_key;
use function array_filter;
use function array_intersect;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function is_file;
use function is_string;
use function ksort;
use function uksort;

use const ARRAY_FILTER_USE_KEY;
use const DIRECTORY_SEPARATOR;

class CallMapTest extends \Psalm\Tests\TestCase
{
    protected const DICTIONARY_PATH = 'dictionaries';
    
    public function testDictionaryPathMustBeAReadableDirectory(): void
    {
        self::assertDirectoryExists(self::DICTIONARY_PATH, self::DICTIONARY_PATH . " is not a valid directory");
        self::assertDirectoryIsReadable(self::DICTIONARY_PATH, self::DICTIONARY_PATH . " is not a readable directory");
    }
    
    /**
     * @depends testDictionaryPathMustBeAReadableDirectory
     */
    public function testMainCallmapFileExists(): string
    {
        $callMapFilePath = self::DICTIONARY_PATH . DIRECTORY_SEPARATOR . 'CallMap.php';
        
        self::assertFileExists($callMapFilePath, "Main CallMap " . $callMapFilePath . " file not found");

        return $callMapFilePath;
    }
    
    /**
     * @depends testMainCallmapFileExists
     * @return array<string, array<int|string,string>>
     */
    public function testMainCallmapFileContainsACallmap(string $callMapFilePath): array
    {
        /**
         * @var array<string, array<int|string,string>>
         * @psalm-suppress UnresolvableInclude
         */
        $mainCallMap = include($callMapFilePath);
        
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        self::assertIsArray($mainCallMap, "Main CallMap file " . $callMapFilePath . " does not contain a readable array");
        return $mainCallMap;
    }
    
    /**
     * @depends testDictionaryPathMustBeAReadableDirectory
     * @return array<string, array<string, array<string, array<int|string, string>>>>
     */
    public function testDeltaFilesContainOldAndNewCallmaps(): array
    {
        /** @var iterable<string, string> */
        $deltaFileIterator = new RegexIterator(
            new FilesystemIterator(
                self::DICTIONARY_PATH,
                FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::SKIP_DOTS
            ),
            '/^CallMap_[\d]{2,}_delta\.php$/i',
            RegexIterator::MATCH,
            RegexIterator::USE_KEY
        );
        
        $deltaFiles = [];
        foreach ($deltaFileIterator as $deltaFile => $deltaFilePath) {
            if (!is_file($deltaFilePath)) {
                continue;
            }

            /** @var array<string, array<string, array<int|string, string>>> */
            $deltaFiles[$deltaFile] = include($deltaFilePath);
        }
        
        uksort($deltaFiles, 'strnatcasecmp');
        
        foreach ($deltaFiles as $name => $deltaFile) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            self::assertIsArray($deltaFile, "Delta file " . $name . " doesn't contain a readable array");
            self::assertArrayHasKey('old', $deltaFile, "Delta file " . $name . " has no 'old' section");
            self::assertArrayHasKey('new', $deltaFile, "Delta file " . $name . " has no 'new' section");
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            self::assertIsArray($deltaFile['old'], "'Old' section in Delta file " . $name . " doesn't contain a readable array");
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            self::assertIsArray($deltaFile['new'], "'New' section in Delta file " . $name . " doesn't contain a readable array");
        }
        
        return $deltaFiles;
    }

    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testDeltaFilesContainOldAndNewCallmaps
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array<string, array<string, array<int|string, string>>>> $deltaFiles
     */
    public function testCallmapKeysAreStringsAndValuesAreSignatures(array $mainCallMap, array $deltaFiles): void
    {
        self::assertArrayKeysAreStrings($mainCallMap, "Main CallMap has non-string keys");
        self::assertArrayValuesAreArrays($mainCallMap, "Main CallMap has non-array values");
        foreach ($deltaFiles as $name => $deltaFile) {
            foreach (['old', 'new'] as $section) {
                self::assertArrayKeysAreStrings($deltaFile[$section], "'" . $section . "' in delta file " . $name . " has non-string keys");
                self::assertArrayValuesAreArrays($deltaFile[$section], "'" . $section . "' in delta file " . $name . " has non-array values");
            }
        }
    }
    
    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testDeltaFilesContainOldAndNewCallmaps
     * @depends testCallmapKeysAreStringsAndValuesAreSignatures
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array<string, array<string, array<int|string, string>>>> $deltaFiles
     */
    public function testSignatureKeysAreZeroOrStringAndValuesAreTypes(array $mainCallMap, array $deltaFiles): void
    {
        foreach ($mainCallMap as $function => $signature) {
            self::assertArrayKeysAreZeroOrString($signature, "Function " . $function . " in main CallMap has invalid keys");
            self::assertArrayValuesAreStrings($signature, "Function " . $function . " in main CallMap has non-string values");
        }
        foreach ($deltaFiles as $name => $deltaFile) {
            foreach (['old', 'new'] as $section) {
                foreach ($deltaFile[$section] as $function => $signature) {
                    self::assertArrayKeysAreZeroOrString($signature, "Function " . $function . " in '" . $section . "' of delta file " . $name . " has invalid keys");
                    self::assertArrayValuesAreStrings($signature, "Function " . $function . " in '" . $section . "' of delta file " . $name . " has non-string values");
                }
            }
        }
    }
    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testDeltaFilesContainOldAndNewCallmaps
     * @depends testSignatureKeysAreZeroOrStringAndValuesAreTypes
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array<string, array<string, array<int|string, string>>>> $deltaFiles
     */
    public function testTypesAreParsable(array $mainCallMap, array $deltaFiles): void
    {
        foreach ($mainCallMap as $function => $signature) {
            foreach ($signature as $type) {
                self::assertStringIsParsableType($type, "Function " . $function . " in main CallMap contains invalid type declaration " . $type);
            }
        }
        
        foreach ($deltaFiles as $name => $deltaFile) {
            foreach (['old', 'new'] as $section) {
                foreach ($deltaFile[$section] as $function => $signature) {
                    foreach ($signature as $type) {
                        self::assertStringIsParsableType(
                            $type,
                            "Function " . $function . " in '" . $section . "' of delta file " . $name . " contains invalid type declaration " . $type
                        );
                    }
                }
            }
        }
    }
    
    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testDeltaFilesContainOldAndNewCallmaps
     * @depends testSignatureKeysAreZeroOrStringAndValuesAreTypes
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array<string, array<string, array<int|string, string>>>> $deltaFiles
     * @return array<string, array<int|string,string>>
     */
    public function testFunctionsAddedInDeltaFilesArePresentInMainCallmap(array $mainCallMap, array $deltaFiles): array
    {
        $newFunctions = [];
        foreach ($deltaFiles as $deltaFile) {
            $removedInDelta = array_diff_key($deltaFile['old'], $deltaFile['new']);
            $newFunctions = array_diff_key($newFunctions, $removedInDelta);
            $newFunctions = array_merge($newFunctions, $deltaFile['new']);
        }
            
        $missingNewFunctions = array_diff(array_keys($newFunctions), array_keys($mainCallMap));
        
        self::assertEquals(
            array_values($missingNewFunctions),
            [],    // Compare against empty array to get handy diff in output
            "Not all functions added in delta files are present in main CallMap file"
        );
        
        return $newFunctions;
    }
    
      /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testDeltaFilesContainOldAndNewCallmaps
     * @depends testSignatureKeysAreZeroOrStringAndValuesAreTypes
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array<string, array<string, array<int|string, string>>>> $deltaFiles
     */
    public function testFunctionsRemovedInDeltaFilesAreAbsentFromMainCallmap(array $mainCallMap, array $deltaFiles): void
    {
        $removedFunctions = [];
        foreach ($deltaFiles as $deltaFile) {
            $addedInDelta = array_diff(array_keys($deltaFile['new']), array_keys($deltaFile['old']));
            $removedInDelta = array_diff(array_keys($deltaFile['old']), array_keys($deltaFile['new']));
            $removedFunctions = array_diff($removedFunctions, $addedInDelta);
            $removedFunctions = array_merge($removedFunctions, $removedInDelta);
        }
            
        $stillPresentRemovedFunctions  = array_intersect($removedFunctions, array_keys($mainCallMap));
        
        self::assertEquals(
            [],    // Compare against empty array to get handy diff in output
            array_values($stillPresentRemovedFunctions),
            "Not all functions removed in delta files are absent in main CallMap file"
        );
    }
    
    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testFunctionsAddedInDeltaFilesArePresentInMainCallmap
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array<int|string,string>> $newFunctions
     */
    public function testMainCallmapSignaturesMustMatchMostRecentIncomingSignatures(array $mainCallMap, array $newFunctions): void
    {
        $existingFunctions = array_intersect_key($mainCallMap, $newFunctions);
        ksort($existingFunctions);
        ksort($newFunctions);
        
        self::assertEquals(
            $newFunctions,
            $existingFunctions,
            "Signatures in CallMap file don't match most recent signatures in delta files"
        );
    }
    
    /**
     * @depends testDeltaFilesContainOldAndNewCallmaps
     * @depends testSignatureKeysAreZeroOrStringAndValuesAreTypes
     * @param array<string, array<string, array<string, array<int|string, string>>>> $deltaFiles
     */
    public function testOutgoingSignaturesMustMatchMostRecentIncomingSignatures(array $deltaFiles): void
    {
        $deltaFileNames = array_keys($deltaFiles);
        for ($i = count($deltaFileNames) - 1; $i > 0; $i--) {
            $outgoingSignatures = $deltaFiles[$deltaFileNames[$i]]['old'];
            ksort($outgoingSignatures);
            for ($j = $i - 1; $j >= 0; $j--) {
                $incomingSignatures = $deltaFiles[$deltaFileNames[$j]]['new'];
                ksort($incomingSignatures);
                $overlapOutgoing = array_intersect_key($outgoingSignatures, $incomingSignatures);
                if (count($overlapOutgoing) !== 0) {
                    $overlapIncoming = array_intersect_key($incomingSignatures, $outgoingSignatures);
                    
                    self::assertEquals(
                        $overlapOutgoing,
                        $overlapIncoming,
                        "Outgoing signatures in " . $deltaFileNames[$i] . " don't match corresponding incoming signatures in " . $deltaFileNames[$j]
                    );
                    
                    // Don't check what has already been matched
                    $outgoingSignatures = array_diff_key($outgoingSignatures, $overlapOutgoing);
                }
            }
        }
    }
    
    public static function assertArrayKeysAreStrings(array $array, string $message = ''): void
    {
        $validKeys = array_filter($array, 'is_string', ARRAY_FILTER_USE_KEY);
        self::assertTrue(count($array) === count($validKeys), $message);
    }
    public static function assertArrayKeysAreZeroOrString(array $array, string $message = ''): void
    {
        $isZeroOrString = /** @param mixed $key */ function ($key): bool {
            return $key === 0 || is_string($key);
        };
        $validKeys = array_filter($array, $isZeroOrString, ARRAY_FILTER_USE_KEY);
        self::assertTrue(count($array) === count($validKeys), $message);
    }
    
    public static function assertArrayValuesAreArrays(array $array, string $message = ''): void
    {
        $validValues = array_filter($array, 'is_array');
        self::assertTrue(count($array) === count($validValues), $message);
    }
    
    public static function assertArrayValuesAreStrings(array $array, string $message = ''): void
    {
        $validValues = array_filter($array, 'is_string');
        self::assertTrue(count($array) === count($validValues), $message);
    }
    
    public static function assertStringIsParsableType(string $type, string $message = ''): void
    {
        if ($type === '') {
            //    Ignore empty types for now, as these are quite common for pecl libraries
            self::assertTrue(true);
        } else {
            $union = null;
            try {
                $tokens = \Psalm\Internal\Type\TypeTokenizer::tokenize($type);
                $union = \Psalm\Internal\Type\TypeParser::parseTokens($tokens);
            } catch (Throwable $_e) {
            }
            self::assertInstanceOf(\Psalm\Type\Union::class, $union, $message);
        }
    }
}
