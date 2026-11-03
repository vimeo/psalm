<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Depends;
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

    public function testReflectedInterfaceMethods(): void
    {
        foreach ([70, 71, 72, 73, 74, 80, 81, 82, 83, 84, 85, 86] as $version) {
            /** @var TCallMap $map */
            $map = require self::DICTIONARY_PATH . '/autogen/CallMap_' . $version . '.php';
            /** @var array<string, string> $extensions */
            $extensions = require self::DICTIONARY_PATH . '/autogen/CallMapExtensions_' . $version . '.php';

            self::assertArrayHasKey('iterator::current', $map);
            self::assertSame(['mixed'], $map['iterator::current']);
            self::assertSame(['mixed'], $map['recursiveiterator::current']);
            self::assertSame('spl', $extensions['recursiveiterator::current']);
            self::assertArrayHasKey('datetimeinterface::format', $map);
            self::assertSame('date', $extensions['datetimeinterface::format']);

            if ($version >= 84) {
                self::assertSame(['int'], $map['datetimeinterface::getmicrosecond']);
                self::assertSame('date', $extensions['datetimeinterface::getmicrosecond']);
            } else {
                self::assertArrayNotHasKey('datetimeinterface::getmicrosecond', $map);
                self::assertArrayNotHasKey('datetimeinterface::getmicrosecond', $extensions);
            }
        }
    }

    public function testMongoDbMonitoringOverridesArePreserved(): void
    {
        foreach ([70, 71, 72, 73, 74, 80, 81, 82, 83, 84, 85, 86] as $version) {
            /** @var TCallMap $map */
            $map = require self::DICTIONARY_PATH . '/CallMap_' . $version . '.php';
            self::assertSame(
                ['void', 'event' => 'MongoDB\\Driver\\Monitoring\\ServerChangedEvent'],
                $map['mongodb\\driver\\monitoring\\sdamsubscriber::serverchanged'] ?? null,
            );
            self::assertSame(
                ['void', 'level' => 'int', 'domain' => 'string', 'message' => 'string'],
                $map['mongodb\\driver\\monitoring\\logsubscriber::log'] ?? null,
            );
        }
    }

    public function testDsLegacyInterfaceOverridesArePreserved(): void
    {
        foreach ([70, 71, 72, 73, 74, 80, 81, 82, 83, 84, 85, 86] as $version) {
            /** @var TCallMap $map */
            $map = require self::DICTIONARY_PATH . '/CallMap_' . $version . '.php';
            self::assertSame('bool', $map['ds\\hashable::equals'][0] ?? null);
            self::assertSame('void', $map['ds\\collection::clear'][0] ?? null);
            self::assertSame('mixed', $map['ds\\sequence::first'][0] ?? null);
        }
    }

    public function testDictionaryPathMustBeAReadableDirectory(): void
    {
        self::assertDirectoryExists(self::DICTIONARY_PATH, self::DICTIONARY_PATH . " is not a valid directory");
        self::assertDirectoryIsReadable(self::DICTIONARY_PATH, self::DICTIONARY_PATH . " is not a readable directory");
    }

    /**
     * @return array<int, TCallMap>
     */
    #[Depends('testDictionaryPathMustBeAReadableDirectory')]
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
     * @param TCallMaps $callMaps
     */
    #[Depends('testLoadCallMaps')]
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
     * @param TCallMaps $callMaps
     */
    #[Depends('testLoadCallMaps')]
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
