<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Override;
use Psalm\Internal\Codebase\ClassConstantByWildcardResolver;
use Psalm\Tests\TestCase;
use Psalm\Type\Atomic\TLiteralString;

use function reset;

final class ClassConstantByWildcardResolverTest extends TestCase
{
    private ClassConstantByWildcardResolver $resolver;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ClassConstantByWildcardResolver($this->project_analyzer->getCodebase());
    }

    public function testWillParseAllClassConstants(): void
    {
        $this->addFile(
            'psalm-assert.php',
            '
            <?php
            namespace ReconciliationTest;
            class Foo
            {
                const PREFIX_BAR = \'bar\';
                const PREFIX_BAZ = \'baz\';
                const PREFIX_QOO = Foo::PREFIX_BAR;
            }
            ',
        );
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->scanFiles();
        $resolved = $this->resolver->resolve('ReconciliationTest\\Foo', '*');
        self::assertNotEmpty($resolved);
        foreach ($resolved as $type) {
            self::assertInstanceOf(TLiteralString::class, $type);
            self::assertTrue($type->value === 'bar' || $type->value === 'baz');
        }
    }

    public function testWillParseMatchingClassConstants(): void
    {
        $this->addFile(
            'psalm-assert.php',
            '
            <?php
            namespace ReconciliationTest;
            class Foo
            {
                const BAR = \'bar\';
                const BAZ = \'baz\';
                const QOO = \'qoo\';
            }
            ',
        );
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->scanFiles();
        $resolved = $this->resolver->resolve('ReconciliationTest\\Foo', 'BA*');
        self::assertNotEmpty($resolved);
        foreach ($resolved as $type) {
            self::assertInstanceOf(TLiteralString::class, $type);
            self::assertTrue($type->value === 'bar' || $type->value === 'baz');
        }

        $resolved = $this->resolver->resolve('ReconciliationTest\\Foo', 'QOO');
        self::assertNotNull($resolved);
        self::assertCount(1, $resolved);
        $type = reset($resolved);
        self::assertInstanceOf(TLiteralString::class, $type);
        self::assertTrue($type->value === 'qoo');
    }
}
