<?php

declare(strict_types=1);

namespace Psalm\Tests\Type;

use Psalm\Context;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Internal\TypeVisitor\FromDocblockSetter;
use Psalm\Tests\TestCase;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TString;
use Psalm\Type\MutableUnion;
use Psalm\Type\Union;

final class FromDocblockTest extends TestCase
{
    public function testSetTypesKeepsTheFlagOfTheUnion(): void
    {
        $union = new Union([new TInt()], ['from_docblock' => true]);

        self::assertTrue($union->setTypes([new TInt(), new TString()])->from_docblock);
        self::assertTrue($union->getBuilder()->setTypes([new TString()])->freeze()->from_docblock);
    }

    public function testSetFromDocblockFalseStaysFalseWhenTheTypesChange(): void
    {
        $union = (new Union([new TInt(true)]))->setFromDocblock(false);

        self::assertFalse($union->from_docblock);
        self::assertFalse($union->setTypes([new TInt(), new TString()])->from_docblock);
        self::assertFalse($union->getBuilder()->addType(new TString())->freeze()->from_docblock);
    }

    public function testFromDocblockSetterSetsTheFlagOfAMutableUnion(): void
    {
        $union = new MutableUnion([new TInt(true)], ['from_docblock' => true]);

        (new FromDocblockSetter(false))->traverse($union);

        self::assertFalse($union->from_docblock);
    }

    public function testCombiningWithMixedIsFromDocblockWhateverTheOrder(): void
    {
        self::assertTrue(TypeCombiner::combine([new TInt(true), new TMixed()], null, false, false)->from_docblock);
        self::assertTrue(TypeCombiner::combine([new TMixed(), new TInt(true)], null, false, false)->from_docblock);
    }

    public function testTemplateBoundOfAClassWithoutExtendsIsNotFromDocblock(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                /** @template T of int|string = int */
                class A {}

                class B extends A {}',
        );

        $this->analyzeFile('somefile.php', new Context());

        $type = $this->project_analyzer->getCodebase()->classlike_storage_provider->get('B')->template_extended_params['A']['T'];

        self::assertFalse($type->from_docblock);
        self::assertFalse($type->getAtomicTypes()['int']->from_docblock);
    }

    public function testOmittedTemplateOfExtendsIsFromDocblock(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @template T
                 * @template U = int
                 */
                class A {}

                /** @extends A<string> */
                class B extends A {}',
        );

        $this->analyzeFile('somefile.php', new Context());

        $type = $this->project_analyzer->getCodebase()->classlike_storage_provider->get('B')->template_extended_params['A']['U'];

        self::assertTrue($type->from_docblock);
        self::assertTrue($type->getAtomicTypes()['int']->from_docblock);
    }

    public function testArrayKeyOfADocblockArrayMatchingTheSignatureIsNotFromDocblock(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    /** @param array<array-key, int> $a */
                    public function m(array $a): void {}
                }',
        );

        $this->analyzeFile('somefile.php', new Context());

        $type = $this->project_analyzer->getCodebase()->classlike_storage_provider->get('A')->methods['m']->params[0]->type;
        self::assertNotNull($type);

        $array = $type->getAtomicTypes()['array'];
        self::assertInstanceOf(TArray::class, $array);
        self::assertFalse($array->from_docblock);
        self::assertFalse($array->type_params[0]->from_docblock);
        self::assertFalse($array->type_params[0]->getAtomicTypes()['array-key']->from_docblock);
        self::assertTrue($array->type_params[1]->from_docblock);
    }
}
