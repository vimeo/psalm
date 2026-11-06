<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Stubs;

use PhpParser\Node\Identifier;
use Psalm\Internal\Stubs\Generator\StubsGenerator;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Union;

final class StubsGeneratorTest extends TestCase
{
    public function testAScalarHasNoPhpType(): void
    {
        self::assertNull(StubsGenerator::getParserTypeFromPsalmType(new Union([new TScalar(), new TNull()])));
    }

    public function testAnIntIsAnIdentifier(): void
    {
        $type = StubsGenerator::getParserTypeFromPsalmType(Type::getInt());

        self::assertInstanceOf(Identifier::class, $type);
        self::assertSame('int', $type->name);
    }
}
