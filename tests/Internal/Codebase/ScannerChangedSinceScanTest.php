<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Psalm\Tests\TestCase;

use function getcwd;
use function hash;

use const DIRECTORY_SEPARATOR;

/**
 * The hashes of the files that the workers of the scan pool scanned reach the scanner of the parent process
 * through addThreadData().
 */
final class ScannerChangedSinceScanTest extends TestCase
{
    private const SCANNED_CONTENTS = '<?php final class A {}';

    public function testAFileIsChangedOnceItsContentsDifferFromTheScannedOnes(): void
    {
        $path = (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'changed_since_scan.php';
        $scanner = $this->project_analyzer->getCodebase()->scanner;
        $this->file_provider->registerFile($path, self::SCANNED_CONTENTS);

        $scanner->addThreadData([
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [$path => true],
            [],
            [$path => hash('xxh128', self::SCANNED_CONTENTS)],
            [],
        ]);

        $this->assertFalse($scanner->changedSinceScan($path));

        $this->file_provider->registerFile($path, '<?php final class A { const X = 1; }');
        $this->assertTrue($scanner->changedSinceScan($path));

        $this->file_provider->registerFile($path, self::SCANNED_CONTENTS);
        $this->assertFalse($scanner->changedSinceScan($path));

        $this->file_provider->deleteFile($path);
        $this->assertTrue($scanner->changedSinceScan($path));
    }

    public function testAFileThatWasNotScannedIsChanged(): void
    {
        $this->assertTrue($this->project_analyzer->getCodebase()->scanner->changedSinceScan('/not/scanned.php'));
    }

    public function testTheFilesThatVanishedDuringAWorkersScanReachTheParent(): void
    {
        $scanner = $this->project_analyzer->getCodebase()->scanner;

        $scanner->addThreadData([[], [], [], [], [], [], [], [], [], [], ['/gone/a.php' => true]]);
        $scanner->addThreadData([[], [], [], [], [], [], [], [], [], [], ['/gone/b.php' => true]]);

        $this->assertEquals(['/gone/a.php' => true, '/gone/b.php' => true], $scanner->getVanishedFiles());
    }
}
