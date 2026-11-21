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

    public function testTheGeneratedStubKeepsTheTraitsAndInterfacesOfAClass(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                namespace App;

                interface I {}

                class P {}

                trait Bar {
                    public function bar(): int { return 1; }
                    public function f(): int { return 1; }
                }

                trait Baz {
                    public function baz(): int { return 2; }
                    public function f(): int { return 2; }
                }

                class Foo extends P implements I {
                    use Bar, Baz {
                        Bar::bar as protected aliased;
                        Baz::f insteadof Bar;
                        baz as private;
                    }
                }',
        );
        $this->analyzeFile('somefile.php', new Context());

        $codebase = $this->project_analyzer->getCodebase();
        $stub = StubsGenerator::getAll(
            $codebase,
            $codebase->classlike_storage_provider,
            $codebase->file_storage_provider,
        );

        self::assertStringContainsString('class Foo extends \\App\\P implements \\App\\I', $stub);
        self::assertStringContainsString('use \\App\\Bar, \\App\\Baz {', $stub);
        self::assertStringContainsString('\\App\\Baz::f insteadof \\App\\Bar;', $stub);
        self::assertStringContainsString('\\App\\Bar::bar as protected aliased;', $stub);
        self::assertStringContainsString('baz as private;', $stub);
    }

    public function testAnIntIsAnIdentifier(): void
    {
        $type = StubsGenerator::getParserTypeFromPsalmType(Type::getInt());

        self::assertInstanceOf(Identifier::class, $type);
        self::assertSame('int', $type->name);
    }
}
