<?php

declare(strict_types=1);

namespace Psalm\Tests\Type;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic\TAnonymousClassInstance;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

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

    /**
     * @psalm-pure
     * @return iterable<string, array{string, string}>
     */
    public static function differentIntersections(): iterable
    {
        yield 'iterable and its intersection' => ['iterable&Countable', 'iterable'];
        yield 'object shape and its intersection' => ['object{a: int}&Countable', 'object{a: int}'];
        yield 'iterables with other intersected types' => [
            'iterable<int, string>&Countable',
            'iterable<int, string>&Traversable',
        ];
        yield 'named object and its intersection' => ['Foo&Countable', 'Foo'];
        yield 'generic object and its intersection' => ['Foo<int>&Countable', 'Foo<int>'];
        yield 'intersected iterables with other type parameters' => [
            'Foo&iterable<mixed, int>',
            'Foo&iterable<mixed, string>',
        ];
        yield 'intersected types in another order' => ['Foo&Bar&Countable', 'Foo&Countable&Bar'];
        yield 'other named objects' => ['Foo', 'Bar'];
        yield 'other generic objects' => ['Foo<int>', 'Bar<int>'];
    }

    #[DataProvider('differentIntersections')]
    public function testIntersectionsWithOtherTypesAreNotEqual(string $a, string $b): void
    {
        self::assertEquality(false, Type::parseString($a), Type::parseString($b));
        self::assertEquality(false, Type::parseString($b), Type::parseString($a));
        self::assertFalse(Type::parseString($a)->getSingleAtomic()->equals(Type::parseString($b)->getSingleAtomic(), true));
        self::assertFalse(Type::parseString($b)->getSingleAtomic()->equals(Type::parseString($a)->getSingleAtomic(), true));
    }

    /**
     * @psalm-pure
     * @return iterable<string, array{string}>
     */
    public static function intersections(): iterable
    {
        yield 'iterable' => ['iterable&Countable'];
        yield 'object shape' => ['object{a: int}&Countable'];
        yield 'named object' => ['Foo&Bar&Countable'];
        yield 'generic object' => ['Foo<int>&Countable'];
        yield 'intersected iterable' => ['Foo&iterable<mixed, int>'];
    }

    #[DataProvider('intersections')]
    public function testIntersectionIsEqualToTheSameIntersection(string $type): void
    {
        self::assertEquality(true, Type::parseString($type), Type::parseString($type));
        self::assertTrue(Type::parseString($type)->getSingleAtomic()->equals(
            Type::parseString($type)->getSingleAtomic(),
            true,
        ));
    }

    public function testTemplateParamIsNotEqualToItsIntersection(): void
    {
        $template = new TTemplateParam('T', Type::getMixed(), 'fn-f');
        $intersection = new TTemplateParam('T', Type::getMixed(), 'fn-f', ['Countable' => new TNamedObject('Countable')]);

        self::assertFalse($template->equals($intersection, true));
        self::assertFalse($intersection->equals($template, true));
        self::assertTrue($intersection->equals(
            new TTemplateParam('T', Type::getMixed(), 'fn-f', ['Countable' => new TNamedObject('Countable')]),
            true,
        ));
        self::assertEquality(false, new Union([$template]), new Union([$intersection]));
    }

    public function testCallableObjectIsNotEqualToItsIntersection(): void
    {
        $callable_object = new TCallableObject();
        $intersection = $callable_object->addIntersectionType(new TNamedObject('Countable'));

        self::assertFalse($callable_object->equals($intersection, true));
        self::assertFalse($intersection->equals($callable_object, true));
        self::assertTrue($intersection->equals(
            (new TCallableObject())->addIntersectionType(new TNamedObject('Countable')),
            true,
        ));
        self::assertEquality(false, new Union([$callable_object]), new Union([$intersection]));
    }

    public function testCasesOfAnEnumAreNotEqual(): void
    {
        self::assertFalse((new TEnumCase('E', 'A'))->equals(new TEnumCase('E', 'B'), true));
        self::assertTrue((new TEnumCase('E', 'A'))->equals(new TEnumCase('E', 'A'), true));
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
     * @psalm-pure
     * @return iterable<string, array{string}>
     */
    public static function publicFlags(): iterable
    {
        foreach ((new ReflectionClass(Union::class))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            yield $property->getName() => [$property->getName()];
        }
    }

    #[DataProvider('publicFlags')]
    public function testBuilderAndFreezeKeepEveryFlag(string $flag): void
    {
        $union = new Union([new TInt()]);
        $node = DataFlowNode::getForPropertyFetch('Foo::$bar');
        $type = (new ReflectionProperty(Union::class, $flag))->getType();
        $value = match ($type instanceof ReflectionNamedType ? $type->getName() : null) {
            'bool' => !$union->{$flag},
            'array' => [$node->id => $node],
            'int' => 5,
            default => 'Foo',
        };
        /** @psalm-suppress ArgumentTypeCoercion The flag is only known at runtime */
        $union = $union->setProperties([$flag => $value]);

        $builder = $union->getBuilder();
        $frozen = $builder->freeze();

        // the cache of the type is not carried over
        $expected = $flag === 'checked' ? false : $value;
        self::assertSame($expected, $builder->{$flag});
        self::assertSame($expected, $frozen->{$flag});
    }

    public function testBuilderAndFreezeDeriveTheFlagsOfTheTypes(): void
    {
        self::assertTrue((new Union([new TNever()]))->getBuilder()->freeze()->explicit_never);
        self::assertTrue((new Union([new TInt(true)]))->getBuilder()->freeze()->from_docblock);
        self::assertTrue(Type::getInt()->getBuilder()->setTypes([new TInt(true)])->freeze()->from_docblock);

        $builder = Type::getInt()->getBuilder();
        $builder->from_docblock = true;
        self::assertTrue($builder->freeze()->from_docblock);
        self::assertFalse(Type::getInt()->getBuilder()->freeze()->from_docblock);
        self::assertFalse(Type::getInt()->getBuilder()->freeze()->explicit_never);
    }

    public function testBuilderAndFreezeKeepTheTypesAndTheLiteralsOfTheUnion(): void
    {
        $union = new Union([
            Type::getAtomicStringFromLiteral('a'),
            new TLiteralInt(1),
            new TLiteralFloat(1.5),
            new TClassString('Foo', new TNamedObject('Foo')),
            new TNull(),
        ]);

        foreach ([$union->getBuilder(), $union->getBuilder()->freeze()] as $copy) {
            self::assertSame($union->getAtomicTypes(), $copy->getAtomicTypes());
            self::assertSame($union->getLiteralStrings(), $copy->getLiteralStrings());
            self::assertSame($union->getLiteralInts(), $copy->getLiteralInts());
            self::assertSame($union->getLiteralFloats(), $copy->getLiteralFloats());
            self::assertSame($union->getId(), $copy->getId());
        }
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
