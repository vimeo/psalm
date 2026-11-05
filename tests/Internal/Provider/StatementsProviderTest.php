<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use PHPUnit\Framework\Attributes\TestWith;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Return_;
use Psalm\Config;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Tests\TestCase;

use function extension_loaded;
use function ini_get;
use function ini_set;
use function str_repeat;

use const DIRECTORY_SEPARATOR;

/**
 * A vendor file, which the parser cache doesn't keep, is requested several times, as the analysis of the classes
 * extending it does: each request must get what parsing the file at that moment gives.
 */
final class StatementsProviderTest extends TestCase
{
    private const PHP_VERSION_ID = 8_02_00;

    private FakeFileProvider $files;

    private StatementsProvider $provider;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->files = new FakeFileProvider();
        $this->files->registerFile(self::vendorFilePath(), '<?php class Acme { public function f(): void {} }');
        $this->provider = new StatementsProvider($this->files, new FakeParserCacheProvider());
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testEveryRequestGetsTheStatementsOfTheFile(bool $use_igbinary): void
    {
        self::useIgbinary($use_igbinary);

        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals($this->parse(), $this->getStatements());
        }
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testEveryRequestGetsStatementsOfItsOwn(bool $use_igbinary): void
    {
        self::useIgbinary($use_igbinary);
        $this->getStatements();
        $this->getStatements();

        $third = $this->getStatements();
        $this->assertInstanceOf(Class_::class, $third[0]);
        $third[0]->stmts = [];
        $fourth = $this->getStatements();

        $this->assertNotSame($third[0], $fourth[0]);
        $this->assertEquals($this->parse(), $fourth);
    }

    public function testRequestAfterAChangeGetsTheNewStatements(): void
    {
        $this->getStatements();
        $this->getStatements();

        $this->files->setContents(self::vendorFilePath(), '<?php class Acme { public function g(): void {} }');

        $this->assertEquals($this->parse(), $this->getStatements());
    }

    public function testRequestForAnotherPhpVersionGetsTheStatementsOfThatVersion(): void
    {
        $this->files->setContents(self::vendorFilePath(), '<?php enum Suit {}');
        $this->provider->getStatementsForFile(self::vendorFilePath(), 8_01_00, false);
        $enum = $this->provider->getStatementsForFile(self::vendorFilePath(), 8_01_00, false);

        // as ProjectAnalyzer::setPhpVersion() does
        StatementsProvider::clearParser();
        $stmts = $this->provider->getStatementsForFile(self::vendorFilePath(), 8_00_00, false);

        // enums don't exist before PHP 8.1
        $this->assertNotEquals($enum, $stmts);
        $this->assertEquals($this->parse(8_00_00), $stmts);
    }

    public function testFloatsComeBackExactWhateverTheSerializePrecision(): void
    {
        self::useIgbinary(false);
        $this->files->setContents(self::vendorFilePath(), '<?php return 1.000000000000001;');
        $precision = ini_set('serialize_precision', '14');

        try {
            $this->getStatements();
            $this->getStatements();
            $third = $this->getStatements();

            $this->assertSame('14', ini_get('serialize_precision'));
        } finally {
            ini_set('serialize_precision', (string) $precision);
        }

        $this->assertInstanceOf(Return_::class, $third[0]);
        $this->assertInstanceOf(Float_::class, $third[0]->expr);
        $this->assertSame(1.000_000_000_000_001, $third[0]->expr->value);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testDeeplyNestedStatementsComeBackWhateverTheUnserializeDepthLimit(bool $use_igbinary): void
    {
        self::useIgbinary($use_igbinary);
        $this->files->setContents(self::vendorFilePath(), '<?php $a = "a"' . str_repeat(' . "a"', 120) . ';');
        $max_depth = ini_set('unserialize_max_depth', '16');

        try {
            $this->getStatements();
            $this->getStatements();
            $third = $this->getStatements();
        } finally {
            ini_set('unserialize_max_depth', (string) $max_depth);
        }

        $this->assertEquals($this->parse(), $third);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testStatementsTooDeepToSerializeComeBackToo(bool $use_igbinary): void
    {
        self::useIgbinary($use_igbinary);
        // serializing 10 000 nested concatenations runs out of stack
        $this->files->setContents(self::vendorFilePath(), '<?php $a = "a"' . str_repeat(' . "a"', 10_000) . ';');

        $this->getStatements();
        $this->getStatements();

        // assertEquals() takes minutes to compare trees this deep
        $this->assertTrue($this->parse() == $this->getStatements());
    }

    /**
     * @return list<Stmt>
     */
    private function getStatements(): array
    {
        return $this->provider->getStatementsForFile(self::vendorFilePath(), self::PHP_VERSION_ID, false);
    }

    /**
     * @return list<Stmt>
     */
    private function parse(int $php_version_id = self::PHP_VERSION_ID): array
    {
        $parse_errors = [];
        StatementsProvider::clearParser();

        return StatementsProvider::parseStatements(
            $this->files->getContents(self::vendorFilePath()),
            $php_version_id,
            $parse_errors,
        );
    }

    private static function useIgbinary(bool $use_igbinary): void
    {
        if ($use_igbinary && !extension_loaded('igbinary')) {
            self::markTestSkipped('igbinary is not loaded');
        }

        Config::getInstance()->use_igbinary = $use_igbinary;
    }

    /**
     * Outside the project directories and below a vendor directory: the parser cache doesn't keep this file
     *
     * @psalm-pure
     */
    private static function vendorFilePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'Acme.php';
    }
}
