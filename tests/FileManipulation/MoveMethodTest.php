<?php
namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;

class MoveMethodTest extends \Psalm\Tests\TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    public function setUp() : void
    {
        FileAnalyzer::clearCache();
        \Psalm\Internal\FileManipulation\FunctionDocblockManipulator::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();
    }

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $input_code
     * @param string $output_code
     * @param array<string, string> $method_migrations
     * @param array<string, string> $call_transforms
     *
     * @return void
     */
    public function testValidCode(
        string $input_code,
        string $output_code,
        array $method_migrations,
        array $call_transforms
    ) {
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

        $codebase->method_migrations = $method_migrations;
        $codebase->call_transforms = $call_transforms;

        $this->project_analyzer->alterCodeAfterCompletion(
            false,
            false
        );

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->prepareMigration();
        $codebase->analyzer->updateFile($file_path, false);

        $this->project_analyzer->migrateCode();

        $this->assertSame($output_code, $codebase->getFileContents($file_path));
    }

    /**
     * @return array<string,array{string,string,array<string, string>,array<string, string>}>
     */
    public function providerValidCodeParse()
    {
        return [
            'moveStaticMethodReferenceOnly' => [
                '<?php
                    class A {
                        const C = 5;

                        /**
                         * @return void
                         */
                        public static function Foo() {
                            echo self::C;
                        }
                    }

                    class B {
                        public static function bar() : void {
                            A::Foo();
                        }
                    }',
                '<?php
                    class A {
                        const C = 5;

                        /**
                         * @return void
                         */
                        public static function Foo() {
                            echo self::C;
                        }
                    }

                    class B {
                        public static function bar() : void {
                            B::Fe();
                        }
                    }',
                [],
                [
                    'a::foo\((.*\))' => 'B::Fe($1)',
                ]
            ],
            'moveEmptyStaticMethodOnly' => [
                '<?php
                    class A {
                        /**
                         * @return void
                         */
                        public static function Foo() : void {}
                    }

                    class B {
                    }',
                '<?php
                    class A {

                    }

                    class B {

                        /**
                         * @return void
                         */
                        public static function Fedcba() : void {}
                    }',
                [
                    'a::foo' => 'B::Fedcba',
                ],
                [
                ]
            ],
            'moveEmptyStaticMethodShorterOnly' => [
                '<?php
                    class A {
                        /**
                         * @return void
                         */
                        public static function Fedbca() : void {}
                    }

                    class B {
                    }',
                '<?php
                    class A {

                    }

                    class B {

                        /**
                         * @return void
                         */
                        public static function Foo() : void {}
                    }',
                [
                    'a::fedbca' => 'B::Foo',
                ],
                [
                ]
            ],
            'moveStaticMethodOnly' => [
                '<?php
                    class A {
                        const C = 5;

                        /**
                         * @return void
                         */
                        public static function Foo() : void {
                            echo self::C;
                        }
                    }

                    class B {
                    }',
                '<?php
                    class A {
                        const C = 5;


                    }

                    class B {

                        /**
                         * @return void
                         */
                        public static function Fedbca() : void {
                            echo A::C;
                        }
                    }',
                [
                    'a::foo' => 'B::Fedbca',
                ],
                []
            ],
            'moveStaticMethodShorterOnly' => [
                '<?php
                    class A {
                        const C = 5;

                        /**
                         * @return void
                         */
                        public static function Fedbca() : void {
                            echo self::C;
                        }
                    }

                    class B {
                    }',
                '<?php
                    class A {
                        const C = 5;


                    }

                    class B {

                        /**
                         * @return void
                         */
                        public static function Foo() : void {
                            echo A::C;
                        }
                    }',
                [
                    'a::fedbca' => 'B::Foo',
                ],
                []
            ],
            'moveStaticMethodAndReferencesFromAbove' => [
                '<?php
                    class A {
                        const C = 5;

                        /**
                         * @return void
                         */
                        public static function Foo() : void {
                            echo self::C;
                        }
                    }

                    class B {
                        public static function bar() : void {
                            A::Foo();
                        }
                    }',
                '<?php
                    class A {
                        const C = 5;


                    }

                    class B {
                        public static function bar() : void {
                            B::Fe();
                        }

                        /**
                         * @return void
                         */
                        public static function Fe() : void {
                            echo A::C;
                        }
                    }',
                [
                    'a::foo' => 'B::Fe',
                ],
                [
                    'a::foo\((.*\))' => 'B::Fe($1)',
                ]
            ],
            'moveStaticMethodAndReferencesFromBelow' => [
                '<?php
                    class B {
                        public static function bar() : void {
                            A::Foo();
                        }
                    }

                    class A {
                        const C = 5;

                        /**
                         * @return void
                         */
                        public static function Foo() : void {
                            echo self::C;
                        }
                    }',
                '<?php
                    class B {
                        public static function bar() : void {
                            B::Fe();
                        }

                        /**
                         * @return void
                         */
                        public static function Fe() : void {
                            echo A::C;
                        }
                    }

                    class A {
                        const C = 5;

                    }',
                [
                    'a::foo' => 'B::Fe',
                ],
                [
                    'a::foo\((.*\))' => 'B::Fe($1)',
                ]
            ]
        ];
    }
}
