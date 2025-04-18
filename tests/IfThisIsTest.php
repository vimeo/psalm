<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class IfThisIsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'worksAfterConvert' => [
                'code' => '<?php
                    interface I {
                        /**
                         * @return void
                         */
                        public function test();
                    }

                    class F implements I
                    {
                        /**
                         * @psalm-this-out I
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
                ',
            ],
            'withTemplate' => [
                'code' => '<?php
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
                ',
            ],
            'subclass' => [
                'code' => '<?php
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
                ',
            ],
            'ifThisIsWithSelfAlias' => [
                'code' => '<?php
                    /**
                     * @template T of string
                     */
                    final class App
                    {
                        /**
                         * @psalm-if-this-is self<"idle">
                         * @psalm-this-out self<"started">
                         */
                        public function start(): void
                        {
                            throw new RuntimeException("???");
                        }
                    }

                    /** @var App<"idle"> */
                    $app = new App();
                    $app->start();
                ',
            ],
            'ifThisIsAndThisOutAtTheSameTime' => [
                'code' => '<?php
                    /**
                     * @template T of string
                     */
                    final class App
                    {
                        /**
                         * @psalm-if-this-is App<"idle">
                         * @psalm-this-out App<"started">
                         */
                        public function start(): void
                        {
                            throw new RuntimeException("???");
                        }
                    }

                    /** @var App<"idle"> */
                    $app = new App();
                    $app->start();
                ',
            ],
            'ifThisIsChangeThisTypeInsideMethod' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    final class Option
                    {
                        /**
                         * @return T|null
                         */
                        public function unwrap()
                        {
                            throw new RuntimeException("???");
                        }
                    }

                    /**
                     * @template T
                     */
                    final class ArrayList
                    {
                        /** @var list<T> */
                        private $items;

                        /**
                         * @param list<T> $items
                         */
                        public function __construct(array $items)
                        {
                            $this->items = $items;
                        }

                        /**
                         * @psalm-if-this-is ArrayList<Option<int>>
                         * @return ArrayList<int>
                         */
                        public function compact(): ArrayList
                        {
                            $values = [];

                            foreach ($this->items as $item) {
                                $value = $item->unwrap();

                                if (null !== $value) {
                                    $values[] = $value;
                                }
                            }

                            return new self($values);
                        }
                    }

                    /** @var ArrayList<Option<int>> $list */
                    $list = new ArrayList([]);
                    $numbers = $list->compact();
                ',
                'assertions' => [
                    '$numbers' => 'ArrayList<int>',
                ],
            ],
            'ifThisIsResolveTemplateParams' => [
                'code' => '<?php
                    /**
                     * @template-covariant T
                     */
                    final class Option
                    {
                        /** @return T|null */
                        public function unwrap() { throw new RuntimeException("???"); }
                    }

                    /**
                     * @template-covariant L
                     * @template-covariant R
                     */
                    final class Either
                    {
                        /** @return R|null */
                        public function unwrap() { throw new RuntimeException("???"); }
                    }

                    /**
                     * @template T
                     */
                    final class ArrayList
                    {
                        /** @var list<T> */
                        private $items;

                        /**
                         * @param list<T> $items
                         */
                        public function __construct(array $items)
                        {
                            $this->items = $items;
                        }

                        /**
                         * @template A
                         * @template B
                         * @template TOption of Option<A>
                         * @template TEither of Either<mixed, B>
                         *
                         * @psalm-if-this-is ArrayList<TOption|TEither>
                         * @return ArrayList<A|B>
                         */
                        public function compact(): ArrayList
                        {
                            $values = [];

                            foreach ($this->items as $item) {
                                $value = $item->unwrap();

                                if (null !== $value) {
                                    $values[] = $value;
                                }
                            }

                            return new self($values);
                        }
                    }

                    /** @var ArrayList<Either<Exception, int>|Option<int>> $list */
                    $list = new ArrayList([]);
                    $numbers = $list->compact();
                ',
                'assertions' => [
                    '$numbers' => 'ArrayList<int>',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{code: string, error_message: string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'failsWithWrongTemplate1' => [
                'code' => '<?php

                /**
                 * @template T
                 */
                class a {
                    /**
                     * @var T
                     */
                    private $data;
                    /**
                     * @param T $data
                     */
                    public function __construct($data) {
                        $this->data = $data;
                    }
                    /**
                     * @psalm-if-this-is a<int>
                     */
                    public function test(): void {
                    }
                }

                $i = new a("test");
                $i->test();
                ',
                'error_message' => 'IfThisIsMismatch',
            ],
            'failsWithWrongTemplate2' => [
                'code' => '<?php
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
                'error_message' => 'IfThisIsMismatch',
            ],
            'failWithInvalidTemplateConstraint' => [
                'code' => '<?php
                    /** @template T */
                    final class Option { }

                    /**
                     * @template T
                     */
                    final class ArrayList
                    {
                        /**
                         * @template A
                         * @psalm-if-this-is ArrayList<Option<A>>
                         * @return ArrayList<A>
                         */
                        public function compact(): ArrayList
                        {
                            throw new RuntimeException("???");
                        }
                    }

                    /** @var ArrayList<int> $list */
                    $list = new ArrayList();
                    $numbers = $list->compact();',
                'error_message' => 'IfThisIsMismatch',
            ],
        ];
    }
}
