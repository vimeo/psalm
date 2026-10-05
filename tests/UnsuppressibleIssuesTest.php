<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class UnsuppressibleIssuesTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    protected function makeConfig(): Config
    {
        $config = parent::makeConfig();
        $config->disable_suppress_all = false;
        $config->addUnsuppressibleIssue('PossiblyNullReference');
        $config->addUnsuppressibleIssue('TaintedHtml');

        return $config;
    }

    public function testIssueHandlerCantSuppressAnUnsuppressibleIssue(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyNullReference');

        Config::getInstance()->setCustomErrorLevel('PossiblyNullReference', Config::REPORT_SUPPRESS);

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile(
            $file_path,
            '<?php
                function message(?Exception $e): string {
                    return $e->getMessage();
                }',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testSuppressingTaintedInputDoesntHideAnUnsuppressibleTaint(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('TaintedHtml');

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile(
            $file_path,
            '<?php
                /** @psalm-suppress TaintedInput */
                function show(): void {
                    echo (string) $_GET["x"];
                }',
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context());
    }

    public function testSuppressingTaintedInputStillHidesTheOtherTaints(): void
    {
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile(
            $file_path,
            '<?php
                /** @psalm-suppress TaintedInput */
                function run(): void {
                    exec((string) $_GET["x"]);
                }',
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context());
        $this->addToAssertionCount(1);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'otherIssuesStaySuppressible' => [
                'code' => '<?php
                    /** @psalm-suppress PossiblyNullArgument */
                    function length(?string $s): int {
                        return strlen($s);
                    }',
            ],
            'suppressionsPsalmAddsAroundItsOwnCallsStillApply' => [
                'code' => '<?php
                    /** @template-implements ArrayAccess<string, int> */
                    final class Values implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        public function offsetGet(mixed $offset): int {
                            return 1;
                        }

                        public function offsetSet(mixed $offset, mixed $value): void {}

                        public function offsetUnset(mixed $offset): void {}
                    }

                    /** @psalm-suppress PossiblyNullReference */
                    function has(?Values $values): bool {
                        return isset($values["x"]);
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'suppressedOnFunction' => [
                'code' => '<?php
                    /** @psalm-suppress PossiblyNullReference */
                    function message(?Exception $e): string {
                        return $e->getMessage();
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'suppressedOnStatement' => [
                'code' => '<?php
                    function message(?Exception $e): string {
                        /** @psalm-suppress PossiblyNullReference */
                        return $e->getMessage();
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'suppressedOnClass' => [
                'code' => '<?php
                    /** @psalm-suppress PossiblyNullReference */
                    final class Messages {
                        public function message(?Exception $e): string {
                            return $e->getMessage();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'suppressedWithTheIssueItSpecializes' => [
                'code' => '<?php
                    /** @psalm-suppress NullReference */
                    function message(?Exception $e): string {
                        return $e->getMessage();
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'suppressedWithAll' => [
                'code' => '<?php
                    /** @psalm-suppress all */
                    function message(?Exception $e): string {
                        return $e->getMessage();
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
        ];
    }
}
