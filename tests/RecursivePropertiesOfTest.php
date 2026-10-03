<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Exception;
use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\RecursivePropertiesOfCycleException;
use Psalm\Exception\RecursivePropertiesOfIntersectionException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function str_replace;
use function strpos;
use function strtoupper;
use function substr;

use const PHP_OS;
use const PHP_VERSION_ID;

final class RecursivePropertiesOfTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'recursivePropertiesOfNamedObjects' => [
                'code' => '<?php
                    final class A {
                        function __construct(
                            public B $a,
                        ) {}
                    }

                    final class B {
                        /** @var int */
                        public $b = 0;
                    }

                    /** @var recursive-properties-of<A> $a */
                ',
                'assertions' => [
                    '$a===' => 'array{a: array{b: int}}',
                ],
            ],
            'recursivePropertiesOfArrayLikes' => [
                'code' => '<?php
                    final class A {
                        /** @var int */
                        public $a = 0;
                    }

                    /** @var recursive-properties-of<array<A>> $array */
                    /** @var recursive-properties-of<array{b: A}> $keyed_array */
                    /** @var recursive-properties-of<list<A>> $list */
                    /** @var recursive-properties-of<list{A}> $tuple */
                    /** @var recursive-properties-of<object> $object */
                    /** @var recursive-properties-of<object{b: A}> $object_with_properties */
                ',
                'assertions' => [
                    '$array===' => 'array<array-key, array{a: int}>',
                    '$keyed_array===' => 'array{b: array{a: int}}',
                    '$list===' => 'list<array{a: int}>',
                    '$tuple===' => 'list{array{a: int}}',
                    '$object===' => 'array<never, never>',
                    '$object_with_properties===' => 'array{b: array{a: int}}',
                ],
            ],
            'recursivePropertiesOfUnions' => [
                'code' => '<?php
                    final class A {
                        /** @var int */
                        public $a = 0;
                    }

                    final class B {
                        /** @var int */
                        public $b = 0;
                    }

                    class C {
                        /** @var int */
                        public $a = 0;
                        /** @var int */
                        public $c = 0;
                    }

                    /** @var recursive-properties-of<A|B> $a_or_b */
                    /** @var recursive-properties-of<A|C> $a_or_c */
                    /** @var recursive-properties-of<A|B|C> $a_or_b_or_c */
                ',
                'assertions' => [
                    '$a_or_b' => 'array{a?: int, b?: int}',
                    '$a_or_c' => 'array{a: int, c?: int, ...<string, mixed>}',
                    '$a_or_b_or_c' => 'array{a?: int, b?: int|mixed, c?: int, ...<string, mixed>}',
                ],
            ],
            'recursivePropertiesOfIterable' => [
                'code' => '<?php
                    /** @var recursive-properties-of<iterable> $iterable */
                ',
                'assertions' => [
                    '$iterable===' => 'array<array-key, mixed>',
                ],
            ],
            'recursivePropertiesOfRecursivePropertiesOf' => [
                'code' => '<?php
                    final class A {
                        /** @var int */
                        public $a = 0;
                    }

                    /** @var recursive-properties-of<recursive-properties-of<A>> $a */
                ',
                'assertions' => [
                    '$a===' => 'array{a: int}',
                ],
            ],
            'recursivePropertiesOfOthers' => [
                'code' => '<?php
                    /** @var recursive-properties-of<int> $int */
                    /** @var recursive-properties-of<bool> $bool */
                    /** @var recursive-properties-of<string> $string */
                    /** @var recursive-properties-of<callable(int): bool> $callable */
                    /** @var recursive-properties-of<resource> $resource */
                    /** @var recursive-properties-of<int-mask<1, 2, 4>> $int_mask */
                ',
                'assertions' => [
                    '$int===' => 'int',
                    '$bool===' => 'bool',
                    '$string===' => 'string',
                    '$callable===' => 'callable(int):bool',
                    '$resource===' => 'resource',
                    '$int_mask===' => '0|1|2|3|4|5|6|7',
                ],
            ],
            'recursivePropertiesOfTemplates' => [
                'code' => '<?php
                    final class A {
                        function __construct(
                            public B $a,
                        ) {}
                    }

                    final class B {
                        /** @var int */
                        public $b = 0;
                    }

                    class C {
                        /** @var int */
                        public $c = 0;
                    }

                    /**
                     * @return recursive-properties-of<$a>
                     * @psalm-suppress InvalidReturnType
                     */
                    function f($a) {}

                    /** @var A $a */
                    $namedObject = f($a);
                    /** @var list<B> $a */
                    $list = f($a);
                    /** @var B|C $a */
                    $union = f($a);
                    /** @var iterable $a */
                    $iterable = f($a);
                    /** @var recursive-properties-of<B> $a */
                    $recursive = f($a);
                    /** @var int $a */
                    $int = f($a);
                ',
                'assertions' => [
                    '$namedObject===' => 'array{a: array{b: int}}',
                    '$list===' => 'list<array{b: int}>',
                    '$union===' => 'array{b?: int|mixed, c?: int, ...<string, mixed>}',
                    '$iterable===' => 'array<array-key, mixed>',
                    '$recursive===' => 'array{b: int}',
                    '$int===' => 'int',
                ],
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'onlyOneParam' => [
                'code' => '<?php
                    class A {}

                    class B {}

                    /** @var recursive-properties-of<A, B> $two_params */
                ',
                'error_message' => 'InvalidDocblock',
            ],
        ];
    }

    /**
     * @return iterable<string, array{
     *     code: string,
     *     exception: class-string<Exception>,
     * }>
     */
    public function providerExceptionCodeParse(): iterable
    {
        return [
            'noCyclicTypes' => [
                'code' => '<?php
                    class A {
                        function __construct(
                            public B $a,
                        ) {}
                    }

                    class B {
                        function __construct(
                            public A $b,
                        ) {}
                    }

                    /** @var recursive-properties-of<A> $cyclic */
                ',
                'exception' => RecursivePropertiesOfCycleException::class,
            ],
            'noIntersectionTypes' => [
                'code' => '<?php
                    class A {}

                    class B {}

                    /** @var recursive-properties-of<A&B> $cyclic */
                ',
                'exception' => RecursivePropertiesOfIntersectionException::class,
            ],
        ];
    }

    /**
     * @dataProvider providerExceptionCodeParse
     * @small
     * @param class-string<Exception> $exception
     * @param list<string> $error_levels
     */
    public function testExceptionCode(
        string $code,
        string $exception,
        array  $error_levels = [],
        ?string $php_version = null,
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'PHP80-') !== false) {
            if (PHP_VERSION_ID < 8_00_00) {
                $this->markTestSkipped('Test case requires PHP 8.0.');
            }

            if ($php_version === null) {
                $php_version = '8.0';
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        if ($php_version === null) {
            $php_version = '7.4';
        }

        // sanity check - do we have a PHP tag?
        if (strpos($code, '<?php') === false) {
            $this->fail('Test case must have a <?php tag');
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $code = str_replace("\n", "\r\n", $code);
        }

        foreach ($error_levels as $error_level) {
            $issue_name = $error_level;
            $error_level = Config::REPORT_SUPPRESS;

            Config::getInstance()->setCustomErrorLevel($issue_name, $error_level);
        }

        $this->project_analyzer->setPhpVersion($php_version, 'tests');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->expectException($exception);

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->enterServerMode();
        $codebase->config->visitPreloadedStubFiles($codebase);

        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, new Context());
    }
}
