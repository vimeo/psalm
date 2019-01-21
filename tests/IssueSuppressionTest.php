<?php
namespace Psalm\Tests;

class IssueSuppressionTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'undefinedClass' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress UndefinedClass
                         * @psalm-suppress MixedMethodCall
                         * @psalm-suppress MissingReturnType
                         */
                        public function b() {
                            B::fooFoo()->barBar()->bat()->baz()->bam()->bas()->bee()->bet()->bes()->bis();
                        }
                    }',
            ],
            'undefinedClassOneLine' => [
                '<?php
                    class A {
                        public function b(): void {
                            /**
                             * @psalm-suppress UndefinedClass
                             */
                            new B();
                        }
                    }',
            ],
            'undefinedClassOneLineInFile' => [
                '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    new B();',
            ],
            'excludeIssue' => [
                '<?php
                    fooFoo();',
                'assertions' => [],
                'error_levels' => ['UndefinedFunction'],
            ],
            'crossClosureBoundariesOnFunction' => [
                '<?php
                    /**
                     * @psalm-suppress MissingClosureParamType
                     * @psalm-suppress MissingClosureReturnType
                     */
                    function foo(array $bar): array {
                        return array_map(
                            function ($value) {
                                return (string)$value;
                            },
                            $bar
                        );
                    }'
            ],
            'crossClosureBoundariesOnReturn' => [
                '<?php
                    function bar(array $bar): array {
                        /**
                         * @psalm-suppress MissingClosureParamType
                         * @psalm-suppress MissingClosureReturnType
                         */
                        return array_map(
                            function ($value) {
                                return (string)$value;
                            },
                            $bar
                        );
                    }'
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'undefinedClassOneLineWithLineAfter' => [
                '<?php
                    class A {
                        public function b() {
                            /**
                             * @psalm-suppress UndefinedClass
                             */
                            new B();
                            new C();
                        }
                    }',
                'error_message' => 'UndefinedClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:8 - Class or interface C',
            ],
            'undefinedClassOneLineInFileAfter' => [
                '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    new B();
                    new C();',
                'error_message' => 'UndefinedClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:6 - Class or interface C',
            ],
        ];
    }
}
