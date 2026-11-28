<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\Providers;
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function dirname;
use function getcwd;

/**
 * Lower bounds of type templates (`@template T super Cat`, #15097): a value of the lower bound fits the template, a
 * type argument must be a supertype of the lower bound, and an inferred template gets the lower bound as one more
 * lower bound (Hack's rule: `keep(1)` is `1|Cat`). The fixtures in
 * bin/hack-conformance/fixtures/type_parameter_lower_bounds/ assert the verdicts of HHVM where Hack has the construct;
 * the cases here pin types and messages, and what Hack has no construct for. Grouped by behaviour: parsing, invalid
 * declarations, explicit type arguments, bodies, inference, construction and overrides.
 */
final class TemplateLowerBoundTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    private const ANIMALS = '<?php
        class Animal {}
        class Cat extends Animal { public function meow(): void {} }
        final class Kitten extends Cat {}
        final class Dog extends Animal {}
        ';

    /**
     * The first issue of the snippet has the issue type, and its message contains the text: for the cases where the
     * issue type alone could come from elsewhere.
     */
    #[DataProvider('providerInvalidCodeWithMessage')]
    public function testInvalidCodeWithMessage(string $code, string $issue_type, string $message): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, new Context());

        $issues = IssueBuffer::getIssuesDataForFile($file_path);

        self::assertNotSame([], $issues, 'No issue was reported');
        self::assertSame($issue_type, $issues[0]->type, $issues[0]->message);
        self::assertStringContainsString($message, $issues[0]->message);
    }

    /**
     * @return iterable<string, array{code: string, issue_type: string, message: string}>
     * @psalm-pure
     */
    public static function providerInvalidCodeWithMessage(): iterable
    {
        return [
            // parsing
            'upperBoundThenLowerBoundRejectsKitten' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T of Animal super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Kitten> $s */
                    function f(Sink $s): void {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat, type Kitten given',
            ],
            'lowerBoundThenUpperBoundRejectsKitten' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat of Animal */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Kitten> $s */
                    function f(Sink $s): void {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat, type Kitten given',
            ],
            'upperBoundThenLowerBoundRejectsMixed' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T of Animal super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<mixed> $s */
                    function f(Sink $s): void {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects type Animal, type mixed given',
            ],
            'lowerBoundThenUpperBoundRejectsMixed' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat of Animal */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<mixed> $s */
                    function f(Sink $s): void {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects type Animal, type mixed given',
            ],
            'descriptionKeepsTheLowerBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat the item type */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<int> $s */
                    function f(Sink $s): void {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat, type int given',
            ],

            // invalid declarations
            'defaultBelowLowerBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template A
                     * @template T super Cat = Kitten
                     */
                    final class Pair {}',
                'issue_type' => 'InvalidDocblock',
                'message' => 'The default Kitten of template T is not a supertype of its lower bound Cat',
            ],
            'lowerBoundOutsideUpperBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T of int super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }',
                'issue_type' => 'InvalidDocblock',
                'message' => 'The lower bound Cat of template T is not within its bound int',
            ],

            // explicit type arguments
            'sinkOfInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<int> $s */
                    function f(Sink $s): void {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'Extended template param T of Sink<int> expects a supertype of Cat, type int given',
            ],
            'sinkOfKitten' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Kitten> $s */
                    function f(Sink $s): void {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'Extended template param T of Sink<Kitten> expects a supertype of Cat, type Kitten given',
            ],
            'extendsInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    abstract class Sink {
                        /** @param T $v */
                        abstract public function put(mixed $v): void;
                    }

                    /** @extends Sink<int> */
                    final class IntSink extends Sink {
                        public function put(mixed $v): void {}
                    }',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat, type int given',
            ],
            'implementsInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    interface Sink {
                        /** @param T $v */
                        public function put(mixed $v): void;
                    }

                    /** @implements Sink<int> */
                    final class IntSink implements Sink {
                        public function put(mixed $v): void {}
                    }',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat, type int given',
            ],
            'useInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    trait Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    final class IntSink {
                        /** @use Sink<int> */
                        use Sink;
                    }',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat, type int given',
            ],
            'extendsUnboundedTemplate' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /**
                     * @template U
                     * @extends Sink<U>
                     */
                    final class Child extends Sink {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat',
            ],
            'extendsTemplateWithUnrelatedLowerBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /**
                     * @template U super Dog
                     * @extends Sink<U>
                     */
                    final class Child extends Sink {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat',
            ],
            'extendsTemplateWithUpperBoundOnly' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /**
                     * @template U of Cat
                     * @extends Sink<U>
                     */
                    final class Child extends Sink {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat',
            ],
            'propertyTypeArgument' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    final class Holder {
                        /** @var Sink<int>|null */
                        public ?Sink $sink = null;
                    }',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat, type int given',
            ],
            'returnTypeArgument' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @return Sink<int> */
                    function f(): Sink {
                        throw new Exception();
                    }',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat, type int given',
            ],
            'classStringArgument' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param class-string<Sink<int>> $class */
                    function f(string $class): void {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat, type int given',
            ],
            'typeAliasArgument' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @psalm-type IntSink = Sink<int> */
                    final class Sinks {
                        /** @param IntSink $s */
                        public function f(Sink $s): void {}
                    }',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat, type int given',
            ],
            'covariantProjectionOfInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<covariant int> $s */
                    function f(Sink $s): void {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat',
            ],
            'contravariantProjectionOfInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<contravariant int> $s */
                    function f(Sink $s): void {}',
                'issue_type' => 'InvalidTemplateParam',
                'message' => 'expects a supertype of Cat',
            ],

            // inference
            'literalClassStringIsNoClassStringOfATemplate' => [
                'code' => self::ANIMALS . '
                    /**
                     * @template U super Cat::class
                     * @param U $v
                     * @return U
                     */
                    function keepName(mixed $v): mixed { return $v; }
                    /**
                     * @template T of object
                     * @param class-string<T> $name
                     */
                    function replaceName(string $name): void {
                        $r = keepName($name);
                        /** @psalm-trace $r */
                        echo 1;
                    }',
                'issue_type' => 'Trace',
                'message' => '$r: Cat::class|(class-string<T:fn-replacename as object>)',
            ],
        ];
    }

    /**
     * A stub declaring a function without the lower bound of the code it overrides drops that lower bound
     */
    public function testStubDropsTheLowerBoundOfTheFunctionItOverrides(): void
    {
        self::assertSame('1', $this->analyzeWithStub(
            'template_lower_bound_override.phpstub',
            '<?php
                final class Cat {}
                /**
                 * @template T super Cat
                 * @param T $v
                 * @return T
                 */
                function dropsLowerBound(mixed $v): mixed { return $v; }
                $x = dropsLowerBound(1);',
        ));
    }

    /**
     * A stub that overrides a method of a class without declaring its templates keeps their lower bounds
     */
    public function testPartialStubKeepsTheLowerBoundOfTheClass(): void
    {
        self::assertSame('Cat', $this->analyzeWithStub(
            'template_lower_bound_partial_override.phpstub',
            '<?php
                class Cat {}
                final class Kitten extends Cat {}
                /** @template T super Cat */
                final class PartialHolder {
                    /** @param T $v */
                    public function __construct(public mixed $v) {}
                    /** @return T */
                    public function get(): mixed { return $this->v; }
                    public function size(): int { return 1; }
                }
                $x = (new PartialHolder(new Kitten()))->get();',
        ));
    }

    /**
     * @return string the type of `$x` after the code
     */
    private function analyzeWithStub(string $stub, string $code): string
    {
        $config = TestConfig::loadFromXML(
            dirname(__DIR__, 2),
            '<?xml version="1.0"?>
            <psalm errorLevel="1">
                <projectFiles>
                    <directory name="src" />
                </projectFiles>
                <stubs>
                    <file name="tests/fixtures/stubs/' . $stub . '" />
                </stubs>
            </psalm>',
        );
        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            new Providers($this->file_provider, new FakeParserCacheProvider()),
        );
        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        $config->setIncludeCollector(new IncludeCollector());
        $config->visitComposerAutoloadFiles($this->project_analyzer, null);

        $file_path = (string) getcwd() . '/src/somefile.php';
        $this->addFile($file_path, $code);

        $context = new Context();
        $this->analyzeFile($file_path, $context);

        return $context->vars_in_scope['$x']->getId();
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            // parsing
            'psalmTemplateTag' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @psalm-template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Animal> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'phpstanTemplateTag' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @phpstan-template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Animal> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'covariantTemplateTag' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template-covariant T super Cat */
                    final class Source {
                        /** @return T */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /** @param Source<Animal> $s */
                    function f(Source $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'contravariantTemplateTag' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template-contravariant T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Animal> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'upperBoundThenLowerBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T of Animal super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Animal> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'lowerBoundThenUpperBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat of Animal */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Animal> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'defaultWithinBothBounds' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template A
                     * @template T super Cat = Animal
                     */
                    final class Pair {
                        /** @return T */
                        public function second(): mixed {
                            throw new Exception();
                        }
                    }

                    /** @return Pair<int> */
                    function makePair(): Pair {
                        throw new Exception();
                    }

                    $second = makePair()->second();',
                'assertions' => [
                    '$second===' => 'Animal',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'descriptionAfterLowerBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat the item type */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Animal> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'psalmTemplateTagWinsWhole' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template T of Cat
                     * @psalm-template T super Cat
                     */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Animal> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'capitalisedKeywordAfterBoundIsDescription' => [
                'code' => self::ANIMALS . '
                    /** @template T of Animal Super class description */
                    final class Described {}
                    /** @param Described<Cat> $d */
                    function described(Described $d): void {}',
            ],

            // explicit type arguments
            'sinkOfCatOrInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<Cat|int> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'extendsAnimal' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    abstract class Sink {
                        /** @param T $v */
                        abstract public function put(mixed $v): void;
                    }

                    /** @extends Sink<Animal> */
                    final class AnimalSink extends Sink {
                        public function put(mixed $v): void {}
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'implementsAnimal' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    interface Sink {
                        /** @param T $v */
                        public function put(mixed $v): void;
                    }

                    /** @implements Sink<Animal> */
                    final class AnimalSink implements Sink {
                        public function put(mixed $v): void {}
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'useAnimal' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    trait Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    final class AnimalSink {
                        /** @use Sink<Animal> */
                        use Sink;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'covariantProjectionOfAnimal' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<covariant Animal> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'contravariantProjectionOfAnimal' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<contravariant Animal> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'starProjection' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<*> $s */
                    function f(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'projectedTypeArgumentAboveGenericLowerBound' => [
                'code' => self::ANIMALS . '
                    /** @template T */
                    final class Box {}
                    /** @template T super Box<Cat> */
                    final class Sink {}
                    /** @param Sink<Box<covariant Animal>> $s */
                    function covariantSink(Sink $s): void {}
                    /** @param Sink<Box<*>> $s */
                    function starSink(Sink $s): void {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],

            // bodies
            'starPutsCat' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<*> $s */
                    function f(Sink $s): void {
                        $s->put(new Cat());
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'covariantAnimalPutsCat' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<covariant Animal> $s */
                    function f(Sink $s): void {
                        $s->put(new Cat());
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'contravariantAnimalPutsAnimal' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<contravariant Animal> $s */
                    function f(Sink $s): void {
                        $s->put(new Animal());
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'methodTemplateReturnsItsValue' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    final class Keeper {
                        /**
                         * @template U super Cat
                         * @param U $u
                         * @return U
                         */
                        public function m(mixed $u): mixed {
                            return $u;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'traitMethodTemplateBoundedByTraitTemplate' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T */
                    trait Keeps {
                        /**
                         * @template U super T
                         * @param U $u
                         * @return U
                         */
                        public function m(mixed $u): mixed {
                            return $u;
                        }
                    }

                    final class CatKeeper {
                        /** @use Keeps<Cat> */
                        use Keeps;
                    }

                    (new CatKeeper())->m(new Cat());',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'classTemplateValueFitsMethodTemplateWithItAsLowerBound' => [
                'code' => self::ANIMALS . '
                    /** @template-contravariant T */
                    interface Consumer {
                        /** @param T $t */
                        public function take(mixed $t): void;
                    }
                    /** @template T */
                    final class Holder {
                        /** @param T $value */
                        public function __construct(private mixed $value) {}
                        /**
                         * @template U super T
                         * @param U $u
                         * @return U
                         */
                        public function m(mixed $u): mixed { return $this->value; }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'nullAndFalseLowerBoundsInBody' => [
                'code' => '<?php
                    /** @template T super null */
                    final class NullSink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                        public function clear(): void { $this->put(null); }
                    }
                    /** @template T super false */
                    final class FalseSink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                        public function clear(): void { $this->put(false); }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'traitSelfLowerBoundIsTheUsingClass' => [
                'code' => '<?php
                    trait Keeps {
                        /**
                         * @template T super self
                         * @param T $value
                         * @return T
                         */
                        public function keep(mixed $value): mixed { return $value; }
                    }
                    final class First { use Keeps; }
                    final class Second { use Keeps; }
                    $x = (new First())->keep(1);
                    $y = (new Second())->keep(1);',
                'assertions' => [
                    '$x===' => '1|First',
                    '$y===' => '1|Second',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],

            // inference
            'keepCat' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template T super Cat
                     * @param T $v
                     * @return T
                     */
                    function keep(mixed $v): mixed {
                        return $v;
                    }

                    $x = keep(new Cat());',
                'assertions' => [
                    '$x===' => 'Cat',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'keepKitten' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template T super Cat
                     * @param T $v
                     * @return T
                     */
                    function keep(mixed $v): mixed {
                        return $v;
                    }

                    $x = keep(new Kitten());',
                'assertions' => [
                    '$x===' => 'Cat',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'keepAnimal' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template T super Cat
                     * @param T $v
                     * @return T
                     */
                    function keep(mixed $v): mixed {
                        return $v;
                    }

                    $x = keep(new Animal());',
                'assertions' => [
                    '$x===' => 'Animal',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'keepInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template T super Cat
                     * @param T $v
                     * @return T
                     */
                    function keep(mixed $v): mixed {
                        return $v;
                    }

                    $x = keep(1);',
                'assertions' => [
                    '$x===' => '1|Cat',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'noEvidenceIsTheLowerBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template T super Cat
                     * @return list<T>
                     */
                    function mk(): array {
                        return [];
                    }

                    $x = mk();',
                'assertions' => [
                    '$x===' => 'list<Cat>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'getOrElseNull' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template T super Cat
                     * @param T $d
                     * @return T
                     */
                    function getOrElse(mixed $d): mixed {
                        return $d;
                    }

                    $x = getOrElse(null);',
                'assertions' => [
                    '$x===' => 'Cat|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'joinKeepsIntWithFloatLowerBound' => [
                'code' => '<?php
                    /**
                     * @template T super float
                     * @param T $value
                     * @return T
                     */
                    function keepFloat(mixed $value): mixed { return $value; }
                    $x = keepFloat(1);
                    /** @template T super float */
                    final class Holder {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}
                    }
                    $y = (new Holder(1))->value;',
                'assertions' => [
                    '$x===' => '1|float',
                    '$y===' => '1|float',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'joinKeepsTheShapeOfTheValue' => [
                'code' => '<?php
                    /**
                     * @template T super list<float>
                     * @param T $v
                     * @return T
                     */
                    function keepList(mixed $v): mixed { return $v; }
                    /** @return list<int> */
                    function ints(): array { return [1]; }
                    $x = keepList(ints());
                    /** @template T super callable(): float */
                    final class CallHolder {
                        /** @param T $v */
                        public function __construct(public mixed $v) {}
                    }
                    $y = (new CallHolder(static fn(): int => 1))->v;',
                'assertions' => [
                    '$x===' => 'list<float|int>',
                    '$y===' => 'Closure[pure]():1|callable[impure]():float',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'joinKeepsAnObjectCastToTheStringLowerBound' => [
                'code' => '<?php
                    final class Stringy {
                        public function __toString(): string { return "x"; }
                    }
                    /**
                     * @template T super string
                     * @param T $value
                     * @return T
                     */
                    function keep(mixed $value): mixed { return $value; }
                    $x = keep(new Stringy());',
                'assertions' => [
                    '$x===' => 'Stringy|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'typeVariableLowerBoundKeepsTheArgument' => [
                'code' => self::ANIMALS . '
                    /** @template T */
                    final class Holder {
                        /** @param T $v */
                        public function __construct(public mixed $v) {}
                        /**
                         * @template U super T
                         * @param U $v
                         * @return U
                         */
                        public function keep(mixed $v): mixed { return $v; }
                    }
                    $x = (new Holder(new Cat()))->keep(1);',
                'assertions' => [
                    '$x===' => '1|Cat',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'instanceMethodTemplate' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    final class Keeper {
                        /**
                         * @template U super Cat
                         * @param U $u
                         * @return U
                         */
                        public function m(mixed $u): mixed {
                            return $u;
                        }
                    }

                    $x = (new Keeper())->m(1);',
                'assertions' => [
                    '$x===' => '1|Cat',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'staticMethodTemplate' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    final class Keeper {
                        /**
                         * @template U super Cat
                         * @param U $u
                         * @return U
                         */
                        public static function s(mixed $u): mixed {
                            return $u;
                        }
                    }

                    $x = Keeper::s(1);',
                'assertions' => [
                    '$x===' => '1|Cat',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'firstClassCallable' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template T super Cat
                     * @param T $v
                     * @return T
                     */
                    function keep(mixed $v): mixed {
                        return $v;
                    }

                    $f = keep(...);
                    $x = $f(1);',
                'assertions' => [
                    '$x===' => '1|Cat',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            // the call of a first-class callable binds no default: a template it gives nothing is its lower bound
            'firstClassCallableIsTheLowerBoundNotTheDefault' => [
                'code' => self::ANIMALS . '
                    /**
                     * @template T super Cat = Animal
                     * @return list<T>
                     */
                    function makeList(): array { return []; }
                    $f = makeList(...);
                    $x = $f();
                    /**
                     * @template T super Cat = Animal
                     * @param (callable(): T)|null $g
                     * @return list<T>
                     */
                    function optional(?callable $g): array { return $g ? [$g()] : []; }
                    $h = optional(...);
                    $y = $h(null);',
                'assertions' => [
                    '$x===' => 'list<Cat>',
                    '$y===' => 'list<Cat>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'firstClassCallableThroughSubclass' => [
                'code' => self::ANIMALS . '
                    /** @template T */
                    class Base {
                        /**
                         * @template U of T
                         * @param U $v
                         * @return U
                         */
                        public function keep(mixed $v): mixed { return $v; }
                    }
                    /** @extends Base<Animal> */
                    final class WithAnimal extends Base {}
                    $f = (new WithAnimal())->keep(...);
                    $x = $f(new Cat());',
                'assertions' => [
                    '$x===' => 'Cat',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],

            // construction
            'holderOfInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Holder {
                        /** @param T $v */
                        public function __construct(public mixed $v) {}
                    }

                    $x = (new Holder(1))->v;',
                'assertions' => [
                    '$x===' => '1|Cat',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'holderOfAnimal' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Holder {
                        /** @param T $v */
                        public function __construct(public mixed $v) {}
                    }

                    $x = (new Holder(new Animal()))->v;',
                'assertions' => [
                    '$x===' => 'Animal',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inheritedConstructorJoinsLowerBound' => [
                'code' => self::ANIMALS . '
                    /** @template T */
                    class BaseHolder {
                        /** @param T $v */
                        public function __construct(public mixed $v) {}
                    }
                    /**
                     * @template U super Cat
                     * @extends BaseHolder<U>
                     */
                    final class ChildHolder extends BaseHolder {}
                    $x = (new ChildHolder(1))->v;',
                'assertions' => [
                    '$x===' => '1|Cat',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],

            // overrides
            'overrideTemplateWithoutParentTemplate' => [
                'code' => self::ANIMALS . '
                    class Factory {
                        public function make(Animal $a): Animal { return $a; }
                    }
                    final class CatFactory extends Factory {
                        /**
                         * @template U of Animal super Cat
                         * @param U $a
                         * @return U
                         */
                        public function make(Animal $a): Animal { return $a; }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'traitOverrideLowersInheritedLowerBound' => [
                'code' => self::ANIMALS . '
                    /** @template T */
                    interface Keeper {
                        /**
                         * @template U super T
                         * @param U $u
                         * @return U
                         */
                        public function keep(mixed $u): mixed;
                    }
                    trait KeepsKittens {
                        /**
                         * @template U super Kitten
                         * @param U $u
                         * @return U
                         */
                        public function keep(mixed $u): mixed { return $u; }
                    }
                    /** @implements Keeper<Cat> */
                    final class CatKeeper implements Keeper {
                        use KeepsKittens;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'traitOverrideLowerBoundThroughUseBinding' => [
                'code' => self::ANIMALS . '
                    /** @template T */
                    abstract class Keeper {
                        /**
                         * @template U super T
                         * @param U $u
                         * @return U
                         */
                        abstract public function keep(mixed $u): mixed;
                    }
                    /** @template T */
                    trait Keeps {
                        /**
                         * @template U super T
                         * @param U $u
                         * @return U
                         */
                        public function keep(mixed $u): mixed { return $u; }
                    }
                    /** @extends Keeper<Cat> */
                    final class CatKeeper extends Keeper {
                        /** @use Keeps<Kitten> */
                        use Keeps;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
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
            // parsing
            'missingLowerBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }',
                'error_message' => 'Missing lower bound after super for template T',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'missingSecondLowerBound' => [
                'code' => self::ANIMALS . '
                    /** @template T of Animal super */
                    final class Sink {}',
                'error_message' => 'Missing lower bound after super',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'repeatedLowerBound' => [
                'code' => self::ANIMALS . '
                    /** @template T super Cat super Kitten */
                    final class Sink {}',
                'error_message' => 'Repeated super clause',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'functionVarianceTagDeclaresNoTemplate' => [
                'code' => '<?php
                    /**
                     * @template-covariant T
                     * @param T $v
                     * @return T
                     */
                    function keep(mixed $v): mixed { return $v; }',
                'error_message' => 'UndefinedDocblockClass',
                'error_levels' => [],
                'php_version' => '8.1',
            ],

            // invalid declarations
            'undefinedLowerBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super UndefinedClass */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }',
                'error_message' => 'UndefinedDocblockClass',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'siblingLowerBoundIsNotSupported' => [
                'code' => self::ANIMALS . '
                    /**
                     * @template A
                     * @template T super A
                     */
                    final class PairSink {
                        /** @param T $value */
                        public function put(mixed $value): void {}
                    }',
                'error_message' => 'Lower bound of template T references template A of the same declaration, which is'
                    . ' not supported',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'functionSiblingLowerBoundIsNotSupported' => [
                'code' => self::ANIMALS . '
                    /**
                     * @template T super U
                     * @template U super Cat
                     * @return list<T>
                     */
                    function makeForward(): array { return []; }',
                'error_message' => 'Lower bound of template T references template U of the same declaration, which is'
                    . ' not supported',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'selfLowerBoundIsNotSupported' => [
                'code' => self::ANIMALS . '
                    /** @template T super T */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                        public function fill(): void { $this->put(new Cat()); }
                    }',
                'error_message' => 'Lower bound of template T references template T of the same declaration, which is'
                    . ' not supported',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'upperBoundNamingASiblingOfAFlooredTemplateIsNotSupported' => [
                'code' => self::ANIMALS . '
                    /**
                     * @template U
                     * @template T of U super Cat
                     */
                    final class Pair {}',
                'error_message' => 'Upper bound of template T, which has a lower bound, references template U of the'
                    . ' same declaration, which is not supported',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'contravariantClassTemplateInMethodLowerBound' => [
                'code' => self::ANIMALS . '
                    /** @template-contravariant T */
                    final class Sink {
                        /**
                         * @template U super T
                         * @param U $u
                         */
                        public function put(mixed $u): void {}
                    }',
                'error_message' => 'is marked contravariant and cannot be used in the lower bound of template U',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'nestedCovariantClassTemplateInMethodLowerBound' => [
                'code' => self::ANIMALS . '
                    /** @template-covariant T */
                    interface Source {
                        /**
                         * @template U super callable(T): void
                         * @param U $u
                         * @return U
                         */
                        public function widen(mixed $u): mixed;
                    }',
                'error_message' => 'is marked covariant and cannot be used in the lower bound of template U',
                'error_levels' => [],
                'php_version' => '8.1',
            ],

            // explicit type arguments
            'nullableUpperIsNoNullLowerBound' => [
                'code' => '<?php
                    final class Cat {}
                    /** @template T super null */
                    class NullableSink {}
                    /**
                     * @template U of Cat|null
                     * @extends NullableSink<U>
                     */
                    final class NullableChild extends NullableSink {}',
                'error_message' => 'expects a supertype of null',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'arrayKeyUpperIsNoArrayKeyLowerBound' => [
                'code' => '<?php
                    /** @template T super array-key */
                    class KeySink {}
                    /**
                     * @template U of array-key
                     * @extends KeySink<U>
                     */
                    final class KeysChild extends KeySink {}',
                'error_message' => 'expects a supertype of array-key',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'floatIsNoSupertypeOfIntLowerBound' => [
                'code' => '<?php
                    /** @template T super int */
                    final class Sink {}
                    /** @param Sink<float> $s */
                    function f(Sink $s): void {}',
                'error_message' => 'expects a supertype of int, type float given',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'strictProofIsRecursive' => [
                'code' => '<?php
                    /** @template T super list<int> */
                    final class ListSink {}
                    /** @param ListSink<list<float>> $s */
                    function f(ListSink $s): void {}',
                'error_message' => 'expects a supertype of list<int>, type list<float> given',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'strictProofRespectsIntersections' => [
                'code' => self::ANIMALS . '
                    /** @template T super Cat */
                    final class Sink {}
                    /**
                     * @template U super Cat
                     * @param Sink<U&Countable> $s
                     */
                    function f(Sink $s): void {}',
                'error_message' => 'expects a supertype of Cat',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'recordLowerBoundIsCheckedByKey' => [
                'code' => '<?php
                    /** @template T super array{a: int, b: float} */
                    final class RecordSink {}
                    /** @param RecordSink<array{a: float, b: int|float}> $sink */
                    function badRecord(RecordSink $sink): void {}',
                'error_message' => 'expects a supertype of array{a: int, b: float}, type',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'iterableLowerBoundIsStrict' => [
                'code' => '<?php
                    /** @template T super iterable<int> */
                    final class IterSink {}
                    /** @param IterSink<iterable<float>> $sink */
                    function badIterable(IterSink $sink): void {}',
                'error_message' => 'expects a supertype of iterable<mixed, int>, type',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'stringIsNoSupertypeOfAStringableLowerBound' => [
                'code' => '<?php
                    final class Stringy {
                        public function __toString(): string { return "x"; }
                    }
                    /** @template T super Stringy */
                    final class Cell {}
                    /** @param Cell<string> $cell */
                    function f(Cell $cell): void {}',
                'error_message' => 'expects a supertype of Stringy, type string given',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'objectWithToStringIsNoStringInAStrictProof' => [
                'code' => '<?php
                    declare(strict_types=1);
                    /**
                     * @template T super string
                     * @param T $v
                     * @return T
                     */
                    function keep(mixed $v): mixed { return $v; }
                    function takeString(string $s): void {}
                    function process(object $x): void {
                        if (method_exists($x, "__toString")) {
                            takeString(keep($x));
                        }
                    }',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],

            // bodies
            'valueLacksMethodsOfLowerBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template T of Animal super Cat
                     * @param T $v
                     */
                    function f(mixed $v): void {
                        $v->meow();
                    }',
                'error_message' => 'Animal::meow does not exist',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'starRejectsInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<*> $s */
                    function f(Sink $s): void {
                        $s->put(1);
                    }',
                'error_message' => 'Argument 1 of Sink::put expects',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'covariantAnimalRejectsAnimal' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T super Cat */
                    final class Sink {
                        /** @param T $v */
                        public function put(mixed $v): void {}
                    }

                    /** @param Sink<covariant Animal> $s */
                    function f(Sink $s): void {
                        $s->put(new Animal());
                    }',
                'error_message' => 'Argument 1 of Sink::put expects',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'nullFloorOnlyForTopLevelTemplate' => [
                'code' => '<?php
                    /** @template T super null */
                    final class NSink {
                        /** @param list<T> $l */
                        public function putAll(array $l): void {}
                        public function x(): void { $this->putAll(null); }
                    }',
                'error_message' => 'NullArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],

            // inference
            'invariantArgumentBelowLowerBound' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /** @template T */
                    final class Box {
                        /** @param T $v */
                        public function set(mixed $v): void {}
                    }

                    /**
                     * @template T super Cat
                     * @param Box<T> $b
                     */
                    function fill(Box $b): void {}

                    /** @param Box<int> $b */
                    function callFill(Box $b): void {
                        fill($b);
                    }',
                'error_message' => 'int does not contain its lower bound Cat',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'callableOfInt' => [
                'code' => '<?php
                    class Animal { public function eat(): void {} }
                    class Cat extends Animal { public function meow(): void {} }
                    class Kitten extends Cat {}
                    class Dog extends Animal {}

                    /**
                     * @template T super Cat
                     * @param callable(T): void $f
                     */
                    function each(callable $f): void {}

                    each(function (int $i): void {});',
                'error_message' => 'Type Cat should be a subtype of int',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'defaultDoesNotBypassFloorOfCallback' => [
                'code' => self::ANIMALS . '
                    /**
                     * @template T super Cat = Animal
                     * @param callable(T): void $f
                     * @return list<T>
                     */
                    function withDefault(callable $f): array { return []; }
                    withDefault(function (Dog $d): void {});',
                'error_message' => 'Type Cat should be a subtype of Dog',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'pinThroughTypeVariableRecordsFloor' => [
                'code' => self::ANIMALS . '
                    final class Purr { public function purr(): void {} }
                    /** @template T */
                    final class Box {
                        /** @param T $v */
                        public function __construct(public mixed $v) {}
                    }
                    /**
                     * @template T super Cat
                     * @param Box<T> $b
                     */
                    function fillF(Box $b): void { $b->v = new Cat(); }
                    function r4(): void {
                        $b = new Box(new Purr());
                        fillF($b);
                        $b->v->purr();
                    }',
                'error_message' => 'Cat::purr does not exist',
                'error_levels' => [],
                'php_version' => '8.1',
            ],

            // construction
            'constructorInvariantArgumentBelowLowerBound' => [
                'code' => self::ANIMALS . '
                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}
                    }
                    /** @template T super Cat */
                    final class Holder {
                        /** @param Box<T> $box */
                        public function __construct(public Box $box) {}
                    }
                    /** @param Box<int> $box */
                    function pinned(Box $box): void {
                        new Holder($box);
                    }',
                'error_message' => 'int does not contain its lower bound Cat',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'contravariantConstructorCallbackBelowLowerBound' => [
                'code' => self::ANIMALS . '
                    /** @template-contravariant T super Cat */
                    final class ContraHolder {
                        /** @param callable(T): void $f */
                        public function __construct(callable $f) {}
                    }
                    new ContraHolder(function (int $i): void {});',
                'error_message' => 'int does not contain its lower bound Cat',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'constructorlessNewWithDefaultKeepsFloor' => [
                'code' => self::ANIMALS . '
                    final class Puppy extends Animal { public function bark(): void {} }
                    /** @template T super Cat = Animal */
                    final class Bag {
                        /** @var list<T> */
                        private array $items = [];
                        /** @param T $v */
                        public function add(mixed $v): void { $this->items[] = $v; }
                        public function addCat(): void { $this->items[] = new Cat(); }
                        /** @return list<T> */
                        public function all(): array { return $this->items; }
                    }
                    function bag(): void {
                        $bag = new Bag();
                        $bag->addCat();
                        $bag->add(new Puppy());
                        foreach ($bag->all() as $x) { $x->bark(); }
                    }',
                'error_message' => 'Cat::bark does not exist',
                'error_levels' => [],
                'php_version' => '8.1',
            ],

            // overrides
            'overrideTemplateLowerBoundIsNotTheParentsBinding' => [
                'code' => self::ANIMALS . '
                    /** @template T */
                    abstract class BaseFactory {
                        /**
                         * @template U super T
                         * @param U $value
                         * @return U
                         */
                        abstract public function make(mixed $value): mixed;
                    }
                    /**
                     * @template V
                     * @extends BaseFactory<Cat>
                     */
                    final class GenericFactory extends BaseFactory {
                        /**
                         * @template U super V
                         * @param U $value
                         * @return U
                         */
                        public function make(mixed $value): mixed { return $value; }
                    }',
                'error_message' => 'is not within the lower bound Cat of the corresponding template of parent method',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'overrideAddsLowerBoundToCallbackTemplate' => [
                'code' => self::ANIMALS . '
                    class Runner {
                        /**
                         * @template T
                         * @param callable(T): void $f
                         */
                        public function run(callable $f): void {}
                    }
                    final class CatRunner extends Runner {
                        /**
                         * @template T super Cat
                         * @param callable(T): void $f
                         */
                        public function run(callable $f): void { $f(new Cat()); }
                    }',
                'error_message' => 'which is not within the lower bound never of the corresponding template of parent method',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'overrideRaisesLowerBoundOfBoundClassTemplate' => [
                'code' => self::ANIMALS . '
                    /** @template T */
                    class Base {
                        /**
                         * @template U super T
                         * @param U $u
                         * @return U
                         */
                        public function keep(mixed $u): mixed { return $u; }
                    }
                    /** @extends Base<Kitten> */
                    final class KittenBase extends Base {
                        /**
                         * @template U super Cat
                         * @param U $u
                         * @return U
                         */
                        public function keep(mixed $u): mixed { return $u; }
                    }',
                'error_message' => 'is not within the lower bound Kitten of the corresponding template',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
