<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;

final class KeyOfArrayTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testUndefinedClassAndConstantInKeyOfAreReported(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class A {}

                /** @param key-of<Missing::FOO> $k */
                function f($k): void {}

                /** @param key-of<A::FOO> $k */
                function g($k): void {}',
        );

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [
                ['UnresolvableConstant', 4],
                ['UndefinedDocblockClass', 4],
                ['UnresolvableConstant', 7],
                ['UndefinedConstant', 7],
            ],
            array_map(
                static fn(IssueData $issue): array => [$issue->type, $issue->line_from],
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'keyOfListClassConstant' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar"
                        ];
                        /** @return key-of<A::FOO> */
                        public function getKey() {
                            return 0;
                        }
                    }
                ',
            ],
            'keyOfAssociativeArrayClassConstant' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar" => 42
                        ];
                        /** @return key-of<A::FOO> */
                        public function getKey() {
                            return "bar";
                        }
                    }
                ',
            ],
            'allKeysOfAssociativeArrayPossible' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar" => 42,
                            "adams" => 43,
                        ];
                        /** @return key-of<A::FOO> */
                        public function getKey(bool $adams) {
                            if ($adams) {
                                return "adams";
                            }
                            return "bar";
                        }
                    }
                ',
            ],
            'keyOfAsArray' => [
                'code' => '<?php
                    class A {
                        /** @var array */
                        const FOO = [
                            "bar" => 42,
                            "adams" => 43,
                        ];
                        /** @return key-of<self::FOO>[] */
                        public function getKey() {
                            return array_keys(self::FOO);
                        }
                    }
                ',
            ],
            'keyOfArrayLiteral' => [
                'code' => '<?php
                    /**
                     * @return key-of<array<int, string>>
                     */
                    function getKey() {
                        return 32;
                    }
                ',
            ],
            'keyOfUnionArrayLiteral' => [
                'code' => '<?php
                    /**
                     * @return key-of<array<int, string>|array<string, string>>
                     */
                    function getKey(bool $asString) {
                        if ($asString) {
                            return "42";
                        }
                        return 42;
                    }
                ',
            ],
            'keyOfUnionListAndKeyedArray' => [
                'code' => '<?php
                    /**
                     * @return key-of<list<int>|array{a: int, b: int}>
                     */
                    function getKey(bool $asInt) {
                        if ($asInt) {
                            return 42;
                        }
                        return "a";
                    }
                ',
            ],
            'keyOfListArrayLiteral' => [
                'code' => '<?php
                    /**
                     * @return key-of<list<string>>
                     */
                    function getKey() {
                        return 42;
                    }
                ',
            ],
            'keyOfStringArrayConformsToString' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function getKey2() {
                        /** @var key-of<array<string, string>>[] */
                        $keys2 = ["foo"];
                        return $keys2[0];
                    }
                ',
            ],
            'keyOfExpandsPropertiesOf' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        public $foo = false;
                        /** @var string */
                        private $bar = "";
                        /** @var int */
                        protected $adams = 42;
                    }

                    /** @return list<key-of<properties-of<A>>> */
                    function returnPropertyOfA() {
                        return ["foo", "bar", "adams"];
                    }
                ',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'onlyDefinedKeysOfAssociativeArray' => [
                'code' => '<?php
                    class A {
                        const FOO = [
                            "bar" => 42
                        ];
                        /** @return key-of<A::FOO> */
                        public function getKey() {
                            return "adams";
                        }
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'keyOfArrayLiteral' => [
                'code' => '<?php
                    class A {
                        /**
                         * @return key-of<array<int, string>>
                         */
                        public function getKey() {
                            return "foo";
                        }
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'onlyIntAllowedForKeyOfList' => [
                'code' => '<?php
                    class A {
                        /**
                         * @return key-of<list<string>>
                         */
                        public function getKey() {
                            return "42";
                        }
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'noStringAllowedInKeyOfIntFloatStringArray' => [
                'code' => '<?php
                    /**
                     * @return key-of<array<int, string>|array<"42.0", string>>
                     */
                    function getKey(bool $asInt) {
                        if ($asInt) {
                            return 42;
                        }
                        return "42";
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
            'noLiteralCAllowedInKeyOfUnionListAndKeyedArray' => [
                'code' => '<?php
                    /**
                     * @return key-of<list<int>|array{a: int, b: int}>
                     */
                    function getKey() {
                        return "c";
                    }
                ',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
