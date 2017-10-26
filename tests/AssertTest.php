<?php
namespace Psalm\Tests;

class AssertTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'SKIPPED-assertInstanceOfB' => [
                '<?php
                    class A {}
                    class B extends A {
                        public function foo() : void {}
                    }

                    function assertInstanceOfB(A $var) : void {
                        if (!$var instanceof B) {
                            throw new \Exception();
                        }
                    }

                    function assertInstanceOfClass(A $var, string $class) : void {
                        if (!$var instanceof $class) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a) : void {
                        assertInstanceOfB($a);
                        $a->foo();
                    }

                    function takesA(A $a) : void {
                        assertInstanceOfB($a);
                        $a->foo();
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [];
    }
}
