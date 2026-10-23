<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stubs of stubs/libraries for amphp/amp and amphp/file, which Psalm loads because the code below declares
 * functions of the packages, as their autoloaded files do
 */
final class AmphpLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace Amp {
                function delay(float $timeout, bool $reference = true): void
                {
                }
            }

            namespace Amp\File {
                function read(string $path): string
                {
                    return "";
                }

                function write(string $path, string $contents): void
                {
                }

                function deleteFile(string $path): void
                {
                }
            }';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getReportedIssueTypePrefixes(): array
    {
        return ['Tainted', 'Impure'];
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'fileFunctions' => [
                '<?php
                    $path = (string) $_GET["path"];
                    Amp\File\read($path);
                    Amp\File\write($path, "contents");
                    Amp\File\deleteFile($path);
                    Amp\File\write("/tmp/file", $path);',
                [
                    'TaintedFile on line 3',
                    'TaintedFile on line 4',
                    'TaintedFile on line 5',
                ],
            ],
            'delayCapabilities' => [
                '<?php
                    /** @psalm-capabilities io */
                    function wait(): void {
                        Amp\delay(1.0);
                    }

                    /** @psalm-pure */
                    function waitPurely(): int {
                        Amp\delay(1.0);

                        return 1;
                    }',
                [
                    'ImpureFunctionCall on line 9',
                ],
            ],
        ];
    }
}
