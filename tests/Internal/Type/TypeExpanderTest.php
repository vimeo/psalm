<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Type;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Internal\Type\TypeVariableBounds;
use Psalm\Tests\TestCase;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

use function getcwd;

use const DIRECTORY_SEPARATOR;

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

    public function testCombinesUnionWithNothingToExpand(): void
    {
        $file_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'somefile.php';
        $this->addFile($file_path, '<?php class A {} class B extends A {}');
        $this->analyzeFile($file_path, new Context());

        foreach (['A|B', 'B|A'] as $type_string) {
            $expanded = TypeExpander::expandUnion($this->codebase, Type::parseString($type_string), null, null, null);

            $this->assertSame('A', $expanded->getId());
        }
    }

    public function testRebuildsTypeHoldingATypeVariableWithNewBounds(): void
    {
        $bounds = new TypeVariableBounds([new TemplateBound(Type::getInt())]);
        $type = new Union([
            new TArray([Type::getInt(), new Union([new TTypeVariable('$T', $bounds)])]),
        ]);
        $this->assertSame('array<int, $T:int>', $type->getId());

        $bounds->lower_bounds[] = new TemplateBound(Type::getString());

        $expanded = TypeExpander::expandUnion($this->codebase, $type, null, null, null);

        $this->assertSame('array<int, $T:int|string>', $expanded->getId());
    }

    public function testKeepsANeverBoundTypeVariableNextToAnUnchangedPart(): void
    {
        $variable = new TTypeVariable('$T', new TypeVariableBounds([new TemplateBound(Type::getNever())]));
        $type = new Union([$variable, new TNull()]);

        $expanded = TypeExpander::expandUnion($this->codebase, $type, null, null, null);

        $this->assertTrue($expanded->hasType($variable->getKey()));
        $this->assertTrue($expanded->isNullable());
    }

    public function testKeepsANeverBoundTypeVariableNextToAnExpandedPart(): void
    {
        $variable = new TTypeVariable('$T', new TypeVariableBounds([new TemplateBound(Type::getNever())]));
        $type = new Union([$variable, new TNamedObject('self')]);

        $expanded = TypeExpander::expandUnion($this->codebase, $type, 'Exception', null, null);

        $this->assertTrue($expanded->hasType($variable->getKey()));
        $this->assertTrue($expanded->hasType('Exception'));
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
