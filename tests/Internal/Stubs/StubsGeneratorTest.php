<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Stubs;

use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\UnionType;
use Psalm\Context;
use Psalm\Internal\Stubs\Generator\StubsGenerator;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

use function array_map;
use function substr_count;

final class StubsGeneratorTest extends TestCase
{
    public function testAScalarIsAUnionOfItsFourTypes(): void
    {
        $type = StubsGenerator::getParserTypeFromPsalmType(new Union([new TScalar(), new TNull()]));

        self::assertInstanceOf(UnionType::class, $type);
        self::assertSame(
            ['string', 'int', 'float', 'bool', 'null'],
            array_map(static fn(Identifier|Name|IntersectionType $part): string => $part instanceof Identifier ? $part->name : '', $type->types),
        );
    }

    public function testANativeUnionKeepsEveryType(): void
    {
        $type = StubsGenerator::getParserTypeFromPsalmType(new Union([new TInt(), new TString()]));

        self::assertInstanceOf(UnionType::class, $type);
        self::assertCount(2, $type->types);
    }

    public function testATypeWithoutAPhpTypeIsLeftOut(): void
    {
        self::assertNull(StubsGenerator::getParserTypeFromPsalmType(new Union([new TInt(), new TMixed()])));
    }

    public function testAScalarWithoutAPhpTypeIsLeftOut(): void
    {
        self::assertNull(StubsGenerator::getParserTypeFromPsalmType(new Union([new TNamedObject('Foo'), new TArrayKey()])));
    }

    public function testTheGeneratedStubKeepsTheTypeOfAScalar(): void
    {
        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                function f(string|int|float|bool|null $x): void {}',
        );
        $this->analyzeFile('somefile.php', new Context());

        $codebase = $this->project_analyzer->getCodebase();
        $stub = StubsGenerator::getAll(
            $codebase,
            $codebase->classlike_storage_provider,
            $codebase->file_storage_provider,
        );

        self::assertStringContainsString('function f(string|int|float|bool|null $x)', $stub);
    }

    public function testTheGeneratedStubKeepsTheIntersectionOfAThrows(): void
    {
        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                interface Marker {}

                /**
                 * @throws Marker&\\Throwable
                 * @throws \\RuntimeException
                 */
                function f(): void {}

                class C {
                    /**
                     * @throws Marker&\\Throwable
                     */
                    public function m(): void {}
                }',
        );
        $this->analyzeFile('somefile.php', new Context());

        $codebase = $this->project_analyzer->getCodebase();
        $stub = StubsGenerator::getAll(
            $codebase,
            $codebase->classlike_storage_provider,
            $codebase->file_storage_provider,
        );

        self::assertSame(3, substr_count($stub, '@throws'));
        self::assertSame(2, substr_count($stub, '@throws Marker&Throwable'));
        self::assertSame(1, substr_count($stub, '@throws RuntimeException'));
    }

    public function testAnIntIsAnIdentifier(): void
    {
        $type = StubsGenerator::getParserTypeFromPsalmType(Type::getInt());

        self::assertInstanceOf(Identifier::class, $type);
        self::assertSame('int', $type->name);
    }

    public function testAnIntersectionIsKept(): void
    {
        $stub = $this->getStub('<?php
            interface I {}
            interface J {}
            function f(I&J $x): (I&J)|null { return rand(0, 1) ? $x : null; }
            function g((I&J)|Countable $x): ?Countable { return null; }');

        self::assertStringContainsString('function f(\\I&\\J $x): (\\I&\\J)|null', $stub);
        self::assertStringContainsString('function g(\\Countable|(\\I&\\J) $x): ?\\Countable', $stub);
    }

    public function testTypesThatStandAloneAreKept(): void
    {
        $stub = $this->getStub('<?php
            class Base {}
            class A extends Base {
                public function yes(): true { return true; }
                public function no(): int|false { return false; }
                public function up(): parent { return $this; }
                public function same(): static { return $this; }
                public function maybe(): ?static { return null; }
                public function stop(): never { throw new Exception(); }
                public function nothing(): void {}
                public function any(): mixed { return 1; }
                public function nul(): null { return null; }
                public function call(callable $c): void {}
            }');

        self::assertStringContainsString('function yes(): true', $stub);
        self::assertStringContainsString('function no(): false|int', $stub);
        self::assertStringContainsString('function up(): parent', $stub);
        self::assertStringContainsString('function same(): static', $stub);
        self::assertStringContainsString('function maybe(): ?static', $stub);
        self::assertStringContainsString('function stop(): never', $stub);
        self::assertStringContainsString('function nothing(): void', $stub);
        self::assertStringContainsString('function any(): mixed', $stub);
        self::assertStringContainsString('function nul(): null', $stub);
        self::assertStringContainsString('function call(callable $c): void', $stub);
    }

    public function testAnEnumIsAnEnumWithItsCases(): void
    {
        $stub = $this->getStub('<?php
            interface L {}
            enum Suit: string implements L {
                case Hearts = "h";
                case Spades = "s";
                const DEFAULT = self::Hearts;
                const Suit OTHER = Suit::Spades;
                public function label(): string { return $this->value; }
            }
            enum Unit { case A; }');

        self::assertStringContainsString(
            'enum Suit : string implements \\L
    {
        case Hearts = \'h\';
        case Spades = \'s\';
        public const DEFAULT = \\Suit::Hearts;
        public const \\Suit OTHER = \\Suit::Spades;
        public function label(): string',
            $stub,
        );
        self::assertStringContainsString('enum Unit
    {
        case A;
    }', $stub);
        self::assertStringNotContainsString('$name', $stub);
        self::assertStringNotContainsString('$value', $stub);
    }

    public function testANewDefaultIsKept(): void
    {
        $stub = $this->getStub('<?php
            final class Dep {}
            function f(Dep $dep = new Dep()): void {}');

        self::assertStringContainsString('function f(\\Dep $dep = new \\Dep()): void', $stub);
    }

    public function testModifiersAndTypedConstantsAreKept(): void
    {
        $stub = $this->getStub('<?php
            interface K { const string KEY = "k"; }
            abstract class Base implements K {
                final public const int MAX = 3;
                abstract public function go(): void;
                final public function stop(): void {}
            }
            readonly class A extends Base {
                const string NAME = "a";
                public function __construct(public int $id) {}
                public function go(): void {}
            }
            final class B {
                public readonly int $ro;
                public function __construct(public readonly string $promoted) { $this->ro = 1; }
                public function f(): void {}
            }');

        self::assertStringContainsString('abstract class Base implements \\K', $stub);
        self::assertStringContainsString('final public const int MAX = 3;', $stub);
        self::assertStringContainsString('final public function stop(): void', $stub);
        self::assertStringContainsString('readonly class A extends \\Base', $stub);
        self::assertStringContainsString('public const string NAME = \'a\';', $stub);
        self::assertStringContainsString('public const string KEY = \'k\';', $stub);
        // in a readonly class, the property is readonly without the modifier
        self::assertStringContainsString('public int $id;', $stub);
        self::assertStringContainsString('final class B', $stub);
        self::assertStringContainsString('public readonly int $ro;', $stub);
        self::assertStringContainsString('public readonly string $promoted;', $stub);
        // the methods of a final class are final already
        self::assertStringContainsString('    public function f(): void', $stub);
    }

    public function testAClassKeepsItsInterfacesAndNotTheConstantsItInherits(): void
    {
        $stub = $this->getStub('<?php
            interface K { const string KEY = "k"; }
            class Base { final public const int MAX = 3; }
            class A extends Base implements K {}');

        self::assertStringContainsString('class A extends \\Base implements \\K
    {
    }', $stub);
        self::assertSame(1, substr_count($stub, 'MAX ='));
        self::assertSame(1, substr_count($stub, 'KEY ='));
    }

    public function testAClassDoesNotRedeclareTheConstantsOfABuiltInParent(): void
    {
        $stub = $this->getStub('<?php
            /** @psalm-suppress ImmutableDependency, MissingTemplateParam */
            class MyAO extends ArrayObject {}
            class MyDate extends DateTimeImmutable {}
            /** @psalm-suppress ImmutableDependency, MissingTemplateParam */
            class MyOwn extends ArrayObject {
                const int STD_PROP_LIST = 1;
            }');

        self::assertStringContainsString('class MyAO extends \\ArrayObject
    {
    }', $stub);
        self::assertStringContainsString('class MyDate extends \\DateTimeImmutable
    {
    }', $stub);
        self::assertStringContainsString('class MyOwn extends \\ArrayObject
    {
        public const int STD_PROP_LIST = 1;
    }', $stub);
    }

    public function testBuiltInClassesAreLeftOut(): void
    {
        $stub = $this->getStub('<?php
            class E extends BadMethodCallException {}
            function f(Closure $c, Countable $d): ArrayObject { return new ArrayObject(); }');

        self::assertStringContainsString('class E extends \\BadMethodCallException', $stub);
        self::assertDoesNotMatchRegularExpression('/(class|interface) (BadMethodCallException|Closure|Countable|ArrayObject)\\b/', $stub);
    }

    private function getStub(string $code): string
    {
        $this->project_analyzer->setPhpVersion('8.4', 'tests');

        $this->addFile('somefile.php', $code);
        $this->analyzeFile('somefile.php', new Context());

        $codebase = $this->project_analyzer->getCodebase();

        return StubsGenerator::getAll(
            $codebase,
            $codebase->classlike_storage_provider,
            $codebase->file_storage_provider,
        );
    }
}
