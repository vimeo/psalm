<?php
namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;
use function strpos;

class PropertyMoveTest extends \Psalm\Tests\TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    public function setUp() : void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new Provider\FakeFileProvider();
    }

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param array<string, string> $properties_to_move
     */
    public function testValidCode(
        string $input_code,
        string $output_code,
        array $properties_to_move
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $config = new TestConfig();

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $input_code
        );

        $codebase = $this->project_analyzer->getCodebase();

        $this->project_analyzer->refactorCodeAfterCompletion($properties_to_move);

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->prepareMigration();

        $codebase->analyzer->updateFile($file_path, false);

        $this->project_analyzer->migrateCode();

        $this->assertSame($output_code, $codebase->getFileContents($file_path));
    }

    /**
     * @return array<string,array{string,string,array<string, string>}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'moveSimpleStaticProperty' => [
                '<?php
                    namespace Ns;

                    use ArrayObject;

                    class A {
                        /** @var ArrayObject<int, int> */
                        public static $foo;
                    }

                    class B {
                        public static function bar() : void {
                            foreach (A::$foo as $f) {}
                        }
                    }',
                '<?php
                    namespace Ns;

                    use ArrayObject;

                    class A {

                    }

                    class B {
                        public static function bar() : void {
                            foreach (B::$fooBar as $f) {}
                        }

                        /** @var ArrayObject<int, int> */
                        public static $fooBar;
                    }',
                [
                    'Ns\A::$foo' => 'Ns\B::$fooBar',
                ],
            ],
            'renameInstanceProperty' => [
                '<?php
                    namespace Ns;

                    class A {
                        /** @var ?int */
                        public $foo;
                    }

                    function foo(A $a) {
                        echo $a->foo;
                        $a->foo = 10;
                    }',
                '<?php
                    namespace Ns;

                    class A {
                        /** @var ?int */
                        public $fooBar;
                    }

                    function foo(A $a) {
                        echo $a->fooBar;
                        $a->fooBar = 10;
                    }',
                [
                    'Ns\A::$foo' => 'Ns\A::$fooBar',
                ],
            ],
            'renameStaticProperty' => [
                '<?php
                    namespace Ns;

                    class A {
                        /** @var ?int */
                        public static $foo;
                    }

                    function foo() {
                        echo A::$foo;
                        A::$foo = 10;
                    }',
                '<?php
                    namespace Ns;

                    class A {
                        /** @var ?int */
                        public static $fooBar;
                    }

                    function foo() {
                        echo A::$fooBar;
                        A::$fooBar = 10;
                    }',
                [
                    'Ns\A::$foo' => 'Ns\A::$fooBar',
                ],
            ],
            'moveStaticProperty' => [
                '<?php
                    namespace Ns;

                    class A {
                        /** @var ?int */
                        public static $foo;
                    }

                    class B {

                    }

                    function foo() {
                        echo A::$foo;
                        A::$foo = 10;
                    }',
                '<?php
                    namespace Ns;

                    class A {

                    }

                    class B {


                        /** @var int|null */
                        public static $fooBar;
                    }

                    function foo() {
                        echo B::$fooBar;
                        B::$fooBar = 10;
                    }',
                [
                    'Ns\A::$foo' => 'Ns\B::$fooBar',
                ],
            ],
        ];
    }
}
