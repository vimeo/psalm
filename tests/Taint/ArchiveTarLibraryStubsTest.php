<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stub of stubs/libraries for pear/archive_tar, which Psalm loads because the code below declares classes
 * of the package, as its sources do
 */
final class ArchiveTarLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            class PEAR {}
            class Archive_Tar extends PEAR {}';
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'paths' => [
                '<?php
                    $tar = new Archive_Tar((string) $_GET["archive"]);
                    (new Archive_Tar("backup.tar"))->extract((string) $_GET["directory"]);
                    (new Archive_Tar("backup.tar"))->extract("/tmp/backup");',
                [
                    'TaintedFile on line 2',
                    'TaintedFile on line 3',
                ],
            ],
        ];
    }
}
