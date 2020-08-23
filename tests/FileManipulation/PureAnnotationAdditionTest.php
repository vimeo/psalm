<?php
namespace Psalm\Tests\FileManipulation;

class PureAnnotationAdditionTest extends FileManipulationTest
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse()
    {
        return [
            'addPureAnnotationToFunction' => [
                '<?php
                    function foo(string $s): string {
                        return $s;
                    }',
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s): string {
                        return $s;
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'addPureAnnotationToFunctionWithExistingDocblock' => [
                '<?php
                    /**
                     * @return string
                     */
                    function foo(string $s) {
                        return $s;
                    }',
                '<?php
                    /**
                     * @return string
                     *
                     * @psalm-pure
                     */
                    function foo(string $s) {
                        return $s;
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddPureAnnotationToImpureFunction' => [
                '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddPureAnnotationToMutationFreeMethod' => [
                '<?php
                    class A {
                        public string $foo = "hello";

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }',
                '<?php
                    class A {
                        public string $foo = "hello";

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddPureAnnotationToFunctionWithImpureCall' => [
                '<?php
                    function foo(string $s): string {
                        if (file_exists($s)) {
                            return "";
                        }
                        return $s;
                    }',
                '<?php
                    function foo(string $s): string {
                        if (file_exists($s)) {
                            return "";
                        }
                        return $s;
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddPureAnnotationToFunctionWithImpureClosure' => [
                '<?php
                    /** @param list<string> $arr */
                    function foo(array $arr): array {
                        return array_map($arr, function ($s) { echo $s; return $s;});
                    }',
                '<?php
                    /** @param list<string> $arr */
                    function foo(array $arr): array {
                        return array_map($arr, function ($s) { echo $s; return $s;});
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
        ];
    }
}
