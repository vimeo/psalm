<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Internal\Codebase\Methods;
use Psalm\Tests\TestCase;
use ReflectionProperty;

use function array_filter;
use function array_values;

/**
 * A call of an inherited method references every method it could reach down the inheritance chain
 */
final class PotentialDeclaringMethodIdsTest extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->project_analyzer->getCodebase()->collectLocations();
        $this->project_analyzer->getConfig()->setCustomErrorLevel('UndefinedTrait', 'suppress');
    }

    /**
     * @param list<string> $candidates
     * @param list<string> $expected_referenced
     */
    #[DataProvider('providerCalls')]
    public function testCallReferencesEveryPotentialDeclaringMethod(
        string $code,
        array $candidates,
        array $expected_referenced,
        string $php_version = '7.4',
    ): void {
        $this->project_analyzer->setPhpVersion($php_version, 'tests');
        $this->addFile('somefile.php', $code);
        $this->analyzeFile('somefile.php', new Context());

        $codebase = $this->project_analyzer->getCodebase();

        self::assertSame(
            $expected_referenced,
            array_values(array_filter(
                $candidates,
                static fn(string $method_id): bool => $codebase->findReferencesToMethod($method_id) !== [],
            )),
        );
    }

    #[DataProvider('providerRecordEdges')]
    public function testIdsAreDerivedOnlyWhenEdgesAreRecorded(bool $record_edges): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->code_use_graph->record_edges = $record_edges;

        $this->addFile('somefile.php', '<?php
            class P { public function foo(): void {} }
            final class C extends P {}

            (new C())->foo();');
        $this->analyzeFile('somefile.php', new Context());

        $derived = (new ReflectionProperty(Methods::class, 'potential_declaring_method_ids'))
            ->getValue($codebase->methods);

        self::assertSame(
            $record_edges ? ['p::foo' => [], 'c::foo' => ['c::foo' => true, 'p::foo' => true]] : [],
            $derived,
        );
        self::assertSame($record_edges, $codebase->findReferencesToMethod('P::foo') !== []);
    }

    /**
     * @return iterable<string, array{bool}>
     * @psalm-pure
     */
    public static function providerRecordEdges(): iterable
    {
        yield 'recorded' => [true];
        yield 'not recorded' => [false];
    }

    public function testCallReferencesTheNewParentAfterTheClassChanges(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $files = [
            'p.php' => '<?php class P { public function foo(): void {} }',
            't.php' => '<?php trait T { public function foo(): void {} }',
            'c.php' => '<?php final class C { use T; }',
            'call.php' => '<?php (new C())->foo();',
        ];

        foreach ($files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        self::assertNotSame([], $codebase->findReferencesToMethod('T::foo'));
        self::assertSame([], $codebase->findReferencesToMethod('P::foo'));

        $this->file_provider->registerFile('c.php', '<?php final class C extends P {}');
        $codebase->reloadFiles($this->project_analyzer, ['c.php', 'call.php'], true);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        self::assertNotSame([], $codebase->findReferencesToMethod('P::foo'));
    }

    public function testCallReferencesTheNewParentAfterTheClassIsScannedAgain(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $files = [
            'p.php' => '<?php class P { public function foo(): void {} }',
            't.php' => '<?php trait T { public function foo(): void {} }',
            'c.php' => '<?php final class C { use T; }',
            'call.php' => '<?php (new C())->foo();',
        ];

        foreach ($files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        self::assertNotSame([], $codebase->findReferencesToMethod('T::foo'));
        self::assertSame([], $codebase->findReferencesToMethod('P::foo'));

        $codebase->invalidateInformationForFile('c.php');
        $codebase->invalidateInformationForFile('call.php');
        $this->file_provider->registerFile('c.php', '<?php final class C extends P {}');
        $codebase->addFilesToAnalyze(['c.php' => 'c.php', 'call.php' => 'call.php']);
        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        self::assertNotSame([], $codebase->findReferencesToMethod('P::foo'));
    }

    /**
     * @return iterable<string, array{0: string, 1: list<string>, 2: list<string>, 3?: string}>
     * @psalm-pure
     */
    public static function providerCalls(): iterable
    {
        yield 'insteadofWithTheExcludedTraitFirst' => [
            '<?php
                trait T { public function foo(): void {} }
                trait U { public function foo(): void {} }
                final class C {
                    use U, T { T::foo insteadof U; }
                }

                (new C())->foo();',
            ['C::foo', 'T::foo', 'U::foo'],
            ['C::foo', 'T::foo'],
        ];

        yield 'aliasOfTheExcludedMethod' => [
            '<?php
                trait T { public function foo(): void {} }
                trait U { public function foo(): void {} }
                final class C {
                    use T, U { T::foo insteadof U; U::foo as uFoo; }
                }

                (new C())->uFoo();',
            ['C::foo', 'C::ufoo', 'T::foo', 'U::foo'],
            ['C::foo', 'U::foo'],
        ];

        yield 'traitBeforeParent' => [
            '<?php
                trait T { public function foo(): void {} }
                class P { public function foo(): void {} }
                final class C extends P { use T; }

                (new C())->foo();',
            ['C::foo', 'T::foo', 'P::foo'],
            ['C::foo', 'T::foo'],
        ];

        yield 'interfaceChain' => [
            '<?php
                interface I { public function i(): void; }
                interface J extends I {}
                interface K extends J {}

                function f(K $k): void {
                    $k->i();
                }',
            ['K::i', 'J::i', 'I::i'],
            ['K::i', 'J::i', 'I::i'],
        ];

        yield 'missingTraitIsSkipped' => [
            '<?php
                class P { public function foo(): void {} }
                final class C extends P { use Missing; }

                (new C())->foo();',
            ['C::foo', 'P::foo'],
            ['C::foo', 'P::foo'],
        ];

        yield 'overriddenInTheMiddleOfTheChain' => [
            '<?php
                class P { public function foo(): void {} }
                class C extends P {}
                class D extends C { public function foo(): void {} }
                final class E extends D {}

                (new E())->foo();',
            ['E::foo', 'D::foo', 'C::foo', 'P::foo'],
            ['E::foo', 'D::foo', 'P::foo'],
        ];

        yield 'aliasedTrait' => [
            '<?php
                trait T { public function foo(): void {} }
                class_alias(T::class, "TAlias");
                final class C { use TAlias; }

                (new C())->foo();',
            ['C::foo', 'T::foo', 'TAlias::foo'],
            ['C::foo', 'T::foo'],
        ];

        yield 'reflectedParentClassMethod' => [
            '<?php
                final class MyException extends RuntimeException {}

                (new MyException())->getMessage();',
            ['MyException::getmessage', 'RuntimeException::getmessage', 'Exception::getmessage'],
            ['MyException::getmessage', 'RuntimeException::getmessage'],
        ];
    }
}
