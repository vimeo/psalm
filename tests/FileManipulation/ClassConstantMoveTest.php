<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function strpos;

class ClassConstantMoveTest extends TestCase
{
    protected ProjectAnalyzer $project_analyzer;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();
    }

    /**
     * @dataProvider providerValidCodeParse
     * @param array<string, string> $constants_to_move
     */
    public function testValidCode(
        string $input_code,
        string $output_code,
        array $constants_to_move,
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $config = new TestConfig();

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $input_code,
        );

        $codebase = $this->project_analyzer->getCodebase();

        $this->project_analyzer->refactorCodeAfterCompletion($constants_to_move);

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->prepareMigration();

        $codebase->analyzer->updateFile($file_path, false);

        $this->project_analyzer->migrateCode();

        $this->assertSame($output_code, $codebase->getFileContents($file_path));
    }

    /**
     * @return array<string,array{input:string,output:string,migrations:array<string, string>}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'moveSimpleClassConstant' => [
                'input' => '<?php
                    namespace Ns;

                    use ArrayObject;

                    class A {
                        const FOO = 15;
                        const FAA = self::FOO;
                    }

                    class B {
                        public static function bar() : void {
                            echo A::FOO;
                        }
                    }',
                'output' => '<?php
                    namespace Ns;

                    use ArrayObject;

                    class A {

                        const FAA = B::FOO_BAR;
                    }

                    class B {
                        public static function bar() : void {
                            echo B::FOO_BAR;
                        }

                        const FOO_BAR = 15;
                    }',
                'migrations' => [
                    'Ns\A::FOO' => 'Ns\B::FOO_BAR',
                ],
            ],
            'renameSimpleClassConstant' => [
                'input' => '<?php
                    namespace Ns;

                    use ArrayObject;

                    class A {
                        const FOO = 15;
                        const FAA = self::FOO;
                    }

                    class B {
                        public static function bar() : void {
                            echo A::FOO;
                        }
                    }',
                'output' => '<?php
                    namespace Ns;

                    use ArrayObject;

                    class A {
                        const FOO_BAR = 15;
                        const FAA = self::FOO_BAR;
                    }

                    class B {
                        public static function bar() : void {
                            echo A::FOO_BAR;
                        }
                    }',
                'migrations' => [
                    'Ns\A::FOO' => 'Ns\A::FOO_BAR',
                ],
            ],
        ];
    }
}
