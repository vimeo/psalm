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

    public function testTheGeneratedStubHasTheTypesAndDefaultsOfConstantsTheScannerLeftUnresolved(): void
    {
        $this->testConfig->add_param_default_to_docblock_type = true;
        // Nope::MODE is undefined
        $this->testConfig->throw_exception = false;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param string $v */
                function f($v = A::NUM): void {}

                class Base {
                    public const MODE = 3;
                }

                final class Child extends Base {
                    /** @param string $v */
                    public function m($v = A::NUM): void {}

                    public function p(int $m = parent::MODE): void {}

                    public function u(int $m = Nope::MODE): void {}

                    public function n(int $arg = ~Z::NUM): void {}

                    public function o(array $args = [[~Z::NUM]]): void {}
                }

                function g(int $arg = ~Z::NUM): void {}

                function h(array $args = [[~Z::NUM]]): void {}

                function k(array $args = [[Z::NUM]]): void {}

                final class Z {
                    public const NUM = 5;
                }

                final class A {
                    public const NUM = 10;
                }',
        );
        $this->analyzeFile('somefile.php', new Context());

        $codebase = $this->project_analyzer->getCodebase();
        $stub = StubsGenerator::getAll(
            $codebase,
            $codebase->classlike_storage_provider,
            $codebase->file_storage_provider,
        );

        // the function and the method
        self::assertSame(2, substr_count($stub, '@param 10|string $v'));
        self::assertStringContainsString('function f($v = 10)', $stub);
        self::assertStringContainsString('function m($v = 10)', $stub);
        self::assertStringContainsString('function p(int $m = 3)', $stub);
        self::assertStringContainsString('function u(int $m)', $stub);
        // a default whose value isn't a literal is left out
        self::assertStringContainsString('function n(int $arg)', $stub);
        self::assertStringContainsString('function g(int $arg)', $stub);
        self::assertStringContainsString('function o(array $args)', $stub);
        self::assertStringContainsString('function h(array $args)', $stub);
        self::assertStringContainsString('function k(array $args = [[5]])', $stub);
    }

    public function testTheGeneratedStubKeepsADefaultOfTheConstantOfAClassDeclaredLater(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                function late(int $i = A::Y): void {}

                final class A {
                    public const Y = 10;
                }',
        );
        $this->analyzeFile('somefile.php', new Context());

        $codebase = $this->project_analyzer->getCodebase();
        $stub = StubsGenerator::getAll(
            $codebase,
            $codebase->classlike_storage_provider,
            $codebase->file_storage_provider,
        );

        self::assertStringContainsString('function late(int $i = 10)', $stub);
    }

    public function testTheGeneratedStubHasTheGlobalConstantsTheScannerLeftUnresolved(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                const FLAG = A::FLAG;
                const ELEVEN = BASE + 1;
                const BASE = 10;
                define("DEFINED_FLAG", A::FLAG);
                // known, without a value a stub can write, as when the class is declared first
                const NEGATED = ~A::NUM;
                define("DEFINED_NEGATED", ~A::NUM);
                const PATH = ROOT . "/src";
                define("ROOT", __DIR__);

                final class A {
                    public const FLAG = true;
                    public const NUM = 10;
                }',
        );
        $this->analyzeFile('somefile.php', new Context());

        $codebase = $this->project_analyzer->getCodebase();
        $stub = StubsGenerator::getAll(
            $codebase,
            $codebase->classlike_storage_provider,
            $codebase->file_storage_provider,
        );

        self::assertStringContainsString('const FLAG = true;', $stub);
        self::assertStringContainsString('const ELEVEN = 11;', $stub);
        self::assertStringContainsString('const DEFINED_FLAG = true;', $stub);
        self::assertStringContainsString("const NEGATED = 'Psalm could not infer this type';", $stub);
        self::assertStringContainsString("const DEFINED_NEGATED = 'Psalm could not infer this type';", $stub);
        self::assertStringContainsString("const PATH = 'Psalm could not infer this type';", $stub);
    }

    public function testAnIntIsAnIdentifier(): void
    {
        $type = StubsGenerator::getParserTypeFromPsalmType(Type::getInt());

        self::assertInstanceOf(Identifier::class, $type);
        self::assertSame('int', $type->name);
    }
}
