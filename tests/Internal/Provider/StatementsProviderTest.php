<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

final class StatementsProviderTest extends TestCase
{
    private const PHP_VERSION_ID = 8_02_00;

    private const PARSING_MESSAGE = 'because we cannot use cache';

    private const CONTENTS = '<?php class Acme { public function f(): void {} }';

    public function testVendorFileIsParsedOnlyOnce(): void
    {
        $provider = new StatementsProvider(self::vendorFileProvider(), new FakeParserCacheProvider());
        $progress = new RecordingProgress();

        $first = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);
        $second = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);

        $this->assertSame(1, $progress->countDebugMessagesContaining(self::PARSING_MESSAGE));
        $this->assertEquals($first, $second);
    }

    public function testMemoisedVendorStatementsAreNotSharedBetweenCallers(): void
    {
        $provider = new StatementsProvider(self::vendorFileProvider(), new FakeParserCacheProvider());

        $first = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false);
        $second = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false);

        $this->assertNotSame($first[0], $second[0]);
    }

    public function testChangedVendorFileIsParsedAgain(): void
    {
        $files = self::vendorFileProvider();
        $provider = new StatementsProvider($files, new FakeParserCacheProvider());
        $progress = new RecordingProgress();

        $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);
        $files->setContents(self::vendorFilePath(), '<?php class Acme { public function g(): void {} }');
        $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);

        $this->assertSame(2, $progress->countDebugMessagesContaining(self::PARSING_MESSAGE));
    }

    public function testStatementsAreNotMemoisedWithoutAParserCacheProvider(): void
    {
        $provider = new StatementsProvider(self::vendorFileProvider());
        $progress = new RecordingProgress();

        $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);
        $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);

        $this->assertSame(2, $progress->countDebugMessagesContaining(self::PARSING_MESSAGE));
    }

    /**
     * A path the parser cache provider refuses to store: outside the project dirs and below a vendor directory.
     *
     * @psalm-pure
     */
    private static function vendorFilePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'Acme.php';
    }

    private static function vendorFileProvider(): FakeFileProvider
    {
        $files = new FakeFileProvider();
        $files->registerFile(self::vendorFilePath(), self::CONTENTS);

        return $files;
    }
}
