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

    public function testAnIntIsAnIdentifier(): void
    {
        $type = StubsGenerator::getParserTypeFromPsalmType(Type::getInt());

        self::assertInstanceOf(Identifier::class, $type);
        self::assertSame('int', $type->name);
    }
}
