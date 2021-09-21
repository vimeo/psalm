<?php
namespace Psalm\Tests;

class IfThisIsTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'worksAfterConvert' => [
                '<?php
                    interface I {
                        /**
                         * @return void
                         */
                        public function test();
                    }

                    class F implements I
                    {
                        /**
                         * @psalm-self-out I
                         * @return void
                         */
                        public function convert() {}

                        /**
                         * @psalm-if-this-is I
                         * @return void
                         */
                        public function test() {}
                    }

                    $f = new F();
                    $f->convert();
                    $f->test();
                '
            ],
            'withTemplate' => [
                '<?php
                class Frozen {}
                class Unfrozen {}

                /**
                 * @template T of Frozen|Unfrozen
                 */
                class Foo
                {
                    /**
                     * @var T
                     */
                    private $state;

                    /**
                     * @param T $state
                     */
                    public function __construct($state)
                    {
                        $this->state = $state;
                    }

                    /**
                     * @param string $name
                     * @param mixed $val
                     * @psalm-if-this-is Foo<Unfrozen>
                     * @return void
                     */
                    public function set($name, $val)
                    {
                    }

                    /**
                     * @return Foo<Frozen>
                     */
                    public function freeze()
                    {
                        /** @var Foo<Frozen> */
                        $f = clone $this;
                        return $f;
                    }
                }

                $f = new Foo(new Unfrozen());
                $f->set("asd", 10);
                '
            ],
            'subclass' => [
                '<?php
                class G
                {
                    /**
                     * @psalm-if-this-is G
                     * @return void
                     */
                    public function test() {}
                }

                class F extends G
                {
                }

                $f = new F();
                $f->test();
                '
            ]
        ];
    }

    /**
     * @return array<string, array{0: string, error_message: string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'blocksWithoutConvert' => [
                '<?php
                    interface I {
                        /**
                         * @return void
                         */
                        public function test();
                    }

                    class F implements I
                    {
                        /**
                         * @psalm-if-this-is I
                         * @return void
                         */
                        public function test() {}
                    }

                    $f = new F();
                    $f->test();
                ',
                'error_message' => 'IfThisIsMismatch'
            ],
            'failsWithWrongTemplate' => [
                '<?php
                class Frozen {}
                class Unfrozen {}

                /**
                 * @template T of Frozen|Unfrozen
                 */
                class Foo
                {
                    /**
                     * @var T
                     */
                    private $state;

                    /**
                     * @param T $state
                     */
                    public function __construct($state)
                    {
                        $this->state = $state;
                    }

                    /**
                     * @param string $name
                     * @param mixed $val
                     * @psalm-if-this-is Foo<Unfrozen>
                     * @return void
                     */
                    public function set($name, $val) {}

                    /**
                     * @return Foo<Frozen>
                     */
                    public function freeze()
                    {
                        /** @var Foo<Frozen> */
                        $f = clone $this;
                        return $f;
                    }
                }

                $f = new Foo(new Unfrozen());
                $f->set("asd", 10);
                $g = $f->freeze();
                $g->set("asd", 20);  // Fails
                ',
                'error_message' => 'IfThisIsMismatch'
            ],
        ];
    }
}
