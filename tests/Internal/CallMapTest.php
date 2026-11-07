<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Depends;
use Psalm\Tests\TestCase;
use RegexIterator;
use Symfony\Component\Process\Process;

use function array_diff_key;
use function array_fill_keys;
use function array_keys;
use function dirname;
use function file_put_contents;
use function hash_file;
use function is_file;
use function sys_get_temp_dir;
use function tempnam;
use function uksort;
use function unlink;
use function var_export;

use const PHP_BINARY;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;

/**
 * @psalm-type TCallMap=array<string, array<int|string, string>>
 * @psalm-type TCallMaps=array<int, array<string, array<int|string, string>>>
 */
final class CallMapTest extends TestCase
{
    protected const DICTIONARY_PATH = 'dictionaries';

    public function testBuiltinVersionAvailability(): void
    {
        foreach ([70, 71, 72, 73, 74, 80, 81, 82, 83, 84, 85, 86] as $version) {
            foreach (['', 'autogen/'] as $directory) {
                $map = require self::DICTIONARY_PATH . '/' . $directory . 'CallMap_' . $version . '.php';
                foreach (['array_key_first', 'array_key_last'] as $function) {
                    self::assertSame($version >= 73, isset($map[$function]), $directory . $version . ':' . $function);
                }
                if ($version === 71) {
                    self::assertArrayHasKey('swoole_string', $map);
                }
                // These builtins moved between bundled extensions across PHP versions.
                self::assertArrayHasKey('random_int', $map);
                self::assertArrayHasKey('countable::count', $map);
            }
        }
    }

    public function testForeignImportPreservesLocalReturnMetadata(): void
    {
        $local = [
            'native' => [-1 => 'int'],
            'tentative' => [-1 => 'int', -2 => 'tentative'],
            'none' => [0 => 'int', -2 => 'none'],
            'unknown' => [0 => 'int'],
        ];
        $maps = [
            'foreign-map-a' => array_fill_keys(array_keys($local), [0 => 'int']),
            'foreign-map-b' => array_fill_keys(array_keys($local), [0 => 'positive-int', -1 => 'mixed', -2 => 'tentative']),
            'local-map-a' => $local,
            'local-map-b' => $local,
        ];
        $files = [];
        try {
            $args = [PHP_BINARY, dirname(__DIR__, 2) . '/bin/stubs/update_signaturemap_from_other_tool.php'];
            foreach ($maps as $option => $map) {
                $file = tempnam(sys_get_temp_dir(), 'psalm-callmap-');
                self::assertNotFalse($file);
                $files[] = $file;
                file_put_contents($file, '<?php return ' . var_export($map, true) . ';');
                $args[] = '--' . $option . '=' . $file;
            }
            $process = new Process($args);
            $process->run();
            self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $result_file = tempnam(sys_get_temp_dir(), 'psalm-callmap-');
            self::assertNotFalse($result_file);
            $files[] = $result_file;
            file_put_contents($result_file, '<?php return [' . $process->getOutput() . '];');
            /** @var TCallMap $result */
            $result = require $result_file;
            self::assertSame([0 => 'positive-int', -1 => 'int'], $result['native']);
            self::assertSame([0 => 'positive-int', -1 => 'int', -2 => 'tentative'], $result['tentative']);
            self::assertSame([0 => 'positive-int', -2 => 'none'], $result['none']);
            self::assertSame([0 => 'positive-int'], $result['unknown']);
        } finally {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function testGeneratorRejectsMissingRequiredExtensionBeforeWriting(): void
    {
        $map = self::DICTIONARY_PATH . '/autogen/CallMap_' . PHP_MAJOR_VERSION . PHP_MINOR_VERSION . '.php';
        $before = hash_file('sha256', $map);
        $process = new Process([
            PHP_BINARY,
            dirname(__DIR__, 2) . '/bin/stubs/gen_base_callmap.php',
            PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            'psalm_missing_extension',
        ]);
        $process->run();

        self::assertFalse($process->isSuccessful());
        self::assertStringContainsString(
            'Required extension is not loaded: psalm_missing_extension',
            $process->getErrorOutput(),
        );
        self::assertSame($before, hash_file('sha256', $map));
    }

    public function testReturnSignatureProvenance(): void
    {
        foreach (['', 'autogen/'] as $directory) {
            /** @var TCallMap $php80 */
            $php80 = require self::DICTIONARY_PATH . '/' . $directory . 'CallMap_80.php';
            /** @var TCallMap $php81 */
            $php81 = require self::DICTIONARY_PATH . '/' . $directory . 'CallMap_81.php';

            self::assertArrayNotHasKey(-1, $php80['arrayaccess::offsetget']);
            self::assertSame('none', $php80['arrayaccess::offsetget'][-2] ?? null);
            self::assertSame('mixed', $php81['arrayaccess::offsetget'][-1]);
            self::assertSame('tentative', $php81['arrayaccess::offsetget'][-2] ?? null);
            self::assertSame('string', $php80['stringable::__tostring'][-1]);
            self::assertArrayNotHasKey(-2, $php80['stringable::__tostring']);
        }
    }

    public function testReflectedInterfaceMethods(): void
    {
        foreach ([70, 71, 72, 73, 74, 80, 81, 82, 83, 84, 85, 86] as $version) {
            /** @var TCallMap $map */
            $map = require self::DICTIONARY_PATH . '/autogen/CallMap_' . $version . '.php';
            /** @var array<string, string> $extensions */
            $extensions = require self::DICTIONARY_PATH . '/autogen/CallMapExtensions_' . $version . '.php';

            self::assertArrayHasKey('iterator::current', $map);
            self::assertSame('mixed', $map['iterator::current'][0] ?? $map['iterator::current'][-1]);
            self::assertSame('mixed', $map['recursiveiterator::current'][0] ?? $map['recursiveiterator::current'][-1]);
            self::assertSame('spl', $extensions['recursiveiterator::current']);
            self::assertArrayHasKey('datetimeinterface::format', $map);
            self::assertSame('date', $extensions['datetimeinterface::format']);

            if ($version >= 84) {
                self::assertSame('int', $map['datetimeinterface::getmicrosecond'][0] ?? $map['datetimeinterface::getmicrosecond'][-1]);
                self::assertSame('int', $map['datetimeinterface::getmicrosecond'][-1]);
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
            $server_changed = $map['mongodb\\driver\\monitoring\\sdamsubscriber::serverchanged'] ?? [];
            $log = $map['mongodb\\driver\\monitoring\\logsubscriber::log'] ?? [];
            self::assertSame('void', $server_changed[0] ?? $server_changed[-1] ?? null);
            self::assertSame('void', $log[0] ?? $log[-1] ?? null);
            self::assertSame(
                ['event' => 'MongoDB\\Driver\\Monitoring\\ServerChangedEvent'],
                array_diff_key($server_changed, [0 => true, -1 => true, -2 => true]),
            );
            self::assertSame(
                ['level' => 'int', 'domain' => 'string', 'message' => 'string'],
                array_diff_key($log, [0 => true, -1 => true, -2 => true]),
            );
        }
    }

    public function testDsLegacyInterfaceOverridesArePreserved(): void
    {
        foreach ([70, 71, 72, 73, 74, 80, 81, 82, 83, 84, 85, 86] as $version) {
            /** @var TCallMap $map */
            $map = require self::DICTIONARY_PATH . '/CallMap_' . $version . '.php';
            self::assertSame('bool', $map['ds\\hashable::equals'][0] ?? $map['ds\\hashable::equals'][-1] ?? null);
            self::assertSame('void', $map['ds\\collection::clear'][0] ?? $map['ds\\collection::clear'][-1] ?? null);
            self::assertSame('mixed', $map['ds\\sequence::first'][0] ?? $map['ds\\sequence::first'][-1] ?? null);
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
    public function testSignatureKeysAndReturnMetadata(array $callMaps): void
    {
        foreach ($callMaps as $callMap) {
            foreach ($callMap as $function => $signature) {
                self::assertArrayKeysAreZeroOrString(array_diff_key($signature, [-1 => true, -2 => true]), "Function " . $function . " in main CallMap has invalid keys");
                self::assertArrayValuesAreStrings($signature, "Function " . $function . " in main CallMap has non-string values");
                if (isset($signature[-2])) {
                    self::assertContains($signature[-2], ['tentative', 'none'], $function);
                    if ($signature[-2] === 'tentative') {
                        self::assertArrayHasKey(-1, $signature, $function);
                    } else {
                        self::assertArrayNotHasKey(-1, $signature, $function);
                    }
                }
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
                foreach (array_diff_key($signature, [-2 => true]) as $type) {
                    self::assertStringIsParsableType($type, "Function " . $function . " in main CallMap contains invalid type declaration " . $type);
                }
            }
        }
    }
}
