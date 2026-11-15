<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Type;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Codebase;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Tests\TestCase;
use Psalm\Type;

final class TypeExpanderTest extends TestCase
{
    private Codebase $codebase;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->codebase = $this->project_analyzer->getCodebase();
    }

    #[DataProvider('providerTypesWithNothingToExpand')]
    public function testKeepsTypeWithNothingToExpand(string $type_string): void
    {
        $type = Type::parseString($type_string);

        $this->assertSame($type, TypeExpander::expandUnion($this->codebase, $type, 'Exception', null, null));
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function providerTypesWithNothingToExpand(): iterable
    {
        yield 'scalar' => ['int'];
        yield 'union' => ['int|null|string'];
        yield 'object' => ['Exception|null'];
        yield 'generic' => ['array<int, string>'];
        yield 'nested shape' => ['list<array{a: int, b?: Exception}>'];
        yield 'callable' => ['callable(int, string=): Exception'];
    }

    public function testRebuildsTypeWithSomethingToExpand(): void
    {
        $type = Type::parseString('array<int, self>|null');

        $expanded = TypeExpander::expandUnion($this->codebase, $type, 'Exception', null, null);

        $this->assertNotSame($type, $expanded);
        $this->assertSame('array<int, Exception>|null', $expanded->getId());
    }

    public function testResetsFlagsOfKeptTypeLikeARebuiltType(): void
    {
        $type = Type::parseString('int')->setProperties([
            'from_docblock' => true,
            'possibly_undefined' => true,
            'from_calculation' => true,
            'failed_reconciliation' => true,
            'ignore_isset' => true,
            'different' => true,
        ]);

        $expanded = TypeExpander::expandUnion($this->codebase, $type, null, null, null);

        $this->assertTrue($expanded->from_docblock);
        $this->assertTrue($expanded->possibly_undefined);
        $this->assertFalse($expanded->from_calculation);
        $this->assertFalse($expanded->failed_reconciliation);
        $this->assertFalse($expanded->ignore_isset);
        $this->assertFalse($expanded->different);
    }
}
