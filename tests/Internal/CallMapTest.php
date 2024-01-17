<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use FilesystemIterator;
use Psalm\Tests\TestCase;
use RegexIterator;

use function array_diff;
use function array_diff_key;
use function array_intersect;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function is_file;
use function ksort;
use function uksort;

use const DIRECTORY_SEPARATOR;

class CallMapTest extends TestCase
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
     * @return array<string, array{
     *     added: array<string, array<int|string, string>>,
     *     changed: array<string, array{
     *         old: array<int|string, string>,
     *         new: array<int|string, string>
     *     }>,
     *     removed: array<string, array<int|string, string>>
     * }>
     */
    public function testDeltaFilesContainAddedChangedAndRemovedSections(): array
    {
        /** @var iterable<string, string> */
        $deltaFileIterator = new RegexIterator(
            new FilesystemIterator(
                self::DICTIONARY_PATH,
                FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::SKIP_DOTS,
            ),
            '/^CallMap_[\d]{2,}_delta\.php$/i',
            RegexIterator::MATCH,
            RegexIterator::USE_KEY,
        );

        $deltaFiles = [];
        foreach ($deltaFileIterator as $deltaFile => $deltaFilePath) {
            if (!is_file($deltaFilePath)) {
                continue;
            }

            /**
             * @var array{
             *     added: array<string, array<int|string, string>>,
             *     changed: array<string, array{
             *         old: array<int|string, string>,
             *         new: array<int|string, string>
             *     }>,
             *     removed: array<string, array<int|string, string>>
             * }
             */
            $deltaFiles[$deltaFile] = include($deltaFilePath);
        }

        uksort($deltaFiles, 'strnatcasecmp');

        $deltaFiles = [
            'CallMap_historical.php' => [
                'added' => include 'dictionaries/CallMap_historical.php',
                'changed' => [],
                'removed' => [],
            ],
        ] + $deltaFiles;

        foreach ($deltaFiles as $name => $deltaFile) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            self::assertIsArray($deltaFile, "Delta file " . $name . " doesn't contain a readable array");
            self::assertArrayHasKey('added', $deltaFile, "Delta file " . $name . " has no 'added' section");
            self::assertArrayHasKey('changed', $deltaFile, "Delta file " . $name . " has no 'changed' section");
            self::assertArrayHasKey('removed', $deltaFile, "Delta file " . $name . " has no 'removed' section");
            /** @psalm-suppress RedundantCondition */
            self::assertIsArray($deltaFile['added'], "'added' section in Delta file " . $name . " doesn't contain a readable array");
            /** @psalm-suppress RedundantCondition */
            self::assertIsArray($deltaFile['removed'], "'removed' section in Delta file " . $name . " doesn't contain a readable array");
            /** @psalm-suppress RedundantCondition */
            self::assertIsArray($deltaFile['changed'], "'changed' section in Delta file " . $name . " doesn't contain a readable array");
        }

        return $deltaFiles;
    }

    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testDeltaFilesContainAddedChangedAndRemovedSections
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array{
     *     added: array<string, array<int|string, string>>,
     *     changed: array<string, array{
     *         old: array<int|string, string>,
     *         new: array<int|string, string>
     *     }>,
     *     removed: array<string, array<int|string, string>>
     * }> $deltaFiles
     */
    public function testCallmapKeysAreStringsAndValuesAreSignatures(array $mainCallMap, array $deltaFiles): void
    {
        self::assertArrayKeysAreStrings($mainCallMap, "Main CallMap has non-string keys");
        self::assertArrayValuesAreArrays($mainCallMap, "Main CallMap has non-array values");
        foreach ($deltaFiles as $name => $deltaFile) {
            foreach (['added', 'changed', 'removed'] as $section) {
                self::assertArrayKeysAreStrings($deltaFile[$section], "'" . $section . "' in delta file " . $name . " has non-string keys");
                self::assertArrayValuesAreArrays($deltaFile[$section], "'" . $section . "' in delta file " . $name . " has non-array values");
            }
            foreach ($deltaFile['changed'] as $changedFunction => $diff) {
                self::assertArrayKeysAreStrings($diff, "Changed function " . $changedFunction . " in delta file " . $name . " has non-string keys");
                self::assertArrayValuesAreArrays($diff, "Changed function " . $changedFunction . " in delta file " . $name . " has non-array values");
                foreach (['old', 'new'] as $section) {
                    self::assertArrayHasKey($section, $diff, "Changed function " . $changedFunction . " in delta file " . $name . " has no '" . $section . "' section");
                }
            }
        }
    }

    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testDeltaFilesContainAddedChangedAndRemovedSections
     * @depends testCallmapKeysAreStringsAndValuesAreSignatures
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array{
     *     added: array<string, array<int|string, string>>,
     *     changed: array<string, array{
     *         old: array<int|string, string>,
     *         new: array<int|string, string>
     *     }>,
     *     removed: array<string, array<int|string, string>>
     * }> $deltaFiles
     */
    public function testSignatureKeysAreZeroOrStringAndValuesAreTypes(array $mainCallMap, array $deltaFiles): void
    {
        foreach ($mainCallMap as $function => $signature) {
            self::assertArrayKeysAreZeroOrString($signature, "Function " . $function . " in main CallMap has invalid keys");
            self::assertArrayValuesAreStrings($signature, "Function " . $function . " in main CallMap has non-string values");
        }
        foreach ($deltaFiles as $name => $deltaFile) {
            foreach (['added', 'removed'] as $section) {
                foreach ($deltaFile[$section] as $function => $signature) {
                    self::assertArrayKeysAreZeroOrString($signature, "Function " . $function . " in '" . $section . "' of delta file " . $name . " has invalid keys");
                    self::assertArrayValuesAreStrings($signature, "Function " . $function . " in '" . $section . "' of delta file " . $name . " has non-string values");
                }
            }
            foreach ($deltaFile['changed'] as $function => $diff) {
                foreach (['old', 'new'] as $section) {
                    self::assertArrayKeysAreZeroOrString(
                        $diff[$section],
                        "'" . $section . "' function " . $function . " in 'changed' of delta file " . $name . " has invalid keys",
                    );
                    self::assertArrayValuesAreStrings(
                        $diff[$section],
                        "'" . $section . "' function " . $function . " in 'changed' of delta file " . $name . " has non-string values",
                    );
                }
            }
        }
    }

    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testDeltaFilesContainAddedChangedAndRemovedSections
     * @depends testSignatureKeysAreZeroOrStringAndValuesAreTypes
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array{
     *     added: array<string, array<int|string, string>>,
     *     changed: array<string, array{
     *         old: array<int|string, string>,
     *         new: array<int|string, string>
     *     }>,
     *     removed: array<string, array<int|string, string>>
     * }> $deltaFiles
     */
    public function testTypesAreParsable(array $mainCallMap, array $deltaFiles): void
    {
        foreach ($mainCallMap as $function => $signature) {
            foreach ($signature as $type) {
                self::assertStringIsParsableType($type, "Function " . $function . " in main CallMap contains invalid type declaration " . $type);
            }
        }

        foreach ($deltaFiles as $name => $deltaFile) {
            foreach (['added', 'removed'] as $section) {
                foreach ($deltaFile[$section] as $function => $signature) {
                    foreach ($signature as $type) {
                        self::assertStringIsParsableType(
                            $type,
                            "Function " . $function . " in '" . $section . "' of delta file " . $name . " contains invalid type declaration " . $type,
                        );
                    }
                }
            }
            foreach ($deltaFile['changed'] as $function => $diff) {
                foreach (['old', 'new'] as $section) {
                    foreach ($diff[$section] as $type) {
                        self::assertStringIsParsableType(
                            $type,
                            "'" . $section . "' function " . $function . " in 'changed' of delta file " . $name . " contains invalid type declaration " . $type,
                        );
                    }
                }
            }
        }
    }

    /**
     * @depends testDeltaFilesContainAddedChangedAndRemovedSections
     * @depends testCallmapKeysAreStringsAndValuesAreSignatures
     * @param array<string, array{
     *     added: array<string, array<int|string, string>>,
     *     changed: array<string, array{
     *         old: array<int|string, string>,
     *         new: array<int|string, string>
     *     }>,
     *     removed: array<string, array<int|string, string>>
     * }> $deltaFiles
     * @return list<string>
     */
    public function testChangedAndRemovedFunctionsMustExist(array $deltaFiles): array
    {
        $newFunctions = [];
        $deletedFunctions = [];
        foreach ($deltaFiles as $name => $deltaFile) {
            $addedFunctions = array_keys($deltaFile['added']);
            $removedFunctions = array_keys($deltaFile['removed']);
            $nonExistingChangedFunctions = array_diff(array_keys($deltaFile['changed']), $newFunctions);
            $nonExistingRemovedFunctions = array_diff($removedFunctions, $newFunctions);

            self::assertEquals(
                array_values($nonExistingChangedFunctions),
                [],    // Compare against empty array to get handy diff in output
                "Deltafile " . $name . " tries to change non-existing functions",
            );

            self::assertEquals(
                array_values($nonExistingRemovedFunctions),
                [],    // Compare against empty array to get handy diff in output
                "Deltafile " . $name . " tries to remove non-existing functions",
            );

            $newFunctions = array_diff($newFunctions, $removedFunctions);
            $newFunctions = [...$newFunctions, ...$addedFunctions];
            $deletedFunctions = array_diff($deletedFunctions, $addedFunctions);
            $deletedFunctions = [...$deletedFunctions, ...$removedFunctions];
        }
        return $deletedFunctions;
    }

    /**
     * @depends testDeltaFilesContainAddedChangedAndRemovedSections
     * @depends testCallmapKeysAreStringsAndValuesAreSignatures
     * @depends testChangedAndRemovedFunctionsMustExist
     * @param array<string, array{
     *     added: array<string, array<int|string, string>>,
     *     changed: array<string, array{
     *         old: array<int|string, string>,
     *         new: array<int|string, string>
     *     }>,
     *     removed: array<string, array<int|string, string>>
     * }> $deltaFiles
     * @return array<string, array<int|string, string>>
     */
    public function testExistingFunctionsCanNotBeAdded(array $deltaFiles): array
    {
        $newFunctions = [];
        foreach ($deltaFiles as $name => $deltaFile) {
            $alreadyExistingFunctions = array_intersect_key($deltaFile['added'], $newFunctions);

            self::assertEquals(
                array_values($alreadyExistingFunctions),
                [],    // Compare against empty array to get handy diff in output
                "Deltafile " . $name . " adds already existing functions",
            );

            $newFunctions = array_diff_key($newFunctions, $deltaFile['removed']);
            foreach ($deltaFile['changed'] as $function => ['new' => $new]) {
                $newFunctions[$function] = $new;
            }
            $newFunctions = array_merge($newFunctions, $deltaFile['added']);
        }
        return $newFunctions;
    }

    /**
     * @depends testDeltaFilesContainAddedChangedAndRemovedSections
     * @depends testCallmapKeysAreStringsAndValuesAreSignatures
     * @param array<string, array{
     *     added: array<string, array<int|string, string>>,
     *     changed: array<string, array{
     *         old: array<int|string, string>,
     *         new: array<int|string, string>
     *     }>,
     *     removed: array<string, array<int|string, string>>
     * }> $deltaFiles
     */
    public function testFunctionsCanNotBeInMoreThanOneSection(array $deltaFiles): void
    {
        foreach ($deltaFiles as $name => $deltaFile) {
            $addedFunctions = array_keys($deltaFile['added']);
            $changedFunctions = array_keys($deltaFile['changed']);
            $removedFunctions = array_keys($deltaFile['removed']);
            $overlapAddedChanged = array_intersect($addedFunctions, $changedFunctions);
            $overlapAddedRemoved = array_intersect($addedFunctions, $removedFunctions);
            $overlapChangedRemoved = array_intersect($changedFunctions, $removedFunctions);
            self::assertEquals(
                array_values($overlapAddedChanged),
                [],    // Compare against empty array to get handy diff in output
                "Deltafile " . $name . " adds and changes the same functions",
            );
            self::assertEquals(
                array_values($overlapAddedRemoved),
                [],    // Compare against empty array to get handy diff in output
                "Deltafile " . $name . " adds and removes the same functions. Move them to the 'changed' section",
            );
            self::assertEquals(
                array_values($overlapChangedRemoved),
                [],    // Compare against empty array to get handy diff in output
                "Deltafile " . $name . " changes and removes the same function",
            );
        }
    }

    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testExistingFunctionsCanNotBeAdded
     * @depends testCallmapKeysAreStringsAndValuesAreSignatures
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array<int|string,string>> $newFunctions
     */
    public function testFunctionsAddedInDeltaFilesArePresentInMainCallmap(array $mainCallMap, array $newFunctions): array
    {
        $missingNewFunctions = array_diff(array_keys($newFunctions), array_keys($mainCallMap));

        self::assertEquals(
            array_values($missingNewFunctions),
            [],    // Compare against empty array to get handy diff in output
            "Not all functions added in delta files are present in main CallMap file",
        );

        return $newFunctions;
    }

    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testExistingFunctionsCanNotBeAdded
     * @depends testCallmapKeysAreStringsAndValuesAreSignatures
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param array<string, array<int|string,string>> $newFunctions
     */
    public function testFunctionsPresentInMainCallmapAreAddedInDeltaFiles(array $mainCallMap, array $newFunctions): void
    {
        $strayNewFunctions = array_diff(array_keys($mainCallMap), array_keys($newFunctions));

        self::assertEquals(
            [],    // Compare against empty array to get handy diff in output
            array_values($strayNewFunctions),
            "Not all functions present in main CallMap file are added in delta files",
        );
    }

    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testChangedAndRemovedFunctionsMustExist
     * @depends testSignatureKeysAreZeroOrStringAndValuesAreTypes
     * @param array<string, array<int|string,string>> $mainCallMap
     * @param list<string> $removedFunctions
     */
    public function testFunctionsRemovedInDeltaFilesAreAbsentFromMainCallmap(array $mainCallMap, array $removedFunctions): void
    {
        $stillPresentRemovedFunctions  = array_intersect($removedFunctions, array_keys($mainCallMap));

        self::assertEquals(
            [],    // Compare against empty array to get handy diff in output
            array_values($stillPresentRemovedFunctions),
            "Not all functions removed in delta files are absent in main CallMap file",
        );
    }

    /**
     * @depends testMainCallmapFileContainsACallmap
     * @depends testFunctionsAddedInDeltaFilesArePresentInMainCallmap
     * @depends testFunctionsPresentInMainCallmapAreAddedInDeltaFiles
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
            "Signatures in CallMap file don't match most recent signatures in delta files",
        );
    }

    /**
     * @depends testDeltaFilesContainAddedChangedAndRemovedSections
     * @depends testSignatureKeysAreZeroOrStringAndValuesAreTypes
     * @param array<string, array{
     *     added: array<string, array<int|string, string>>,
     *     changed: array<string, array{
     *         old: array<int|string, string>,
     *         new: array<int|string, string>
     *     }>,
     *     removed: array<string, array<int|string, string>>
     * }> $deltaFiles
     */
    public function testOutgoingSignaturesMustMatchMostRecentIncomingSignatures(array $deltaFiles): void
    {
        $deltaFileNames = array_keys($deltaFiles);
        for ($i = count($deltaFileNames) - 1; $i > 0; $i--) {
            $outgoingSignatures = $deltaFiles[$deltaFileNames[$i]]['removed'];
            foreach ($deltaFiles[$deltaFileNames[$i]]['changed'] as $function => ['old' => $old]) {
                $outgoingSignatures[$function] = $old;
            }
            ksort($outgoingSignatures);
            for ($j = $i - 1; $j >= 0; $j--) {
                $incomingSignatures = $deltaFiles[$deltaFileNames[$j]]['added'];
                foreach ($deltaFiles[$deltaFileNames[$j]]['changed'] as $function => ['new' => $new]) {
                    $incomingSignatures[$function] = $new;
                }
                ksort($incomingSignatures);
                $overlapOutgoing = array_intersect_key($outgoingSignatures, $incomingSignatures);
                if (count($overlapOutgoing) !== 0) {
                    $overlapIncoming = array_intersect_key($incomingSignatures, $outgoingSignatures);

                    self::assertEquals(
                        $overlapOutgoing,
                        $overlapIncoming,
                        "Outgoing signatures in " . $deltaFileNames[$i] . " don't match corresponding incoming signatures in " . $deltaFileNames[$j],
                    );

                    // Don't check what has already been matched
                    $outgoingSignatures = array_diff_key($outgoingSignatures, $overlapOutgoing);
                }
            }
        }
    }
}
