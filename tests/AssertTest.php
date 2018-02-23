<?php
namespace Psalm\Tests;

class AssertTest extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'assertInstanceOfB' => [
                '<?php
                    class A {}
                    class B extends A {
                        public function foo(): void {}
                    }

                    function assertInstanceOfB(A $var): void {
                        if (!$var instanceof B) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        assertInstanceOfB($a);
                        $a->foo();
                    }',
            ],
            'assertInstanceOfBInClassMethod' => [
                '<?php
                    class A {}
                    class B extends A {
                        public function foo(): void {}
                    }

                    class C {
                        private function assertInstanceOfB(A $var): void {
                            if (!$var instanceof B) {
                                throw new \Exception();
                            }
                        }

                        private function takesA(A $a): void {
                            $this->assertInstanceOfB($a);
                            $a->foo();
                        }
                    }',
            ],
            'assertPropertyNotNull' => [
                '<?php
                    class A {
                        public function foo(): void {}
                    }

                    class B {
                        /** @var A|null */
                        public $a;

                        private function assertNotNullProperty(): void {
                            if (!$this->a) {
                                throw new \Exception();
                            }
                        }

                        public function takesA(A $a): void {
                            $this->assertNotNullProperty();
                            $a->foo();
                        }
                    }',
            ],
            'SKIPPED-assertInstanceOfClass' => [
                '<?php
                    class A {}
                    class B extends A {
                        public function foo(): void {}
                    }

                    function assertInstanceOfClass(A $var, string $class): void {
                        if (!$var instanceof $class) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        assertInstanceOfClass($a, B::class);
                        $a->foo();
                    }',
            ],
        ];
    }
}
