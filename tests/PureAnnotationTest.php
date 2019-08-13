<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;

class PureAnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'simplePureFunction' => [
                '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        $a = new A();

                        return null;
                    }',
            ],
            'pureFunctionCallingBuiltinFunctions' => [
                '<?php
                    namespace Bar;

                    /** @psalm-pure */
                    function lower(string $s) : string {
                        return substr(strtolower($s), 0, 10);
                    }',
            ],
            'pureWithStrReplace' => [
                '<?php
                    /** @psalm-pure */
                    function highlight(string $needle, string $output) : string {
                        $needle = preg_quote($needle, \'#\');
                        $needles = str_replace([\'"\', \' \'], [\'\', \'|\'], $needle);
                        $output = preg_replace("#({$needles})#im", "<mark>$1</mark>", $output);

                        return $output;
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'impurePropertyAssignment' => [
                '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        $a->a++;

                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        return null;
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'impureMethodCall' => [
                '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;

                        public function foo() : void {
                            $this->a++;
                        }
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        $a->foo();

                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        return null;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureFunctionCall' => [
                '<?php
                    namespace Bar;

                    function impure() : ?string {
                        /** @var int */
                        static $i = 0;

                        ++$i;

                        return $i % 2 ? "hello" : null;
                    }

                    /** @psalm-pure */
                    function filterOdd(array $a) : void {
                        impure();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'impureConstructorCall' => [
                '<?php
                    namespace Bar;

                    class A {
                        public int $a = 5;
                    }

                    class B {
                        public function __construct(A $a) {
                            $a->a++;
                        }
                    }

                    /** @psalm-pure */
                    function filterOdd(int $i, A $a) : ?int {
                        $b = new B($a);

                        if ($i % 2 === 0 || $a->a === 2) {
                            return $i;
                        }

                        return null;
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
        ];
    }
}
