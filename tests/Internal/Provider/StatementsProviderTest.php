<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Amp\Serialization\SerializationException;
use Amp\Serialization\Serializer;
use Override;
use PhpParser\PrettyPrinter\Standard;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Tests\TestCase;
use ReflectionProperty;

use const DIRECTORY_SEPARATOR;

final class StatementsProviderTest extends TestCase
{
    private const PHP_VERSION_ID = 8_02_00;

    private const PARSING_MESSAGE = 'because we cannot use cache';

    private const CONTENTS = '<?php class Acme { public function f(): void {} }';

    #[Override]
    public function tearDown(): void
    {
        (new ReflectionProperty(StatementsProvider::class, 'serializer'))->setValue(null, null);

        parent::tearDown();
    }

    public function testVendorFileIsMemoisedOnItsSecondRequest(): void
    {
        $provider = new StatementsProvider(self::vendorFileProvider(), new FakeParserCacheProvider());
        $progress = new RecordingProgress();

        $first = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);
        $second = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);
        $third = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);
        $fourth = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);

        $this->assertSame(2, $progress->countDebugMessagesContaining(self::PARSING_MESSAGE));
        $this->assertEquals($first, $second);
        $this->assertEquals($first, $third);
        $this->assertEquals($first, $fourth);
    }

    public function testMemoisedVendorStatementsAreNotSharedBetweenCallers(): void
    {
        $provider = new StatementsProvider(self::vendorFileProvider(), new FakeParserCacheProvider());

        $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false);
        $second = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false);
        $third = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false);
        $fourth = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false);

        $this->assertNotSame($second[0], $third[0]);
        $this->assertNotSame($third[0], $fourth[0]);
    }

    public function testChangedVendorFileIsParsedAgain(): void
    {
        $files = self::vendorFileProvider();
        $provider = new StatementsProvider($files, new FakeParserCacheProvider());
        $progress = new RecordingProgress();

        $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);
        $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);
        $files->setContents(self::vendorFilePath(), '<?php class Acme { public function g(): void {} }');
        $stmts = $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);

        $this->assertSame(3, $progress->countDebugMessagesContaining(self::PARSING_MESSAGE));
        $this->assertStringContainsString('function g(', (new Standard())->prettyPrint($stmts));
    }

    public function testVendorFileThatCannotBeUnserializedIsParsedWithoutMemoisingItAgain(): void
    {
        $serializer = new class implements Serializer {
            public int $serialized = 0;

            #[Override]
            public function serialize(mixed $data): string
            {
                $this->serialized++;

                return 'x';
            }

            #[Override]
            public function unserialize(string $data): never
            {
                throw new SerializationException('Maximum depth exceeded');
            }
        };
        (new ReflectionProperty(StatementsProvider::class, 'serializer'))->setValue(null, $serializer);

        $provider = new StatementsProvider(self::vendorFileProvider(), new FakeParserCacheProvider());
        $progress = new RecordingProgress();

        for ($i = 0; $i < 5; $i++) {
            $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);
        }

        $this->assertSame(5, $progress->countDebugMessagesContaining(self::PARSING_MESSAGE));
        $this->assertSame(1, $serializer->serialized);
    }

    public function testStatementsAreNotMemoisedWithoutAParserCacheProvider(): void
    {
        $provider = new StatementsProvider(self::vendorFileProvider());
        $progress = new RecordingProgress();

        for ($i = 0; $i < 3; $i++) {
            $provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false, $progress);
        }

        $this->assertSame(3, $progress->countDebugMessagesContaining(self::PARSING_MESSAGE));
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
