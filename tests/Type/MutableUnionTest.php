<?php

declare(strict_types=1);

namespace Psalm\Tests\Type;

use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TUnknownClassString;
use Psalm\Type\MutableUnion;
use Psalm\Type\Union;

use function array_keys;

final class MutableUnionTest extends TestCase
{
    public function testSetTypesUpdatesId(): void
    {
        $union = new MutableUnion([new TInt()]);
        self::assertSame('int', $union->getId());

        $union->setTypes([new TString()]);
        self::assertSame('string', $union->getId());
    }

    public function testRemoveStringLiteralsUpdatesId(): void
    {
        $union = new MutableUnion([new TLiteralString('a'), new TInt()]);
        self::assertSame("'a'|int", $union->getId());

        $union->removeType('string');
        self::assertSame('int', $union->getId());
    }

    public function testRemoveIntLiteralsUpdatesId(): void
    {
        $union = new MutableUnion([new TLiteralInt(1), new TString()]);
        self::assertSame('1|string', $union->getId());

        $union->removeType('int');
        self::assertSame('string', $union->getId());
    }

    public function testRemoveFloatLiteralsUpdatesId(): void
    {
        $union = new MutableUnion([new TLiteralFloat(1.5), new TString()]);
        self::assertSame('float(1.5)|string', $union->getId());

        $union->removeType('float');
        self::assertSame('string', $union->getId());
    }

    public function testRemoveTypedClassString(): void
    {
        $union = new MutableUnion([new TClassString('Foo', new TNamedObject('Foo')), new TInt()]);

        self::assertTrue($union->removeType('class-string<Foo>'));
        self::assertFalse($union->hasLiteralClassString());
        self::assertFalse($union->hasString());
    }

    public function testRemoveStringRemovesInterfaceAndEnumStrings(): void
    {
        $union = new MutableUnion([
            new TClassString('object', null, false, true),
            new TClassString('object', null, false, false, true),
            new TInt(),
        ]);

        $union->removeType('string');
        self::assertSame(['int'], array_keys($union->getAtomicTypes()));
    }

    public function testSubstituteArrayInIterable(): void
    {
        $union = new MutableUnion([new TIterable()]);

        $union->substitute(Type::getArray());
        self::assertSame(['Traversable'], array_keys($union->getAtomicTypes()));
    }

    public function testSubstituteRegistersLiterals(): void
    {
        $union = new MutableUnion([new TInt()]);

        $union->substitute(Type::getInt(), new Union([new TLiteralInt(5)]));
        self::assertTrue($union->isSingleIntLiteral());
    }

    public function testAddStringSubtypeKeepsLiterals(): void
    {
        $union = new MutableUnion([new TLiteralString('')]);

        $union->addType(new TNonEmptyString());
        self::assertSame(['string()', 'string'], array_keys($union->getAtomicTypes()));
        self::assertTrue($union->hasLiteralString());
    }

    public function testAddStringSubtypeDropsContainedLiterals(): void
    {
        $union = new MutableUnion([new TLiteralString('a'), new TInt()]);

        $union->addType(new TNonEmptyString());
        self::assertSame(['int', 'string'], array_keys($union->getAtomicTypes()));

        $union->removeType('string');
        self::assertSame(['int'], array_keys($union->getAtomicTypes()));
    }

    public function testAddNonFalsyStringKeepsTypedClassStrings(): void
    {
        $union = new MutableUnion([new TClassString('Foo', new TNamedObject('Foo'))]);

        $union->addType(new TNonFalsyString());
        self::assertSame(['class-string<Foo>', 'string'], array_keys($union->getAtomicTypes()));
        self::assertTrue($union->hasLiteralClassString());
    }

    public function testAddLowercaseStringKeepsUppercaseLiterals(): void
    {
        $union = new MutableUnion([new TLiteralString('A'), new TLiteralString('b')]);

        $union->addType(new TLowercaseString());
        self::assertSame(['string(A)', 'string'], array_keys($union->getAtomicTypes()));
    }

    public function testAddStringDropsLiteralsAndClassStrings(): void
    {
        $union = new MutableUnion([new TLiteralString('a'), new TClassString('Foo', new TNamedObject('Foo'))]);

        $union->addType(new TString());
        self::assertSame(['string'], array_keys($union->getAtomicTypes()));
        self::assertFalse($union->hasLiteralString());
        self::assertFalse($union->hasLiteralClassString());
    }

    public function testAddStringKeepsTemplateClassStrings(): void
    {
        $union = new MutableUnion([new TTemplateParamClass('U', 'Foo', new TNamedObject('Foo'), 'Bar')]);

        $union->addType(new TString());
        self::assertSame(['class-string<U:Bar>', 'string'], array_keys($union->getAtomicTypes()));

        $union->addType(new TClassString());
        self::assertSame(['class-string<U:Bar>', 'string', 'class-string'], array_keys($union->getAtomicTypes()));
    }

    public function testAddUnknownClassStringKeepsTypedClassStrings(): void
    {
        $union = new MutableUnion([new TClassString('Foo', new TNamedObject('Foo'))]);

        $union->addType(new TUnknownClassString(new TCallableObject()));
        self::assertSame(['class-string<Foo>', 'class-string'], array_keys($union->getAtomicTypes()));
    }

    public function testAddTypedClassString(): void
    {
        $union = new MutableUnion([new TInt()]);

        $union->addType(new TClassString('Foo', new TNamedObject('Foo')));
        self::assertTrue($union->hasLiteralClassString());
    }

    public function testAddClassStringDropsOnlyClassStrings(): void
    {
        $union = new MutableUnion([
            new TLiteralString('a'),
            new TLiteralClassString('Foo'),
            new TClassString('Foo', new TNamedObject('Foo')),
            new TClassString('Foo', new TNamedObject('Foo'), false, true),
        ]);

        $union->addType(new TClassString());
        self::assertSame(
            ['string(a)', 'interface-string<Foo>', 'class-string'],
            array_keys($union->getAtomicTypes()),
        );
    }
}
