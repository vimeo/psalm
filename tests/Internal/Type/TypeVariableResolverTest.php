<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Type;

use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TypeVariableBounds;
use Psalm\Internal\TypeVisitor\TypeVariableResolver;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

use function hrtime;
use function str_repeat;

final class TypeVariableResolverTest extends TestCase
{
    public function testResolvesAVariableNextToAnotherMemberHoldingIt(): void
    {
        $variable = new TTypeVariable('_0', new TypeVariableBounds([new TemplateBound(Type::getInt())]));
        $type = new Union([
            $variable,
            new TArray([Type::getInt(), new Union([$variable])]),
        ]);

        (new TypeVariableResolver($this->project_analyzer->getCodebase(), construction_site_only: false))
            ->traverse($type);

        $this->assertSame('array<int, int>|int', $type->getId());
    }

    public function testKeepsAVariableWhoseBoundHoldsIt(): void
    {
        $bounds = new TypeVariableBounds([new TemplateBound(new Union([new TGenericObject('Box', [Type::getInt()])]))]);
        $variable = new TTypeVariable('_0', $bounds);
        $bounds->lower_bounds[] = new TemplateBound(new Union([new TGenericObject('Box', [new Union([$variable])])]));
        $type = new Union([$variable]);

        (new TypeVariableResolver($this->project_analyzer->getCodebase(), construction_site_only: false))
            ->traverse($type);

        $this->assertSame('Box<_0:Box<_0>|Box<int>>|Box<int>', $type->getId());
    }

    public function testResolvesAVariableOfTheSameNameFromAnotherTracker(): void
    {
        $inner = new TTypeVariable('_0', new TypeVariableBounds([new TemplateBound(Type::getInt())]));
        $outer = new TTypeVariable('_0', new TypeVariableBounds([
            new TemplateBound(new Union([new TGenericObject('Holder', [new Union([$inner])])])),
        ]));
        $type = new Union([$outer]);

        (new TypeVariableResolver($this->project_analyzer->getCodebase(), construction_site_only: false))
            ->traverse($type);

        $this->assertSame('Holder<int>', $type->getId());
    }

    public function testCombinesTheCountsOfArraysTwoVariablesResolveTo(): void
    {
        $one = new TTypeVariable('_0', new TypeVariableBounds([
            new TemplateBound(new Union([new TNonEmptyArray([Type::getInt(), Type::getInt()], 1)])),
        ]));
        $two = new TTypeVariable('_1', new TypeVariableBounds([
            new TemplateBound(new Union([new TNonEmptyArray([Type::getInt(), Type::getInt()], 2)])),
        ]));
        $type = new Union([$one, $two]);

        (new TypeVariableResolver($this->project_analyzer->getCodebase(), construction_site_only: false))
            ->traverse($type);

        $array = $type->getSingleAtomic();
        $this->assertInstanceOf(TNonEmptyArray::class, $array);
        $this->assertNull($array->count);
    }

    public function testResolvesABoundSharedByTwoVariablesOnce(): void
    {
        // two variables bound to the same type at each of 20 levels
        $type = Type::getInt();
        for ($level = 20; $level > 0; $level--) {
            $bounds = new TypeVariableBounds([new TemplateBound(new Union([new TArray([Type::getInt(), $type])]))]);
            $type = new Union([new TTypeVariable('_a' . $level, $bounds), new TTypeVariable('_b' . $level, $bounds)]);
        }

        $start = hrtime(true);
        (new TypeVariableResolver($this->project_analyzer->getCodebase(), construction_site_only: false))
            ->traverse($type);
        $nanoseconds = hrtime(true) - $start;

        $this->assertSame(str_repeat('array<int, ', 20) . 'int' . str_repeat('>', 20), $type->getId());
        // expanding the bound again for each variable takes 2^20 traversals, tens of seconds
        $this->assertLessThan(2_000_000_000, $nanoseconds);
    }
}
