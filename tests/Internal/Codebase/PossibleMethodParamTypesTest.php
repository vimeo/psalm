<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\MethodIdentifier;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Union;

final class PossibleMethodParamTypesTest extends TestCase
{
    /**
     * @param non-empty-list<Union> $argument_types
     */
    #[DataProvider('providerArgumentTypes')]
    public function testCombinesArgumentTypes(array $argument_types, string $expected_type): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $method_id = new MethodIdentifier('Foo', 'bar');

        foreach ($argument_types as $argument_type) {
            $codebase->analyzer->addPossibleMethodParamType($codebase, $method_id, 0, $argument_type);
        }

        $this->assertSame($expected_type, $codebase->analyzer->possible_method_param_types['foo::bar'][0]->getId());
    }

    /**
     * @return iterable<string, array{non-empty-list<Union>, string}>
     */
    public static function providerArgumentTypes(): iterable
    {
        yield 'mixedAbsorbsLaterTypes' => [
            [Type::getMixed(), Type::getString(), Type::getInt()],
            'mixed',
        ];
        yield 'mixedAbsorbsEarlierTypes' => [
            [Type::getString(), Type::getMixed()],
            'mixed',
        ];
        yield 'failedReconciliationMixedIsDropped' => [
            [Type::getMixed()->setProperties(['failed_reconciliation' => true]), Type::getInt()],
            'int',
        ];
        yield 'loopIssetMixedIsDropped' => [
            [new Union([new TMixed(true)]), Type::getInt()],
            'int',
        ];
        yield 'nonEmptyMixedIsCombined' => [
            [new Union([new TNonEmptyMixed()]), Type::getInt()],
            'int|non-empty-mixed',
        ];
        yield 'loopIssetMixedComingLastIsDropped' => [
            [Type::getInt(), new Union([new TMixed(true)])],
            'int',
        ];
    }
}
