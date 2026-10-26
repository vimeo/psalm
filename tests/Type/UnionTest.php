<?php

declare(strict_types=1);

namespace Psalm\Tests\Type;

use InvalidArgumentException;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic\TAnonymousClassInstance;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

final class UnionTest extends TestCase
{

    public function testWillDetectSingleLiteralFloat(): void
    {
        $literalFloat = new TLiteralFloat(1.0);
        $union = new Union([$literalFloat]);

        self::assertTrue($union->isSingleFloatLiteral());
        self::assertTrue($union->hasLiteralFloat());
        self::assertSame($literalFloat, $union->getSingleFloatLiteral());
    }

    public function testWillThrowInvalidArgumentExceptionWhenSingleFloatLiteralIsRequestedButNoneExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $union = new Union([new TFloat()]);
        $union->getSingleFloatLiteral();
    }

    public function testStaticObjectIsNotEqualToItsClass(): void
    {
        self::assertEquality(
            false,
            new Union([new TNamedObject('Foo')]),
            new Union([new TNamedObject('Foo', true)]),
        );
        self::assertEquality(
            false,
            new Union([new TNamedObject('Foo', true)]),
            new Union([new TNamedObject('Foo')]),
        );
        self::assertEquality(
            true,
            new Union([new TNamedObject('Foo', true)]),
            new Union([new TNamedObject('Foo', true)]),
        );
        self::assertEquality(
            true,
            new Union([new TNamedObject('Foo')]),
            new Union([new TNamedObject('Foo')]),
        );
    }

    public function testNamedObjectIsNotEqualToAnotherKindOfObjectWithTheSameName(): void
    {
        self::assertEquality(
            false,
            new Union([new TNamedObject('Foo')]),
            new Union([new TAnonymousClassInstance('Foo')]),
        );
    }

    public function testStaticGenericObjectIsNotEqualToItsClass(): void
    {
        self::assertEquality(
            false,
            new Union([new TGenericObject('Foo', [Type::getInt()])]),
            new Union([new TGenericObject('Foo', [Type::getInt()], false, true)]),
        );
        self::assertEquality(
            true,
            new Union([new TGenericObject('Foo', [Type::getInt()], false, true)]),
            new Union([new TGenericObject('Foo', [Type::getInt()], false, true)]),
        );
    }

    public function testListIsNotEqualToAnArrayWithTheSameEntries(): void
    {
        $array = TKeyedArray::make(
            [Type::getString()->setPossiblyUndefined(true)],
            null,
            [Type::getListKey(), Type::getString()],
        );

        self::assertEquality(false, Type::getList(Type::getString()), new Union([$array]));
        self::assertEquality(true, Type::getList(Type::getString()), Type::getList(Type::getString()));
    }

    public function testListIdDoesNotDependOnTheDataFlowOfItsFirstElement(): void
    {
        $node = DataFlowNode::getForPropertyFetch('Foo::$bar');
        $list = self::makeList(Type::getString()->setParentNodes([$node->id => $node]), Type::getString());

        self::assertSame('list<string>', $list->getId());
        self::assertEquality(true, $list, Type::getList(Type::getString()));
    }

    public function testListIdDoesNotDependOnWhetherItsFirstElementComesFromADocblock(): void
    {
        $list = self::makeList(Type::getString()->setFromDocblock(), Type::getString());

        self::assertSame('list<string>', $list->getId());
        self::assertEquality(false, $list, Type::getList(Type::getString()));
    }

    public function testListWhoseFirstElementHasAnotherTypeIsNotGeneric(): void
    {
        $list = self::makeList(Type::getInt(), Type::getString());

        self::assertSame('list{0?: int, ...<string>}', $list->getId());
        self::assertEquality(false, $list, Type::getList(Type::getString()));
    }

    /**
     * Union::equals() compares cached ids when both sides have them, so its result must not change once they are.
     */
    private static function assertEquality(bool $expected, Union $a, Union $b): void
    {
        self::assertSame($expected, $a->equals($b));

        $ids_match = $a->getId(false) === $b->getId(false) && $a->getId() === $b->getId();
        if ($expected) {
            self::assertTrue($ids_match);
        }

        self::assertSame($expected, $a->equals($b));
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function makeList(Union $first, Union $rest): Union
    {
        return new Union([TKeyedArray::make(
            [$first->setPossiblyUndefined(true)],
            null,
            [Type::getListKey(), $rest],
            true,
        )]);
    }
}
