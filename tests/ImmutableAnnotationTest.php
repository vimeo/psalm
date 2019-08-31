<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;

class ImmutableAnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'immutableClassGenerating' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var int */
                        private $a;

                        /** @var string */
                        public $b;

                        public function __construct(int $a, string $b) {
                            $this->a = $a;
                            $this->b = $b;
                        }

                        public function setA(int $a) : self {
                            return new self($this->a, $this->b);
                        }
                    }',
            ],
            'callInternalClassMethod' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var string */
                        public $a;

                        public function __construct(string $a) {
                            $this->a = $a;
                        }

                        public function getA() : string {
                            return $this->a;
                        }

                        public function getHelloA() : string {
                            return "hello" . $this->getA();
                        }
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'immutablePropertyAssignmentInternally' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var int */
                        private $a;

                        /** @var string */
                        public $b;

                        public function __construct(int $a, string $b) {
                            $this->a = $a;
                            $this->b = $b;
                        }

                        public function setA(int $a): void {
                            $this->a = $a;
                        }
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'immutablePropertyAssignmentExternally' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var int */
                        private $a;

                        /** @var string */
                        public $b;

                        public function __construct(int $a, string $b) {
                            $this->a = $a;
                            $this->b = $b;
                        }
                    }

                    $a = new A(4, "hello");

                    $a->b = "goodbye";',
                'error_message' => 'InaccessibleProperty',
            ],
            'callImpureFunction' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var int */
                        private $a;

                        /** @var string */
                        public $b;

                        public function __construct(int $a, string $b) {
                            $this->a = $a;
                            $this->b = $b;
                        }

                        public function bar() : void {
                            header("Location: https://vimeo.com");
                        }
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'callExternalClassMethod' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var string */
                        public $a;

                        public function __construct(string $a) {
                            $this->a = $a;
                        }

                        public function getA() : string {
                            return $this->a;
                        }

                        public function redirectToA() : void {
                            B::setRedirectHeader($this->getA());
                        }
                    }

                    class B {
                        public static function setRedirectHeader(string $s) : void {
                            header("Location: $s");
                        }
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
        ];
    }
}
