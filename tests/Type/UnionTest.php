<?php
namespace Psalm\Tests\Type;

use PHPUnit\Framework\TestCase;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

/** @covers \Psalm\Type\Union */
class UnionTest extends TestCase
{
    /** @dataProvider removedTypes */
    public function testRemovingTypeFromUnion(Union $unionType, string $removedType, string $expectedType): void
    {
        $unionType->removeType($removedType);

        self::assertSame($expectedType, $unionType->__toString());
    }

    /** @psalm-return non-empty-array<string, array{Union, string, string}> */
    public function removedTypes(): array
    {
        return [
            'true - true => ∅' => [
                new Union([new TTrue()]),
                'true',
                '',
            ],
            'true - false => false' => [
                new Union([new TTrue()]),
                'false',
                'true',
            ],
            'bool - true => false' => [
                new Union([new TBool()]),
                'true',
                'false',
            ],
            'false - false => ∅' => [
                new Union([new TTrue()]),
                'true',
                '',
            ],
            'false - true => false' => [
                new Union([new TFalse()]),
                'true',
                'false',
            ],
            'bool \ false => true' => [
                new Union([new TBool()]),
                'false',
                'true',
            ],
        ];
    }
}
