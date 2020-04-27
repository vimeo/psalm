<?php
namespace Psalm\Tests;

use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;
use Psalm\Type;
use function array_values;
use function end;

class TypeTest extends TestCase
{
    public function testMakesUnionFromOneAtomicType(): void
    {
        $union = Type::union(new TMixed());
        $atomicTypes = $union->getAtomicTypes();

        $this->assertCount(1, $atomicTypes);
        $this->assertInstanceOf(TMixed::class, end($atomicTypes));
    }

    public function testMakesUnionFromMultipleAtomicTypes(): void
    {
        $union = Type::union(new TMixed(), new TInt(), new TString());
        $atomicTypes = $union->getAtomicTypes();

        $this->assertCount(3, $atomicTypes);
        $this->assertInstanceOf(TMixed::class, array_values($atomicTypes)[0]);
        $this->assertInstanceOf(TInt::class, array_values($atomicTypes)[1]);
        $this->assertInstanceOf(TString::class, array_values($atomicTypes)[2]);
    }
}
