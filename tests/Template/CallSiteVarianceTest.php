<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class CallSiteVarianceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    private const BOX = '<?php
        class Node {}
        final class LeafNode extends Node {}
        final class OtherNode extends Node {}

        /** @template T of Node */
        class Box {
            /** @var T */
            public $value;

            /** @param T $value */
            public function __construct($value) {
                $this->value = $value;
            }

            /** @return T */
            public function get() {
                return $this->value;
            }

            /** @param T $value */
            public function set($value): void {
                $this->value = $value;
            }

            /** @return Box<T> */
            public function copy(): Box {
                return new Box($this->value);
            }

            /**
             * @template U
             * @param callable(T): U $f
             * @return U
             */
            public function map(callable $f) {
                return $f($this->value);
            }
        }

        /**
         * @template T of Node
         * @extends Box<T>
         */
        final class SubBox extends Box {}
        ';

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'covariantArgumentAcceptsAnImplementationOfASubtype' => [
                'code' => '<?php
                    class Node {}
                    final class LeafNode extends Node {}

                    /** @template T of Node */
                    interface Handler {
                        /** @param T $node */
                        public function handle(Node $node): void;
                    }

                    /** @implements Handler<LeafNode> */
                    final class LeafHandler implements Handler {
                        public function handle(Node $node): void {}
                    }

                    /** @param class-string<Handler<covariant Node>> $name */
                    function report(string $name): void {}

                    /** @param Handler<covariant Node> $h */
                    function take(Handler $h): void {}

                    report(LeafHandler::class);
                    take(new LeafHandler());',
            ],
            'covariantArgumentAcceptsSubtypeArguments' => [
                'code' => self::BOX . '
                    /** @param Box<covariant Node> $box */
                    function take(Box $box): void {}

                    /**
                     * @param Box<LeafNode> $leaf
                     * @param Box<covariant LeafNode> $covariant_leaf
                     * @param SubBox<LeafNode> $sub_leaf
                     */
                    function f(Box $leaf, Box $covariant_leaf, SubBox $sub_leaf): void {
                        take($leaf);
                        take($covariant_leaf);
                        take($sub_leaf);
                    }',
            ],
            'contravariantArgumentAcceptsSupertypeArguments' => [
                'code' => self::BOX . '
                    /** @param Box<contravariant LeafNode> $box */
                    function take(Box $box): void {}

                    /**
                     * @param Box<Node> $node
                     * @param Box<contravariant Node> $contravariant_node
                     * @param SubBox<contravariant Node> $sub_node
                     */
                    function f(Box $node, Box $contravariant_node, SubBox $sub_node): void {
                        take($node);
                        take($contravariant_node);
                        take($sub_node);
                    }',
            ],
            'invariantArgumentFitsBothProjectionsAndAStar' => [
                'code' => self::BOX . '
                    /** @param Box<covariant Node> $box */
                    function takeCovariant(Box $box): void {}

                    /** @param Box<contravariant Node> $box */
                    function takeContravariant(Box $box): void {}

                    /** @param Box<*> $box */
                    function takeAny(Box $box): void {}

                    /**
                     * @param Box<Node> $node
                     * @param Box<covariant Node> $covariant
                     * @param Box<contravariant Node> $contravariant
                     */
                    function f(Box $node, Box $covariant, Box $contravariant): void {
                        takeCovariant($node);
                        takeContravariant($node);
                        takeAny($covariant);
                        takeAny($contravariant);
                    }',
            ],
            'covariantArgumentReadsTheTypeItHolds' => [
                'code' => self::BOX . '
                    /** @return Box<covariant Node> */
                    function box(): Box {
                        return new Box(new LeafNode());
                    }

                    $box = box();
                    $read = $box->get();
                    $property = $box->value;
                    $copy = $box->copy();
                    $mapped = $box->map(function ($node) {
                        return $node;
                    });',
                'assertions' => [
                    '$box===' => 'Box<covariant Node>',
                    '$read===' => 'Node',
                    '$property===' => 'Node',
                    '$copy===' => 'Box<covariant Node>',
                    '$mapped===' => 'Node',
                ],
            ],
            'contravariantArgumentReadsTheBound' => [
                'code' => self::BOX . '
                    /** @return Box<contravariant LeafNode> */
                    function box(): Box {
                        return new Box(new Node());
                    }

                    /** @param Box<covariant Node> $box */
                    function takeCovariant(Box $box): void {}

                    $box = box();
                    $read = $box->get();
                    $property = $box->value;
                    $copy = $box->copy();
                    $mapped = $box->map(function ($node) {
                        return $node;
                    });
                    $box->set(new LeafNode());
                    $box->value = new LeafNode();
                    takeCovariant($box);',
                'assertions' => [
                    '$read===' => 'Node',
                    '$property===' => 'Node',
                    '$copy===' => 'Box<*>',
                    '$mapped===' => 'Node',
                ],
            ],
            'projectedArgumentOfAChildClass' => [
                'code' => self::BOX . '
                    /** @return SubBox<covariant Node> */
                    function covariantBox(): SubBox {
                        return new SubBox(new LeafNode());
                    }

                    /** @return SubBox<contravariant LeafNode> */
                    function contravariantBox(): SubBox {
                        return new SubBox(new Node());
                    }

                    $covariant_read = covariantBox()->get();
                    $contravariant_read = contravariantBox()->get();
                    contravariantBox()->set(new LeafNode());',
                'assertions' => [
                    '$covariant_read===' => 'Node',
                    '$contravariant_read===' => 'Node',
                ],
            ],
            'contravariantArgumentBoundsATemplateFromAbove' => [
                'code' => self::BOX . '
                    /**
                     * @template T of Node
                     * @param Box<contravariant T> $box
                     * @param T $value
                     * @return T
                     */
                    function fill(Box $box, Node $value): Node {
                        $box->set($value);

                        return $value;
                    }

                    $filled = fill(new Box(new Node()), new LeafNode());',
                'assertions' => [
                    '$filled===' => 'LeafNode',
                ],
            ],
            'contravariantArgumentOfAMemberReadThroughACovariantOne' => [
                'code' => '<?php
                    /** @template T */
                    final class Cell {
                        /** @var Cell<contravariant T>|null */
                        public $wider;

                        /** @return Cell<contravariant T> */
                        public function widen(): Cell {
                            throw new RuntimeException();
                        }
                    }

                    /** @return Cell<covariant int> */
                    function cell(): Cell {
                        throw new RuntimeException();
                    }

                    $widened = cell()->widen();
                    $wider = cell()->wider;',
                'assertions' => [
                    '$widened===' => 'Cell<*>',
                    '$wider===' => 'Cell<*>|null',
                ],
            ],
            'covariantArgumentsOfOneTemplateBindItsUnion' => [
                'code' => self::BOX . '
                    /**
                     * @template T of Node
                     * @param Box<covariant T> $first
                     * @param Box<covariant T> $second
                     * @return T
                     */
                    function either(Box $first, Box $second): Node {
                        return $first->get();
                    }

                    $leaf = either(new Box(new LeafNode()), new Box(new LeafNode()));
                    $node = either(new Box(new LeafNode()), new Box(new OtherNode()));',
                'assertions' => [
                    '$leaf===' => 'LeafNode',
                    '$node===' => 'LeafNode|OtherNode',
                ],
            ],
            'covariantArgumentInNestedPositions' => [
                'code' => self::BOX . '
                    /** @param list<Box<covariant Node>> $boxes */
                    function takeList(array $boxes): void {}

                    /** @param Box<covariant Node>|null $box */
                    function takeNullable(?Box $box): void {}

                    /** @param class-string<Box<covariant Node>> $class */
                    function takeClass(string $class): void {}

                    /** @extends Box<LeafNode> */
                    final class LeafBox extends Box {}

                    /** @param list<Box<LeafNode>> $boxes */
                    function f(array $boxes): void {
                        takeList($boxes);
                        takeNullable($boxes[0] ?? null);
                        takeClass(LeafBox::class);
                    }',
            ],
            'covariantArgumentInAReturnTypeAndAProperty' => [
                'code' => self::BOX . '
                    /** @return Box<covariant Node> */
                    function box(): Box {
                        return new Box(new LeafNode());
                    }

                    final class Holder {
                        /** @var Box<covariant Node> */
                        public $box;

                        /** @param Box<LeafNode> $box */
                        public function __construct(Box $box) {
                            $this->box = $box;
                        }
                    }',
            ],
            'covariantTemplateInACovariantArgument' => [
                'code' => self::BOX . '
                    /** @template-covariant T of Node */
                    final class Producer {
                        /** @var T */
                        private $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }

                        /** @return Box<covariant T> */
                        public function box(): Box {
                            return new Box($this->value);
                        }
                    }',
            ],
            'argumentOfTheDeclaredVarianceIsTheSameType' => [
                'code' => '<?php
                    /** @template-covariant T */
                    interface Source {
                        /** @return T */
                        public function get();
                    }

                    /** @template-contravariant T */
                    interface Sink {
                        /** @param T $value */
                        public function put($value): void;
                    }

                    /** @return Source<covariant Exception> */
                    function covariantSource(): Source {
                        throw new RuntimeException();
                    }

                    /** @return Source<Exception> */
                    function source(): Source {
                        throw new RuntimeException();
                    }

                    /** @return Sink<contravariant Exception> */
                    function contravariantSink(): Sink {
                        throw new RuntimeException();
                    }

                    $source = rand(0, 1) ? covariantSource() : source();
                    $sink = contravariantSink();
                    $sink->put($source->get());',
                'assertions' => [
                    '$source===' => 'Source<Exception>',
                    '$sink===' => 'Sink<Exception>',
                ],
            ],
            'argumentOfTheDeclaredVarianceTakesWhatTheTemplateTakes' => [
                'code' => '<?php
                    /**
                     * @template-covariant T
                     * @psalm-immutable
                     */
                    final class Holder {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }

                        /**
                         * @param T $value
                         * @return Holder<T>
                         */
                        public function with($value): Holder {
                            return new Holder($value);
                        }
                    }

                    /** @param Holder<covariant Exception> $holder */
                    function f(Holder $holder): void {
                        $holder->with(new Exception());
                    }',
            ],
            'contravariantArgumentTakesAnIndexedAccessOfTheTypeItHolds' => [
                'code' => '<?php
                    /**
                     * @template T of array{a: int, b: string}
                     * @template K of key-of<T>
                     */
                    final class Slot {
                        /** @param T[K] $value */
                        public function set($value): void {}
                    }

                    /** @param Slot<contravariant array{a: int, b: string}, "a"> $slot */
                    function f(Slot $slot): void {
                        $slot->set(1);
                    }',
            ],
            'combiningProjectedArguments' => [
                'code' => '<?php
                    /** @template T */
                    final class Cell {}

                    /** @return Cell<covariant 5> */
                    function covariantFive(): Cell {
                        throw new RuntimeException();
                    }

                    /** @return Cell<covariant 6> */
                    function covariantSix(): Cell {
                        throw new RuntimeException();
                    }

                    /** @return Cell<contravariant 5> */
                    function contravariantFive(): Cell {
                        throw new RuntimeException();
                    }

                    /** @return Cell<contravariant 6> */
                    function contravariantSix(): Cell {
                        throw new RuntimeException();
                    }

                    /** @return Cell<5> */
                    function five(): Cell {
                        throw new RuntimeException();
                    }

                    $covariant = rand(0, 1) ? covariantFive() : covariantSix();
                    $contravariant = rand(0, 1) ? contravariantFive() : contravariantSix();
                    $mixed = rand(0, 1) ? five() : covariantFive();',
                'assertions' => [
                    '$covariant===' => 'Cell<covariant 5|6>',
                    '$contravariant===' => 'Cell<contravariant 5>|Cell<contravariant 6>',
                    '$mixed===' => 'Cell<5>|Cell<covariant 5>',
                ],
            ],
            'narrowingKeepsTheVariance' => [
                'code' => self::BOX . '
                    /** @return Box<covariant Node>|Node */
                    function boxOrNode() {
                        return new Node();
                    }

                    $box = boxOrNode();

                    if (!$box instanceof Box) {
                        exit;
                    }',
                'assertions' => [
                    '$box===' => 'Box<covariant Node>',
                ],
            ],
            'iteratingAProjectedArgument' => [
                'code' => '<?php
                    class Node {}
                    final class LeafNode extends Node {}

                    /**
                     * @template T of Node
                     * @implements IteratorAggregate<int, T>
                     */
                    final class Nodes implements IteratorAggregate {
                        /** @var list<T> */
                        private $nodes = [];

                        public function getIterator(): ArrayIterator {
                            return new ArrayIterator($this->nodes);
                        }
                    }

                    /** @param iterable<int, Node> $nodes */
                    function takeIterable(iterable $nodes): void {}

                    /**
                     * @param Nodes<covariant Node> $nodes
                     * @param Nodes<contravariant LeafNode> $more_nodes
                     */
                    function f(Nodes $nodes, Nodes $more_nodes): void {
                        takeIterable($nodes);
                        takeIterable($more_nodes);

                        foreach ($nodes as $node) {
                            /** @psalm-check-type-exact $node = Node */
                        }

                        foreach ($more_nodes as $node) {
                            /** @psalm-check-type-exact $node = Node */
                        }
                    }',
            ],
            'contravariantArgumentOfAnUnboundedTemplateReadsMixed' => [
                'code' => '<?php
                    /** @template T */
                    final class Cell {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }

                        /** @return T */
                        public function get() {
                            return $this->value;
                        }
                    }

                    /** @return Cell<contravariant int> */
                    function cell(): Cell {
                        return new Cell(1);
                    }

                    $read = cell()->get();',
                'assertions' => [
                    '$read===' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'contravariantArgumentTakesAClassStringOfTheTypeItHolds' => [
                'code' => '<?php
                    class Node {}
                    final class LeafNode extends Node {}

                    /** @template T of Node */
                    final class Factory {
                        /** @param class-string<T> $class */
                        public function register(string $class): void {}
                    }

                    /** @param Factory<contravariant LeafNode> $factory */
                    function f(Factory $factory): void {
                        $factory->register(LeafNode::class);
                    }',
            ],
            'covariantArgumentOfAWrappedTemplateKeepsItsVariance' => [
                'code' => self::BOX . '
                    /** @template-covariant T */
                    interface Reader {
                        /** @return T */
                        public function read();
                    }

                    /**
                     * @template T of Node
                     * @implements Reader<Box<T>>
                     */
                    final class BoxReader implements Reader {
                        /** @return Box<T> */
                        public function read(): Box {
                            throw new RuntimeException();
                        }
                    }

                    /** @param Reader<Box<covariant Node>> $reader */
                    function take(Reader $reader): void {}

                    /** @param BoxReader<covariant LeafNode> $reader */
                    function f(BoxReader $reader): void {
                        take($reader);
                    }',
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
            'contravariantArgumentOfAWrappedUnboundedTemplateIsUnknown' => [
                'code' => '<?php
                    /** @template-covariant T */
                    interface Reader {}

                    /**
                     * @template T
                     * @implements Reader<list<T>>
                     */
                    final class ListReader implements Reader {}

                    /** @param Reader<list<string>> $reader */
                    function takeStrings(Reader $reader): void {}

                    /** @param ListReader<contravariant int> $reader */
                    function f(ListReader $reader): void {
                        takeStrings($reader);
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:37 - Argument 1 of takeStrings expects Reader<list<string>>, but parent type ListReader<contravariant int> provided',
            ],
            'covariantArgumentTakesNoClassString' => [
                'code' => '<?php
                    class Node {}
                    final class LeafNode extends Node {}

                    /** @template T of Node */
                    final class Factory {
                        /** @param class-string<T> $class */
                        public function register(string $class): void {}
                    }

                    /** @param Factory<covariant Node> $factory */
                    function f(Factory $factory): void {
                        $factory->register(LeafNode::class);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:44 - Argument 1 of Factory::register expects never, but LeafNode::class provided',
            ],
            'contravariantArgumentBoundsATemplateFromAboveAndRejectsAWiderValue' => [
                'code' => self::BOX . '
                    /**
                     * @template T of Node
                     * @param Box<contravariant T> $box
                     * @param T $value
                     */
                    function fill(Box $box, Node $value): void {
                        $box->set($value);
                    }

                    /** @param Box<LeafNode> $box */
                    function f(Box $box): void {
                        fill($box, new Node());
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:58:25 - Type Node should be a subtype of LeafNode',
            ],
            'covariantArgumentRejectsAnotherArgument' => [
                'code' => self::BOX . '
                    /** @param Box<covariant LeafNode> $box */
                    function take(Box $box): void {}

                    /** @param Box<OtherNode> $other */
                    function f(Box $other): void {
                        take($other);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:52:30 - Argument 1 of take expects Box<covariant LeafNode>, but Box<OtherNode> provided',
            ],
            'contravariantArgumentRejectsAnotherArgument' => [
                'code' => self::BOX . '
                    /** @param Box<contravariant LeafNode> $box */
                    function take(Box $box): void {}

                    /** @param Box<OtherNode> $other */
                    function f(Box $other): void {
                        take($other);
                    }',
                'error_message' => 'expects Box<contravariant LeafNode>, but Box<OtherNode> provided',
            ],
            'covariantArgumentTakesNoValue' => [
                'code' => self::BOX . '
                    /** @param Box<covariant Node> $box */
                    function f(Box $box): void {
                        $box->set(new Node());
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:49:35 - Argument 1 of Box::set expects never, but Node provided',
            ],
            'covariantArgumentTakesNoAssignedValue' => [
                'code' => self::BOX . '
                    /** @param Box<covariant Node> $box */
                    function f(Box $box): void {
                        $box->value = new Node();
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:49:39 - $box->value with declared type \'never\' cannot be assigned type',
            ],
            'covariantArgumentOfAChildClassTakesNoValue' => [
                'code' => self::BOX . '
                    /** @param SubBox<covariant Node> $box */
                    function f(SubBox $box): void {
                        $box->set(new Node());
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:49:35 - Argument 1 of SubBox::set expects never, but Node provided',
            ],
            'contravariantArgumentTakesNoWiderValue' => [
                'code' => self::BOX . '
                    /** @param Box<contravariant LeafNode> $box */
                    function f(Box $box): void {
                        $box->set(new Node());
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'covariantArgumentIsNotTheInvariantOne' => [
                'code' => self::BOX . '
                    /** @param Box<Node> $box */
                    function take(Box $box): void {}

                    /** @param Box<covariant Node> $box */
                    function f(Box $box): void {
                        take($box);
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:52:30 - Argument 1 of take expects Box<Node>, but parent type Box<covariant Node> provided',
            ],
            'contravariantArgumentIsNotTheInvariantOne' => [
                'code' => self::BOX . '
                    /** @param Box<LeafNode> $box */
                    function take(Box $box): void {}

                    /** @param Box<contravariant LeafNode> $box */
                    function f(Box $box): void {
                        take($box);
                    }',
                'error_message' => 'Argument 1 of take expects Box<LeafNode>, but parent type Box<contravariant LeafNode> provided',
            ],
            'covariantArgumentIsNotAContravariantOne' => [
                'code' => self::BOX . '
                    /** @param Box<contravariant Node> $box */
                    function take(Box $box): void {}

                    /** @param Box<covariant Node> $box */
                    function f(Box $box): void {
                        take($box);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:52:30 - Argument 1 of take expects Box<contravariant Node>, but Box<covariant Node> provided',
            ],
            'contravariantArgumentReadsTheBoundForACovariantOne' => [
                'code' => self::BOX . '
                    /** @param Box<covariant LeafNode> $box */
                    function take(Box $box): void {}

                    /** @param Box<contravariant LeafNode> $box */
                    function f(Box $box): void {
                        take($box);
                    }',
                'error_message' => 'Argument 1 of take expects Box<covariant LeafNode>, but parent type Box<contravariant LeafNode> provided',
            ],
            'starIsNotANarrowerCovariantArgument' => [
                'code' => self::BOX . '
                    /** @param Box<covariant LeafNode> $box */
                    function take(Box $box): void {}

                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        take($box);
                    }',
                'error_message' => 'expects Box<covariant LeafNode>, but parent type Box<*> provided',
            ],
            'projectedArgumentBindsNoInvariantTemplate' => [
                'code' => self::BOX . '
                    /**
                     * @template T of Node
                     * @param Box<T> $box
                     * @return T
                     */
                    function unwrap(Box $box): Node {
                        return $box->get();
                    }

                    /** @param Box<covariant Node> $box */
                    function f(Box $box): void {
                        unwrap($box);
                    }',
                'error_message' => 'Argument 1 of unwrap expects Box<Node>, but parent type Box<covariant Node> provided',
            ],
            'covariantArgumentOfAContravariantTemplate' => [
                'code' => '<?php
                    /** @template-contravariant T */
                    interface Sink {
                        /** @param T $value */
                        public function put($value): void;
                    }

                    /** @param Sink<covariant stdClass> $sink */
                    function f(Sink $sink): void {}',
                'error_message' => 'InvalidTemplateParam - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:32 - The covariant argument of Sink<covariant stdClass> conflicts with template param T of Sink, which is contravariant',
            ],
            'contravariantArgumentOfACovariantTemplate' => [
                'code' => '<?php
                    /** @template-covariant T */
                    interface Source {
                        /** @return T */
                        public function get();
                    }

                    /** @return Source<contravariant stdClass> */
                    function f(): Source {
                        throw new RuntimeException();
                    }',
                'error_message' => 'InvalidTemplateParam - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:33 - The contravariant argument of Source<contravariant stdClass> conflicts with template param T of Source, which is covariant',
            ],
            'projectedArgumentInExtends' => [
                'code' => '<?php
                    /** @template T */
                    class Base {}

                    /** @extends Base<covariant stdClass> */
                    final class Child extends Base {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21 - The variance of a type argument of Base<covariant stdClass> is not allowed in @template-extends',
            ],
            'projectedArgumentInImplements' => [
                'code' => '<?php
                    /** @template T */
                    interface Box {}

                    /** @implements Box<contravariant stdClass> */
                    final class ContravariantBox implements Box {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21 - The variance of a type argument of Box<contravariant stdClass> is not allowed in @template-implements',
            ],
            'projectedArgumentInUse' => [
                'code' => '<?php
                    /** @template T */
                    trait Box {}

                    final class CovariantBox {
                        /** @use Box<covariant stdClass> */
                        use Box;
                    }',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:25 - The variance of a type argument of Box<covariant stdClass> is not allowed in @template-use',
            ],
        ];
    }
}
