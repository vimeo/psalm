<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

final class TypeIntersectionTest extends TestCase
{
    public function testArrayIntersectionPreservesGenericElements(): void
    {
        $value = new Union([new TTemplateParam('T', Type::getMixed(), 'test')]);
        $generic_array = new Union([new TArray([Type::getArrayKey(), $value])]);
        $non_empty_array = new Union([new TNonEmptyArray([Type::getInt(), Type::getMixed()], count: 3)]);

        foreach ([[$generic_array, $non_empty_array], [$non_empty_array, $generic_array]] as [$left, $right]) {
            $intersection = Type::intersectUnionTypes($left, $right, $this->project_analyzer->getCodebase());
            $this->assertNotNull($intersection);
            $array = $intersection->getSingleAtomic();
            $this->assertInstanceOf(TNonEmptyArray::class, $array);
            $this->assertSame(3, $array->count);
            $this->assertSame('int', $array->type_params[0]->getId());
            $this->assertSame($value->getId(), $array->type_params[1]->getId());
        }
    }

    public function testArrayIntersectionPreservesNestedGenericElements(): void
    {
        $value = new Union([new TTemplateParam('T', Type::getMixed(), 'test')]);
        $generic_array = new Union([new TArray([
            Type::getInt(),
            new Union([new TArray([Type::getInt(), $value])]),
        ])]);

        $bound = Type::parseString('array<array-key, array<array-key, mixed>>');
        foreach ([[$generic_array, $bound], [$bound, $generic_array]] as [$left, $right]) {
            $intersection = Type::intersectUnionTypes($left, $right, $this->project_analyzer->getCodebase());
            $this->assertNotNull($intersection);
            $this->assertSame($generic_array->getId(), $intersection->getId());
        }
    }

    public function testArrayIntersectionRejectsIncompatibleElements(): void
    {
        $integers = Type::parseString('non-empty-array<int, int>');
        $strings = Type::parseString('non-empty-array<int, string>');

        foreach ([[$integers, $strings], [$strings, $integers]] as [$left, $right]) {
            $this->assertNull(Type::intersectUnionTypes($left, $right, $this->project_analyzer->getCodebase()));
        }
    }
}
