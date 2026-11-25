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
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

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
}
