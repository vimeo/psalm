<?php

declare(strict_types=1);

namespace Psalm\Tests\Type;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Tests\TestCase;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

final class NamedObjectTest extends TestCase
{
    #[DataProvider('providerSetValueIsStatic')]
    public function testSetValueIsStatic(
        TNamedObject $type,
        bool $is_static,
        ?bool $is_static_resolved,
        bool $expected_is_static_resolved,
    ): void {
        $changed = $type->setValueIsStatic('B', $is_static, $is_static_resolved);

        $this->assertSame('B', $changed->value);
        $this->assertSame($is_static, $changed->is_static);
        $this->assertSame($expected_is_static_resolved, $changed->is_static_resolved);
        $this->assertSame($type::class, $changed::class);
    }

    /**
     * @return iterable<string, array{TNamedObject, bool, ?bool, bool}>
     * @psalm-pure
     */
    public static function providerSetValueIsStatic(): iterable
    {
        $bound_static = (new TNamedObject('A'))->setIsStatic(true, true);
        $unbound_static = new TNamedObject('A', true);

        yield 'null keeps a bound static bound' => [$bound_static, false, null, true];
        yield 'null keeps an unbound static unbound' => [$unbound_static, true, null, false];
        yield 'null keeps a non-static object unbound' => [new TNamedObject('A'), true, null, false];
        yield 'explicit false unbinds' => [$bound_static, true, false, false];
        yield 'explicit true binds' => [$unbound_static, false, true, true];
        yield 'generic object keeps a bound static bound' => [
            (new TGenericObject('A', [new Union([new TNamedObject('C')])]))->setIsStatic(true, true),
            false,
            null,
            true,
        ];
    }
}
