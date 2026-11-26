<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Countable;
use DateTime;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisCasesTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisCasesTrait;

use function array_column;
use function sort;

use const DIRECTORY_SEPARATOR;
use const PHP_VERSION_ID;

final class PropertyTypeTest extends TestCase
{
    use InvalidCodeAnalysisCasesTrait;
    use ValidCodeAnalysisCasesTrait;

    /**
     * The cases are run by PropertyTypeCases1Test to PropertyTypeCases<SHARDS>Test (see ProviderShard).
     */
    public const SHARDS = 2;

    public function testForgetPropertyAssignments(): void
    {
        $this->expectExceptionMessage('NullableReturnStatement');
        $this->expectException(CodeException::class);
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    /** @var X[] */
                    private static array $xs = [];

                    public static function modify() : void {
                        foreach (self::$xs as $x) {
                            $x->x = null;
                        }
                    }
                }

                class X {
                    public ?int $x = null;

                    public function getX(): int {
                        $this->x = 5;

                        XCollector::modify();

                        return $this->x;
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetPropertyAssignmentsPassesNormally(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    /** @var X[] */
                    private static array $xs = [];

                    public static function modify() : void {
                        foreach (self::$xs as $x) {
                            $x->x = null;
                        }
                    }
                }

                class X {
                    public ?int $x = null;

                    public function getX(): int {
                        $this->x = 5;

                        XCollector::modify();

                        return $this->x;
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetPropertyAssignmentsInBranch(): void
    {
        $this->markTestSkipped('Never actually worked: https://github.com/vimeo/psalm/issues/13923');
        /*Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    /** @var X[] * /
                    private static array $xs = [];

                    public static function modify() : void {
                        foreach (self::$xs as $x) {
                            $x->x = null;
                        }
                    }
                }

                class X {
                    public ?int $x = null;
                }

                function testX(X $x, int $v): void {
                    $x->x = 5;

                    if ($v) {
                        XCollector::modify();
                    }

                    if ($x->x === null) {}
                }',
        );

        $this->analyzeFile('somefile.php', new Context());*/
    }

    public function testFooBar(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                    class A {
                        private ?int $bar = null;

                        public function baz(): void
                        {
                            $this->bar = null;

                            foreach (range(1, 5) as $part) {
                                if ($part === 3) {
                                    $this->foo();
                                }
                            }

                            if ($this->bar === null) {}
                        }

                        private function foo() : void {
                            $this->bar = 5;
                        }
                    }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetFinalMethodCalls(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    /** @var X[] */
                    private static array $xs = [];

                    public static function modify() : void {
                        foreach (self::$xs as $x) {
                            $x->x = null;
                        }
                    }
                }

                class X {
                    public ?int $x = null;

                    public function __construct(?int $x) {
                        $this->x = $x;
                    }

                    public final function getX() : ?int {
                        return $this->x;
                    }
                }

                function testX(X $x): void {
                    if (is_int($x->getX())) {
                        XCollector::modify();
                        if ($x->getX() === null) {}
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testRememberImmutableMethodCalls(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->expectExceptionMessage('TypeDoesNotContainNull - somefile.php:22:29');
        $this->expectException(CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    public static function modify() : void {}
                }

                /** @psalm-immutable */
                class X {
                    public ?int $x = null;

                    public function __construct(?int $x) {
                        $this->x = $x;
                    }

                    public function getX() : ?int {
                        return $this->x;
                    }
                }

                function testX(X $x): void {
                    if ($x->getX() !== null) {
                        XCollector::modify();
                        if ($x->getX() === null) {}
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testRememberImmutableProperties(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->expectExceptionMessage('TypeDoesNotContainNull - somefile.php:18:29');
        $this->expectException(CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    public static function modify() : void {}
                }

                /** @psalm-immutable */
                class X {
                    public ?int $x = null;

                    public function __construct(?int $x) {
                        $this->x = $x;
                    }
                }

                function testX(X $x): void {
                    if ($x->x !== null) {
                        XCollector::modify();
                        if ($x->x === null) {}
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testReadonlyPropertySeenAsUninitializedIsForgottenAfterCall(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;
        Config::getInstance()->setCustomErrorLevel('RedundantPropertyInitializationCheck', Config::REPORT_SUPPRESS);
        Config::getInstance()->setCustomErrorLevel('InaccessibleProperty', Config::REPORT_SUPPRESS);
        Config::getInstance()->setCustomErrorLevel('MissingConstructor', Config::REPORT_SUPPRESS);
        Config::getInstance()->setCustomErrorLevel('RiskyTruthyFalsyComparison', Config::REPORT_SUPPRESS);
        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                final class L {
                    public readonly int $x;
                    public readonly ?string $s;

                    public function get(): int {
                        if (!isset($this->x)) {
                            $this->init();
                            return $this->x;
                        }
                        return $this->x;
                    }

                    public function str(): string {
                        if (!isset($this->s)) {
                            $this->init();
                            $s = $this->s;
                            return $s ?? "";
                        }
                        return $this->s;
                    }

                    public function viaVariable(L $l): int {
                        if (!isset($l->x)) {
                            $l->get();
                            return $l->x;
                        }
                        return $l->x;
                    }

                    public function emptyCheck(): string {
                        if (empty($this->s)) {
                            $this->init();
                            if ($this->s === "a") {
                                return "initialized";
                            }
                        }
                        return "";
                    }

                    public function notEmptyElse(): string {
                        if (!empty($this->s)) {
                            return $this->s;
                        }
                        $this->init();
                        if ($this->s === "a") {
                            return "initialized";
                        }
                        return "";
                    }

                    private function init(): void {
                        $this->x = 5;
                        $this->s = "a";
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testRememberReadonlyPropertiesAfterCall(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;
        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                final class Box {
                    public int $v = 0;
                }

                function poke(Box $b): void {
                    $b->v = 1;
                }

                final class Num {
                    public function __construct(public readonly ?int $n) {}
                }

                final class Holder {
                    public function __construct(public readonly ?int $own, public readonly Num $num) {}

                    public function own(Box $b): int {
                        if ($this->own === null) {
                            return 0;
                        }
                        poke($b);
                        return $this->own;
                    }

                    public function nested(Box $b): int {
                        if ($this->num->n === null) {
                            return 0;
                        }
                        poke($b);
                        return $this->num->n;
                    }
                }

                function param(Num $num, Box $b): int {
                    if ($num->n === null) {
                        return 0;
                    }
                    poke($b);
                    return $num->n;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetReadonlyPropertyOfAWritablePropertyAfterCall(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;
        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $this->expectExceptionMessage('NullableReturnStatement - somefile.php:22:32');
        $this->expectException(CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                final class Box {
                    public int $v = 0;
                }

                function poke(Box $b): void {
                    $b->v = 1;
                }

                final class Num {
                    public function __construct(public readonly ?int $n) {}
                }

                final class Holder {
                    public function __construct(public Num $num) {}

                    public function nested(Box $b): int {
                        if ($this->num->n === null) {
                            return 0;
                        }
                        poke($b);
                        return $this->num->n;
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetReadonlyPropertyOfAGlobalACallMayReplace(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;
        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $this->expectExceptionMessage('NullableReturnStatement - somefile.php:18:32');
        $this->expectException(CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                final class Num {
                    public function __construct(public readonly ?int $n) {}
                }

                /** @var Num $num */
                $num = new Num(1);

                function replace(): void {
                    global $num;
                    $num = new Num(null);
                }

                function read(): int {
                    global $num;
                    if ($num->n !== null) {
                        replace();
                        return $num->n;
                    }
                    return 0;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetReadonlyPropertyWithPrivateMutationAfterCall(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->expectExceptionMessage('NullableReturnStatement - somefile.php:21:32');
        $this->expectException(CodeException::class);

        $this->addFile(
            'somefile.php',
            '<?php
                final class Box {
                    public int $v = 0;
                }

                function poke(Box $b): void {
                    $b->v = 1;
                }

                final class Num {
                    /**
                     * @psalm-readonly-allow-private-mutation
                     */
                    public ?int $n = null;

                    public function get(Box $b): int {
                        if ($this->n === null) {
                            return 0;
                        }
                        poke($b);
                        return $this->n;
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoCrashInTryCatch(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                function maybeMutates() : void {}

                class X {
                    public int $f = 0;

                    public function validate(): void {
                        try {
                        } finally {
                            $this->f = 1;
                            maybeMutates();
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertionInsideWhileOne(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    public array $a = [];
                    public array $b = [];
                    public array $c = [];

                    public function one(): bool {
                        $has_changes = false;

                        while ($this->a) {
                            $has_changes = true;
                            $this->alter();
                        }

                        return $has_changes;
                    }

                    public function alter() : void {
                        if (rand(0, 1)) {
                            array_pop($this->a);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->b);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->c);
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertionInsideWhileTwo(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    public array $a = [];
                    public array $b = [];

                    public function two(): bool {
                        $has_changes = false;

                        while ($this->a || $this->b) {
                            $has_changes = true;
                            $this->alter();
                        }

                        return $has_changes;
                    }

                    public function alter() : void {
                        if (rand(0, 1)) {
                            array_pop($this->a);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->b);
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertionInsideWhileThree(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    public array $a = [];
                    public array $b = [];
                    public array $c = [];

                    public function three(): bool {
                        $has_changes = false;

                        while ($this->a || $this->b || $this->c) {
                            $has_changes = true;
                            $this->alter();
                        }

                        return $has_changes;
                    }

                    public function alter() : void {
                        if (rand(0, 1)) {
                            array_pop($this->a);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->b);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->c);
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertionInsideWhileFour(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    public array $a = [];
                    public array $b = [];
                    public array $c = [];

                    public function four(): bool {
                        $has_changes = false;

                        while (($this->a && $this->b) || $this->c) {
                            $has_changes = true;
                            $this->alter();
                        }

                        return $has_changes;
                    }

                    public function alter() : void {
                        if (rand(0, 1)) {
                            array_pop($this->a);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->b);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->c);
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAssertionInsideWhileFive(): void
    {
        Config::getInstance()->remember_property_assignments_after_call = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {
                    public array $a = [];
                    public array $b = [];
                    public array $c = [];

                    public function five(): bool {
                        $has_changes = false;

                        while ($this->a || ($this->b && $this->c)) {
                            $has_changes = true;
                            $this->alter();
                        }

                        return $has_changes;
                    }

                    public function alter() : void {
                        if (rand(0, 1)) {
                            array_pop($this->a);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->b);
                        }

                        if (rand(0, 1)) {
                            array_pop($this->c);
                        }
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testUniversalObjectCrates(): void
    {
        Config::getInstance()->addUniversalObjectCrate(DateTime::class);

        $this->addFile(
            'somefile.php',
            '<?php
                $f = new \DateTime();
                // reads are fine
                $f->bar;

                // sets are fine
                $f->buzz = false;
        ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testUniversalObjectCratesOfAnInterface(): void
    {
        Config::getInstance()->addUniversalObjectCrate(Countable::class);

        $this->addFile(
            'somefile.php',
            '<?php
                interface Sized extends \\Countable {}

                class A implements \\Countable {
                    public function count(): int {
                        return 0;
                    }
                }

                class B implements Sized {
                    public function count(): int {
                        return 0;
                    }
                }

                function reads(\\Countable $c, A $a, B $b, Sized $s): void {
                    $c->foo;
                    $a->foo;
                    $b->foo;
                    $s->foo;
                }

                function sets(\\Countable $c, A $a, B $b, Sized $s): void {
                    $c->foo = 1;
                    $a->foo = 2;
                    $b->foo = 3;
                    $s->foo = 4;
                }
        ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testUniversalObjectCratesOfAParentClass(): void
    {
        Config::getInstance()->addUniversalObjectCrate(DateTime::class);

        $this->addFile(
            'somefile.php',
            '<?php
                class MyDate extends \\DateTime {}
                class MyOtherDate extends MyDate {}

                function reads(MyDate $a, MyOtherDate $b): void {
                    $a->foo;
                    $b->foo;
                }

                function sets(MyDate $a, MyOtherDate $b): void {
                    $a->foo = 1;
                    $b->foo = 2;
                }
        ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testUniversalObjectCratesDoNotMatchOtherClassesOnFetch(): void
    {
        Config::getInstance()->addUniversalObjectCrate(Countable::class);

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UndefinedPropertyFetch');

        $this->addFile(
            'somefile.php',
            '<?php
                class Plain {}

                function read(Plain $p): void {
                    $p->foo;
                }
        ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testUniversalObjectCratesDoNotMatchOtherClassesOnAssignment(): void
    {
        Config::getInstance()->addUniversalObjectCrate(Countable::class);

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UndefinedPropertyAssignment');

        $this->addFile(
            'somefile.php',
            '<?php
                class Plain {}

                function set(Plain $p): void {
                    $p->foo = 1;
                }
        ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testForgetPropertyAssignmentsInBranchWithThrowNormally(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class XCollector {
                    /** @var X[] */
                    private static array $xs = [];

                    public static function modify() : void {
                        foreach (self::$xs as $x) {
                            $x->x = null;
                        }
                    }
                }

                class X {
                    /** @var ?int **/
                    public $x;

                    public function getX(bool $b): int {
                        $this->x = 5;

                        if ($b) {
                            XCollector::modify();
                            throw new \Exception("bad");
                        }

                        return $this->x;
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * A parameter type naming a missing class makes the analysis of a constructor skip its body, but the properties
     * the body sets are still initialised in the subclasses.
     */
    #[DataProvider('providerSubclassesOfAConstructorWithAMissingParamClass')]
    public function testConstructorWithAMissingParamClassInitialisesSubclassProperties(string $child_code): void
    {
        $this->testConfig->throw_exception = false;

        $parent_file = self::$src_dir_path . 'parent.php';
        $child_file = self::$src_dir_path . 'child.php';

        $this->addFile($parent_file, '<?php
            namespace Fixture;

            class ParentClass {
                protected string $name;

                /** @param MissingClass $finder */
                public function __construct($finder = null) {
                    $this->name = "name";
                }
            }
        ');
        $this->addFile($child_file, $child_code);

        $this->analyzeFile($child_file, new Context());

        $issues = [];

        foreach ([$parent_file, $child_file] as $file) {
            foreach (IssueBuffer::getIssuesDataForFile($file) as $issue) {
                $issues[] = $issue->type . ' ' . $issue->file_name . ':' . $issue->line_from;
            }
        }

        self::assertSame(['UndefinedDocblockClass src' . DIRECTORY_SEPARATOR . 'parent.php:7'], $issues);
    }

    public function testCodeAfterAnInvalidStaticPropertyAssignmentIsAnalyzed(): void
    {
        $this->testConfig->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, '<?php
            final class S {
                public static int $x = 0;
                /** @var list<int> */
                public static array $l = [];
            }

            function possiblyInvalid(int|float $v): void {
                S::$x = $v;
                undefinedFunction1();
            }

            function invalid(float $v): void {
                S::$x = $v;
                undefinedFunction2();
            }

            function invalidOffset(): void {
                S::$l[] = "x";
                undefinedFunction3();
            }
        ');

        $this->project_analyzer->setPhpVersion('8.0', 'tests');
        $this->analyzeFile($file_path, new Context());

        $issues = [];

        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            $issues[] = $issue->type . ':' . $issue->line_from;
        }

        self::assertSame(
            [
                'PossiblyInvalidPropertyAssignmentValue:9',
                'UndefinedFunction:10',
                'InvalidPropertyAssignmentValue:14',
                'UndefinedFunction:15',
                'InvalidPropertyAssignmentValue:19',
                'UndefinedFunction:20',
            ],
            $issues,
        );
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function providerSubclassesOfAConstructorWithAMissingParamClass(): iterable
    {
        yield 'inheriting the constructor' => ['<?php
            namespace Fixture;

            class ChildClass extends ParentClass {}
        '];
        yield 'calling the constructor' => ['<?php
            namespace Fixture;

            class ChildClass extends ParentClass {
                public function __construct() {
                    parent::__construct();
                }
            }
        '];
    }

    public function testPropertyOfABuiltinClassKnownOnlyThroughReflectionHasItsNativeType(): void
    {
        if (PHP_VERSION_ID < 8_04_00) {
            $this->markTestSkipped('Dom\DocumentType is defined from PHP 8.4.');
        }

        $this->project_analyzer->setPhpVersion('8.4', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                function doctype(): Dom\DocumentType {
                    throw new RuntimeException();
                }

                $entities = doctype()->entities;
                $entityCount = $entities->length;',
        );

        $context = new Context();
        $this->analyzeFile('somefile.php', $context);

        self::assertSame('Dom\DtdNamedNodeMap', $context->vars_in_scope['$entities']->getId(true));
        self::assertSame('int', $context->vars_in_scope['$entityCount']->getId(true));
    }

    public function testPossiblyNullAndPossiblyFalseAssignmentsToAPropertyAreBothReported(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                final class A {
                    public int $i = 0;

                    /** @param int|null|false $i */
                    public function set($i): void {
                        $this->i = $i;
                    }
                }',
        );

        $this->analyzeFile($file_path, new Context());

        $types = array_column(IssueBuffer::getIssuesDataForFile($file_path), 'type');
        sort($types);

        self::assertSame(['PossiblyFalsePropertyAssignmentValue', 'PossiblyNullPropertyAssignmentValue'], $types);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'staticInThePropertyTypeOfAParentIsTheClassOfTheObjectRead' => [
                'code' => '<?php
                    class Node {
                        /** @var ?static */
                        public ?self $next = null;
                        /** @var ?self */
                        public ?self $prev = null;
                    }
                    final class Leaf extends Node {}
                    $leaf = new Leaf();
                    $next = $leaf->next;
                    $prev = $leaf->prev;
                    $vars = get_object_vars($leaf);
                    $column = array_column([$leaf], "next");',
                'assertions' => [
                    '$next===' => 'Leaf|null',
                    '$prev===' => 'Node|null',
                    '$vars===' => 'array{next: Leaf|null, prev: Node|null}',
                    '$column===' => 'list{Leaf|null}',
                ],
            ],
            'assignedUnionHasItsWidenedGenericReplaced' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    final class Box {
                        /**
                         * @param list<T> $v
                         */
                        public function __construct(public array $v) {}
                    }

                    final class H {
                        /** @var Box<int>|null */
                        public ?Box $b = null;
                    }

                    /** @return Box<never>|null */
                    function emptyBox(): ?Box {
                        return null;
                    }

                    $h = new H();
                    $h->b = emptyBox();
                    $b = $h->b;',
                'assertions' => [
                    '$b===' => 'Box<int>|null',
                ],
            ],
            'offsetWriteInitializesAnUninitializedArrayProperty' => [
                'code' => '<?php
                    final class Bag
                    {
                        /** @var array<string, string> */
                        private array $values;

                        public function __construct(string $name)
                        {
                            $this->values["name"] = $name;
                        }

                        /** @return array<string, string> */
                        public function all(): array
                        {
                            return $this->values;
                        }
                    }',
            ],
            'appendAndNestedOffsetWriteInitializeAnUninitializedArrayProperty' => [
                'code' => '<?php
                    final class Bag
                    {
                        /** @var list<string> */
                        private array $items;

                        /** @var array<string, array<string, string>> */
                        private array $nested;

                        public function __construct(string $x)
                        {
                            $this->items[] = $x;
                            $this->nested["a"]["b"] = $x;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'finalClassInitialisesThePropertyThroughAnAliasedTraitMethod' => [
                'code' => '<?php
                    interface H {}

                    trait T {
                        private H $h;

                        public function setH(H $h): void {
                            $this->h = $h;
                        }

                        public function getH(): H {
                            return $this->h;
                        }
                    }

                    final class C {
                        use T { setH as public init; }

                        public function __construct(H $h) {
                            $this->init($h);
                        }
                    }',
            ],
            'finalClassInitialisesThePropertyThroughAMethodOfANestedTrait' => [
                'code' => '<?php
                    interface H {}

                    trait T {
                        private H $h;

                        public function setH(H $h): void {
                            $this->h = $h;
                        }

                        public function getH(): H {
                            return $this->h;
                        }
                    }

                    trait T2 {
                        use T;
                    }

                    final class C {
                        use T2;

                        public function __construct(H $h) {
                            $this->setH($h);
                        }
                    }',
            ],
            'finalClassInitialisesThePropertyThroughATraitMethodItCalls' => [
                'code' => '<?php
                    interface ViewHandler {}

                    trait ControllerTrait {
                        private ViewHandler $handler;

                        public function setHandler(ViewHandler $handler): void {
                            $this->handler = $handler;
                        }

                        public function getHandler(): ViewHandler {
                            return $this->handler;
                        }
                    }

                    final class Controller {
                        use ControllerTrait;

                        public function __construct(ViewHandler $handler) {
                            $this->setHandler($handler);
                        }
                    }',
            ],
            'propertyInitialisedByParentConstructorInEveryNonThrowingBranch' => [
                'code' => '<?php
                    class X {
                        public string $y;

                        public function __construct(string $y)
                        {
                            $this->y = $y;
                        }
                    }

                    final class Y extends X {
                        public function __construct(string $y)
                        {
                            if ($y === \'a\' || $y === \'b\' || $y === \'c\') {
                                parent::__construct($y);
                            } else {
                                throw new InvalidArgumentException();
                            }
                        }
                    }

                    final class Z extends X {
                        public function __construct(string $y)
                        {
                            switch ($y) {
                                case \'a\':
                                case \'b\':
                                case \'c\':
                                    parent::__construct($y);
                                    return;
                                default:
                                    throw new InvalidArgumentException();
                            }
                        }
                    }

                    final class ZZ extends X {
                        public function __construct(string $y)
                        {
                            match ($y) {
                                \'a\', \'b\', \'c\' => parent::__construct($y),
                                default => throw new InvalidArgumentException()
                            };
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertySetInConstructorStaysSetAfterAnIfNarrowingThis' => [
                'code' => '<?php
                    interface Configurable {
                        public function setConfiguration(array $c): void;
                    }

                    interface Aggregate {}

                    abstract class PluginBase {
                        protected array $configuration;

                        public function __construct(array $configuration) {
                            $this->configuration = $configuration;
                        }
                    }

                    class PluginDefault extends PluginBase {
                        public function __construct(array $configuration) {
                            parent::__construct($configuration);
                            if ($this instanceof Configurable) {
                                $this->setConfiguration($configuration);
                            }
                        }
                    }

                    class QueryBase {
                        protected string $condition;
                        protected ?string $aggregate = null;

                        public function __construct(string $c) {
                            $this->condition = $c;
                            if ($this instanceof Aggregate) {
                                $this->aggregate = $c;
                            }
                        }
                    }',
            ],
            'propertyOfThisIsNarrowedAgainWithThisAfterAnIfOnlyNarrowingThis' => [
                'code' => '<?php
                    final class A {
                        public array $uses = [];
                    }

                    final class B {
                        public array $params = [];
                    }

                    final class C {
                        public string $name = "";
                    }

                    /** @template-covariant T as A|B|C */
                    abstract class Analyzer {
                        /** @param T $function */
                        public function __construct(protected A|B|C $function) {}

                        /** @param list<int> $ids */
                        public function run(array $ids): void {
                            if ($this instanceof ClosureAnalyzer && $this->function instanceof A) {
                                foreach ($this->function->uses as $_) {
                                    $this->touch();
                                }
                            } elseif ($this instanceof ClosureAnalyzer && $this->function instanceof B) {
                                foreach ($this->function->params as $_) {
                                    echo 1;
                                }
                            }

                            foreach ($ids as $id) {
                                if ($this instanceof MethodAnalyzer && $id === 1) {
                                    echo $this->function->name;
                                }
                            }
                        }

                        public function touch(): void {}
                    }

                    /** @extends Analyzer<A|B> */
                    final class ClosureAnalyzer extends Analyzer {}

                    /** @extends Analyzer<C> */
                    final class MethodAnalyzer extends Analyzer {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'declaredPropertyOfAStdClassSubclassKeepsItsTypeAndAnUndeclaredOneStaysDynamic' => [
                'code' => '<?php
                    final class Item extends stdClass {
                        /** @var list<string> */
                        public array $tags = [];

                        public int $count = 0;

                        public ?string $note = null;
                    }

                    function write(Item $item): void {
                        $item->tags = ["a"];
                        $item->count = 2;
                        $item->extra = "x";
                    }

                    function read(Item $item): void {
                        $tags = $item->tags;
                        $count = $item->count;
                        $note = $item->note;
                        $extra = $item->extra;

                        /** @psalm-check-type-exact $tags = list<string> */
                        /** @psalm-check-type-exact $count = int */
                        /** @psalm-check-type-exact $note = null|string */
                        /** @psalm-check-type-exact $extra = mixed */
                        echo count($tags), $count, $note;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.1',
            ],
            'staticOfTheCallerIsNotTheStaticPropertyOfAnotherClassOfTheSameName' => [
                'code' => '<?php
                    final class Other {
                        public static ?int $p = null;

                        public static function clear(): void {
                            self::$p = null;
                        }
                    }

                    final class A {
                        public static ?int $p = null;

                        public static function get(): int {
                            static::$p = 1;
                            Other::clear();

                            return static::$p;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'parentStaticPropertyRedeclaredByTheParentIsNotTheOneOfTheGrandparent' => [
                'code' => '<?php
                    class G {
                        public static ?int $p = null;
                    }

                    class P extends G {
                        public static ?int $p = null;
                    }

                    final class A extends P {
                        public static function clear(): void {
                            parent::$p = null;
                        }
                    }

                    function get(): int {
                        G::$p = 1;
                        A::clear();

                        return G::$p;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticPropertyOfATraitIsOnePropertyPerUsingClass' => [
                'code' => '<?php
                    trait Holder {
                        public static ?int $v = null;
                    }

                    final class A {
                        use Holder;

                        public static function clear(): void {
                            self::$v = null;
                        }
                    }

                    final class B {
                        use Holder;

                        public static function get(): int {
                            self::$v = 1;
                            A::clear();

                            return self::$v;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticPropertyAssignedThroughStaticInAParentCallIsForgottenInTheCallingClass' => [
                'code' => '<?php
                    class Base {
                        public static bool $flag = false;

                        public static function prepare(): void {
                            static::$flag = true;
                        }
                    }

                    final class Child extends Base {
                        public static bool $flag = false;

                        public static function run(): bool {
                            Child::$flag = false;
                            parent::prepare();

                            if (Child::$flag) {
                                return true;
                            }

                            return false;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticPropertyAssignedByAStaticMethodIsForgottenAfterTheCall' => [
                'code' => '<?php
                    final class Counter {
                        public static bool $ready = false;

                        public static function prepare(): void {
                            self::$ready = true;
                        }

                        public static function run(): void {
                            self::$ready = false;
                            self::prepare();

                            if (self::$ready) {
                                echo "ready";
                            }
                        }
                    }',
            ],
            'staticPropertyAssignedByAnInstanceMethodIsForgottenInALoop' => [
                'code' => '<?php
                    final class Widget {
                        private static bool $isSomething = false;

                        /** @param list<string> $values */
                        public function run(array $values): void {
                            self::$isSomething = false;

                            foreach ($values as $value) {
                                if (self::$isSomething) {
                                    echo $value;
                                    continue;
                                }

                                $this->helper();
                            }
                        }

                        private function helper(): void {
                            static::$isSomething = true;
                        }
                    }',
            ],
            'staticPropertyAssignedThroughParentIsForgottenInTheNamedClass' => [
                'code' => '<?php
                    class Base {
                        public static bool $ready = false;
                    }

                    final class Child extends Base {
                        public static function prepare(): void {
                            parent::$ready = true;
                        }

                        public static function run(): void {
                            Base::$ready = false;
                            self::prepare();

                            if (Base::$ready) {
                                echo "ready";
                            }
                        }
                    }',
            ],
            'staticPropertyAssignedThroughTheNamedClassIsForgottenAsSelf' => [
                'code' => '<?php
                    class Base {
                        public static bool $ready = false;
                    }

                    final class Child extends Base {
                        public static function prepare(): void {
                            Base::$ready = true;
                        }

                        public static function run(): void {
                            self::$ready = false;
                            self::prepare();

                            if (self::$ready) {
                                echo "ready";
                            }
                        }
                    }',
            ],
            'staticPropertyAssignedThroughAChildClassIsForgottenInTheParentClass' => [
                'code' => '<?php
                    class Base {
                        public static bool $ready = false;
                    }

                    final class Child extends Base {
                        public static function prepare(): void {
                            Child::$ready = true;
                        }

                        public static function run(): void {
                            Base::$ready = false;
                            self::prepare();

                            if (Base::$ready) {
                                echo "ready";
                            }
                        }
                    }',
            ],
            'staticPropertyRedeclaredByAChildClassIsAnotherPropertyThanTheOneAssigned' => [
                'code' => '<?php
                    class Base {
                        public static bool $flag = false;
                    }

                    final class Child extends Base {
                        public static bool $flag = false;

                        public static function prepare(): void {
                            Base::$flag = true;
                        }

                        public static function run(): void {
                            Child::$flag = false;
                            self::prepare();

                            /** @psalm-check-type-exact $flag = false */
                            $flag = Child::$flag;
                        }
                    }',
            ],
            'firstClassCallableOfAMethodThatAssignsAStaticPropertyKeepsItsType' => [
                'code' => '<?php
                    final class Counter {
                        public static bool $ready = false;

                        public static function prepare(): void {
                            self::$ready = true;
                        }

                        public static function run(): void {
                            self::$ready = false;
                            $callback = self::prepare(...);

                            /** @psalm-check-type-exact $ready = false */
                            $ready = self::$ready;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'selfInAnInheritedMethodIsTheClassThatDeclaresItNotTheRedeclaringChild' => [
                'code' => '<?php
                    class Base {
                        public static bool $flag = false;

                        public static function prepare(): void {
                            self::$flag = true;
                        }
                    }

                    final class Child extends Base {
                        public static bool $flag = false;

                        public static function runChild(): void {
                            Child::$flag = false;
                            Child::prepare();

                            /** @psalm-check-type-exact $child = false */
                            $child = Child::$flag;
                        }

                        public static function runBase(): void {
                            Base::$flag = false;
                            Child::prepare();

                            /** @psalm-check-type-exact $base = bool */
                            $base = Base::$flag;
                        }
                    }',
            ],
            'staticInAnInheritedMethodIsTheClassItIsCalledOn' => [
                'code' => '<?php
                    class Base {
                        public static bool $flag = false;

                        public static function prepare(): void {
                            static::$flag = true;
                        }
                    }

                    final class Child extends Base {
                        public static bool $flag = false;

                        public static function run(): void {
                            Child::$flag = false;
                            Base::$flag = false;
                            Child::prepare();

                            /** @psalm-check-type-exact $child = bool */
                            $child = Child::$flag;
                            /** @psalm-check-type-exact $base = false */
                            $base = Base::$flag;
                        }
                    }',
            ],
            'selfInAMethodOfATraitIsTheClassUsingTheTraitNotTheClassItIsCalledOn' => [
                'code' => '<?php
                    trait Prepares {
                        public static function prepare(): void {
                            self::$flag = true;
                        }
                    }

                    class Base {
                        use Prepares;

                        public static bool $flag = false;
                    }

                    final class Child extends Base {
                        public static bool $flag = false;

                        public static function runChild(): void {
                            Child::$flag = false;
                            Child::prepare();

                            /** @psalm-check-type-exact $child = false */
                            $child = Child::$flag;
                        }

                        public static function runBase(): void {
                            Base::$flag = false;
                            Child::prepare();

                            /** @psalm-check-type-exact $base = bool */
                            $base = Base::$flag;
                        }
                    }',
            ],
            'staticPropertyIncrementedByAMethodIsForgottenAfterTheCall' => [
                'code' => '<?php
                    final class Holder {
                        public static int $n = 0;

                        public static function change(): void {
                            self::$n++;
                        }

                        public static function run(): void {
                            self::$n = 0;
                            self::change();

                            /** @psalm-check-type-exact $n = int */
                            $n = self::$n;
                        }
                    }',
            ],
            'staticPropertyPreDecrementedByAMethodIsForgottenAfterTheCall' => [
                'code' => '<?php
                    final class Holder {
                        public static int $n = 0;

                        public static function change(): void {
                            --self::$n;
                        }

                        public static function run(): void {
                            self::$n = 0;
                            self::change();

                            /** @psalm-check-type-exact $n = int */
                            $n = self::$n;
                        }
                    }',
            ],
            'staticPropertyInAListAssignedByAMethodIsForgottenAfterTheCall' => [
                'code' => '<?php
                    final class Holder {
                        public static int $n = 0;

                        public static function change(): void {
                            [self::$n] = [1];
                        }

                        public static function run(): void {
                            self::$n = 0;
                            self::change();

                            /** @psalm-check-type-exact $n = int */
                            $n = self::$n;
                        }
                    }',
            ],
            'staticPropertyAForeachTargetOfAMethodIsForgottenAfterTheCall' => [
                'code' => '<?php
                    final class Holder {
                        public static int $n = 0;

                        public static function change(): void {
                            foreach ([1] as self::$n) {}
                        }

                        public static function run(): void {
                            self::$n = 0;
                            self::change();

                            /** @psalm-check-type-exact $n = int */
                            $n = self::$n;
                        }
                    }',
            ],
            'staticPropertyAssignedByAClosureOfAMethodIsAKnownLimit' => [
                'code' => '<?php
                    final class Holder {
                        public static int $n = 0;

                        public static function change(): void {
                            $set = function (): void {
                                self::$n = 1;
                            };
                            $set();
                        }

                        public static function run(): void {
                            self::$n = 0;
                            self::change();

                            /** @psalm-check-type-exact $n = 0 */
                            $n = self::$n;
                        }
                    }',
            ],
            'staticPropertyAssignedByAConstructorIsAKnownLimit' => [
                'code' => '<?php
                    final class Holder {
                        public static int $n = 0;

                        public static function run(): void {
                            self::$n = 0;
                            new Setter();

                            /** @psalm-check-type-exact $n = 0 */
                            $n = self::$n;
                        }
                    }

                    final class Setter {
                        public function __construct() {
                            Holder::$n = 1;
                        }
                    }',
            ],
            'staticPropertyAssignedByAFirstClassCallableIsAKnownLimit' => [
                'code' => '<?php
                    final class Holder {
                        public static int $n = 0;

                        public static function change(): void {
                            self::$n = 1;
                        }

                        public static function run(): void {
                            self::$n = 0;
                            $change = self::change(...);
                            $change();

                            /** @psalm-check-type-exact $n = 0 */
                            $n = self::$n;
                        }
                    }',
            ],
            'staticArrayPropertyChangedByAMethodIsForgottenAfterTheCall' => [
                'code' => '<?php
                    final class Queue {
                        /** @var list<int> */
                        private static array $items = [];

                        public static function add(int $item): void {
                            self::$items[] = $item;
                        }

                        public static function hasItems(): bool {
                            self::$items = [];
                            self::add(1);

                            if (self::$items) {
                                return true;
                            }

                            return false;
                        }
                    }',
            ],
            'staticPropertyAssignedByNamedClassIsForgottenAfterTheCall' => [
                'code' => '<?php
                    final class Flag {
                        public static bool $raised = false;

                        public function raise(): void {
                            Flag::$raised = true;
                        }
                    }

                    final class Runner {
                        public static function run(): bool {
                            Flag::$raised = false;
                            (new Flag())->raise();

                            if (Flag::$raised) {
                                return true;
                            }

                            return false;
                        }
                    }',
            ],
            'propertyReadOnlyWhereIssetHoldsInATraitMayBeLeftUninitialisedByAReturnOfConstructor' => [
                'code' => '<?php
                    final class Canary {
                        public function cancel(): void {}
                    }

                    trait CancelsTheCanary {
                        public function cancel(): void {
                            if (isset($this->canary)) {
                                $this->canary->cancel();
                            }
                        }
                    }

                    final class Response {
                        use CancelsTheCanary;

                        private Canary $canary;

                        public function __construct(bool $pushed) {
                            if ($pushed) {
                                return;
                            }

                            $this->canary = new Canary();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantPropertyInitializationCheck'],
            ],
            'propertyInitialisedByFinallyOfConstructorThatReturns' => [
                'code' => '<?php
                    final class A {
                        private string $value;

                        public function __construct() {
                            try {
                                return;
                            } finally {
                                $this->value = "ok";
                            }
                        }
                    }',
            ],
            'propertyCheckedWithIssetMayBeLeftUninitialisedByAReturnOfConstructor' => [
                'code' => '<?php
                    final class Canary {}

                    final class Response {
                        private Canary $canary;

                        public function __construct(bool $pushed) {
                            if ($pushed) {
                                return;
                            }

                            $this->canary = new Canary();
                        }

                        public function cancel(): void {
                            if (isset($this->canary)) {
                                echo "cancelled";
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantPropertyInitializationCheck'],
            ],
            'propertyReadOnlyWhereIssetHoldsMayBeLeftUninitialisedByAReturnOfConstructor' => [
                'code' => '<?php
                    final class Canary {
                        public function cancel(): void {}
                    }

                    final class Response {
                        private Canary $canary;

                        public function __construct(bool $pushed) {
                            if ($pushed) {
                                return;
                            }

                            $this->canary = new Canary();
                        }

                        public function a(): void {
                            isset($this->canary) && $this->canary->cancel();
                        }

                        public function b(): ?Canary {
                            return isset($this->canary) ? $this->canary : null;
                        }

                        public function c(): ?Canary {
                            return $this->canary ?? null;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantPropertyInitializationCheck', 'RedundantCondition'],
            ],
            'propertyReadOnlyThroughCoalesceMayBeLeftUninitialisedByAReturnOfConstructor' => [
                'code' => '<?php
                    final class Canary {
                        public function cancel(): void {}
                    }

                    final class Response {
                        private Canary $canary;

                        public function __construct(bool $pushed) {
                            if ($pushed) {
                                return;
                            }

                            $this->canary = new Canary();
                        }

                        public function canary(): ?Canary {
                            return $this->canary ?? null;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantPropertyInitializationCheck', 'RedundantCondition'],
            ],
            'propertyInitialisedBeforeEveryReturnOfConstructor' => [
                'code' => '<?php
                    final class A {
                        private string $value;

                        public function __construct(string $v) {
                            if ($v !== "") {
                                $this->value = "foo";
                                return;
                            }

                            throw new InvalidArgumentException("empty");
                        }
                    }

                    final class B {
                        private string $value;

                        public function __construct(string $v) {
                            if ($v === "a") {
                                $this->value = "a";
                                return;
                            }

                            if ($v === "b") {
                                $this->value = "b";
                                return;
                            }

                            $this->value = "c";
                        }
                    }

                    final class C {
                        private string $value;

                        public function __construct(string $v) {
                            $this->value = "x";

                            if ($v === "") {
                                return;
                            }

                            $this->value = $v;
                        }
                    }',
            ],
            'propertyInitialisedByMethodBeforeReturnOfConstructor' => [
                'code' => '<?php
                    final class A {
                        private string $value;

                        public function __construct(string $v) {
                            if ($v !== "") {
                                $this->init($v);
                                return;
                            }

                            throw new InvalidArgumentException("empty");
                        }

                        private function init(string $v): void {
                            $this->value = $v;
                        }
                    }',
            ],
            'privateTraitPropertyInitializedByTheUsingClassIsNotCheckedInSubclasses' => [
                'code' => '<?php
                    trait IdTrait {
                        private int $id;

                        private function initializeId(): void {
                            $this->id = 5;
                        }

                        public function id(): int {
                            return $this->id;
                        }
                    }

                    class BaseUser {
                        use IdTrait;

                        public function __construct() {
                            $this->initializeId();
                        }
                    }

                    class Admin extends BaseUser {}

                    class Client extends BaseUser {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
            ],
            'privateTemplatedPropertyInitializedByAFinalParentConstructorChain' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Box {
                        /**
                         * @return Box<int>
                         */
                        public static function factory(): self {
                            /** @var Box<int> $instance */
                            $instance = new self();

                            return $instance;
                        }
                    }

                    abstract class Base {
                        final public function __construct() {
                            $this->init();
                        }

                        abstract protected function init(): void;
                    }

                    final class Child extends Base {
                        /** @var Box<int> */
                        private $box;

                        protected function init(): void {
                            $this->box = Box::factory();
                        }

                        public function get(): Box {
                            return $this->box;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticCallOfAnInterfaceMethodInAConstructorHasItsReturnType' => [
                'code' => '<?php
                    interface Limits {
                        public static function minimum(): int;
                    }

                    abstract class Value implements Limits {
                        public int $value;

                        public function __construct(int $value) {
                            $minimum = self::minimum();
                            $static_minimum = static::minimum();

                            if ($value < $minimum || $value < $static_minimum) {
                                throw new InvalidArgumentException((string) $minimum);
                            }

                            $this->value = $value;
                        }
                    }',
            ],
            'staticMethodWithABodyCalledInAConstructorKeepsItsReturnType' => [
                'code' => '<?php
                    class Value {
                        public int $value;

                        public function __construct() {
                            $default = self::defaultValue();
                            $this->value = $default;
                        }

                        public static function defaultValue(): int {
                            return 1;
                        }
                    }',
            ],
            'assignPrivatePropertyOfCurrentClassOnChildInstance' => [
                'code' => '<?php
                    class A {
                        private int $value = 0;

                        public function handle(A $v): void {
                            if ($v instanceof B) {
                                $v->value = 123;
                            } else {
                                $v->value = 321;
                            }
                        }
                    }

                    class B extends A {}',
            ],
            'protectedPropertyRedeclaredInASubclassAccessedFromASibling' => [
                'code' => '<?php
                    class A
                    {
                        protected string $prop = "";
                    }

                    class B extends A
                    {
                        protected string $prop = "b";
                    }

                    class C extends A
                    {
                        public B $b;

                        public function __construct()
                        {
                            $this->b = new B();
                        }

                        public function read(): string
                        {
                            return $this->b->prop;
                        }
                    }',
            ],
            'protectedPropertyRedeclaredByATraitAccessedFromASibling' => [
                'code' => '<?php
                    class A
                    {
                        protected string $prop = "";
                    }

                    trait Mangler
                    {
                        protected string $prop = "";
                    }

                    class B extends A
                    {
                        use Mangler;
                    }

                    class C extends A
                    {
                        public B $b;

                        public function __construct()
                        {
                            $this->b = new B();
                        }

                        public function write(): void
                        {
                            $this->b->prop = "x";
                        }
                    }',
            ],
            'newVarInIf' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var mixed
                         */
                        public $foo = "hello";

                        /** @return void */
                        public function barBar()
                        {
                            if (rand(0,10) === 5) {
                                $this->foo = [];
                            }

                            if (!is_array($this->foo)) {
                                // do something
                            }
                        }
                    }',
            ],
            'propertyWithoutTypeSuppressingIssue' => [
                'code' => '<?php
                    class A {
                        public $foo = "hello";
                    }

                    $a = (new A)->foo;',
                'assertions' => [],
                'ignored_issues' => [
                    'MissingPropertyType',
                    'MixedAssignment',
                ],
            ],
            'promotedPropertyNoExtendedConstructor' => [
                'code' => '<?php
                    class A
                    {
                        public function __construct(
                            public string $name,
                        ) {}
                    }

                    class B extends A
                    {
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'promotedPropertyReadInTheBodyOfAParentConstructor' => [
                'code' => '<?php
                    class A
                    {
                        public string $copy;

                        public function __construct(public string $title)
                        {
                            $this->copy = $this->title;
                        }
                    }

                    final class B extends A
                    {
                        public function __construct()
                        {
                            parent::__construct("b");
                        }
                    }

                    final class C extends A
                    {
                        public function __construct()
                        {
                            A::__construct("c");
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'promotedPropertyOfAncestorConstructorCalledByItsName' => [
                'code' => '<?php
                    class A
                    {
                        public function __construct(
                            public string $name,
                        ) {}
                    }

                    class B extends A {}

                    final class C extends B
                    {
                        public function __construct()
                        {
                            A::__construct("c");
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyWithoutTypeSuppressingIssueAndAssertingNull' => [
                'code' => '<?php
                    class A {
                        /** @return void */
                        function foo() {
                            $boop = $this->foo === null && rand(0,1);

                            echo $this->foo->baz;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'UndefinedThisPropertyFetch',
                    'MixedAssignment',
                    'MixedArgument',
                    'MixedMethodCall',
                    'MixedPropertyFetch',
                ],
            ],
            'sharedPropertyInIf' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $foo = 0;
                    }
                    class B {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new A(): (rand(0, 10) ? new B() : null);
                    $b = null;

                    if ($a instanceof A || $a instanceof B) {
                        $b = $a->foo;
                    }',
                'assertions' => [
                    '$b' => 'int|null|string',
                ],
            ],
            'sharedPropertyInElseIf' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $foo = 0;
                    }
                    class B {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new A() : new B();
                    if (rand(0, 1)) {
                        $a = null;
                    }
                    $b = null;

                    if (rand(0, 10) === 4) {
                        // do nothing
                    }
                    elseif ($a instanceof A || $a instanceof B) {
                        $b = $a->foo;
                    }',
                'assertions' => [
                    '$b' => 'int|null|string',
                ],
            ],
            'nullablePropertyCheck' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $aa = "";
                    }

                    class B {
                        /** @var A|null */
                        public $bb;
                    }

                    $b = rand(0, 10) ? new A() : new B();

                    if ($b instanceof B && isset($b->bb) && $b->bb->aa === "aa") {
                        echo $b->bb->aa;
                    }',
            ],
            'nullablePropertyAfterGuard' => [
                'code' => '<?php
                    class A {
                        /** @var string|null */
                        public $aa;
                    }

                    $a = new A();

                    if (!$a->aa) {
                        $a->aa = "hello";
                    }

                    echo substr($a->aa, 1);',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'nullableStaticPropertyWithIfCheck' => [
                'code' => '<?php
                    class A {
                        /** @var A|null */
                        public static $fooFoo;

                        public static function getFoo(): A {
                            if (!self::$fooFoo) {
                                self::$fooFoo = new A();
                            }

                            return self::$fooFoo;
                        }
                    }',
            ],
            'reflectionProperties' => [
                'code' => '<?php
                    class Foo {
                    }

                    $a = new \ReflectionMethod(Foo::class, "__construct");

                    echo $a->name . " - " . $a->class;',
            ],
            'grandparentReflectedProperties' => [
                'code' => '<?php
                    $a = new DOMElement("foo");
                    $owner = $a->ownerDocument;',
                'assertions' => [
                    '$owner' => 'DOMDocument|null',
                ],
            ],
            'propertyMapHydration' => [
                'code' => '<?php
                    function foo(DOMElement $e) : void {
                        echo $e->attributes->length;
                    }',
            ],
            'genericTypeFromPropertyMap' => [
                'code' => '<?php
                    function foo(DOMElement $e) : ?DOMAttr {
                        return $e->attributes->item(0);
                    }',
            ],
            'propertyMapKeyMatchesDeclaredCase' => [
                'code' => '<?php
                    namespace Parle;

                    class Parser {
                        public $reduceId;

                        public function __construct() {
                            $this->reduceId = 1;
                        }
                    }

                    class RParser {
                        public $reduceId;

                        public function __construct() {
                            $this->reduceId = 1;
                        }
                    }

                    $parser = new Parser();
                    $rparser = new RParser();
                    $parserId = $parser->reduceId;
                    $rparserId = $rparser->reduceId;',
                'assertions' => [
                    '$parserId===' => 'int',
                    '$rparserId===' => 'int',
                ],
            ],
            'propertyMapNestedNamespaceKeyMatchesDeclaredCase' => [
                'code' => '<?php
                    namespace MongoDB\Driver\Exception;

                    class CommandException extends \Exception {
                        public $resultDocument;

                        public function __construct() {
                            $this->resultDocument = new \stdClass();
                        }
                    }

                    class RuntimeException extends \Exception {
                        public $errorLabels;

                        public function __construct() {
                            $this->errorLabels = null;
                        }
                    }

                    $command = new CommandException();
                    $runtime = new RuntimeException();
                    $document = $command->resultDocument;
                    $labels = $runtime->errorLabels;',
                'assertions' => [
                    '$document===' => 'object',
                    '$labels===' => 'array<array-key, mixed>|null',
                ],
            ],
            'propertyMapTypesCamelCaseProperties' => [
                'code' => '<?php
                    namespace MongoDB\Driver;

                    final class WriteConcernError {}

                    final class WriteResult {
                        /** @var mixed */
                        public $insertedCount = null;
                        /** @var mixed */
                        public $writeConcernError = null;
                    }

                    $r = new WriteResult();
                    $inserted = $r->insertedCount;
                    $error = $r->writeConcernError;',
                'assertions' => [
                    '$inserted===' => 'int|null',
                    '$error===' => 'MongoDB\Driver\WriteConcernError|null',
                ],
            ],
            'goodArrayProperties' => [
                'code' => '<?php
                    interface I1 {}

                    class A1 implements I1{}

                    class B1 implements I1 {}

                    class C1 {
                        /** @var array<I1> */
                        public $is = [];
                    }

                    $c = new C1;
                    $c->is = [new A1];
                    $c->is = [new A1, new A1];
                    $c->is = [new A1, new B1];',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'issetPropertyDoesNotExist' => [
                'code' => '<?php
                    class A {
                    }

                    $a = new A();

                    if (isset($a->bar)) {

                    }',
            ],
            'notSetInConstructorButHasDefault' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a = 0;

                        public function __construct() { }
                    }',
            ],
            'propertySetInPrivateMethod' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            $this->foo();
                        }

                        private function foo(): void {
                            $this->a = 5;
                        }
                    }',
            ],
            'definedInTraitSetInConstructor' => [
                'code' => '<?php
                    trait A {
                        /** @var string **/
                        public $a;
                    }
                    class B {
                        use A;

                        public function __construct() {
                            $this->a = "hello";
                        }
                    }',
            ],
            'propertySetInNestedPrivateMethod' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            $this->foo();
                        }

                        private function foo(): void {
                            $this->bar();
                        }

                        private function bar(): void {
                            $this->a = 5;
                        }
                    }',
            ],
            'propertyArrayIssetAssertion' => [
                'code' => '<?php
                    function bar(string $s): void { }

                    class A {
                        /** @var array<string, string> */
                        public $a = [];

                        private function foo(): void {
                            if (isset($this->a["hello"])) {
                                bar($this->a["hello"]);
                            }
                        }
                    }',
            ],
            'propertyArrayIssetAssertionWithVariableOffset' => [
                'code' => '<?php
                    function bar(string $s): void { }

                    class A {
                        /** @var array<string, string> */
                        public $a = [];

                        private function foo(): void {
                            $b = "hello";

                            if (!isset($this->a[$b])) {
                                return;
                            }

                            bar($this->a[$b]);
                        }
                    }',
            ],
            'staticPropertyArrayIssetAssertionWithVariableOffset' => [
                'code' => '<?php
                    function bar(string $s): void { }

                    class A {
                        /** @var array<string, string> */
                        public static $a = [];
                    }

                    function foo(): void {
                        $b = "hello";

                        if (!isset(A::$a[$b])) {
                            return;
                        }

                        bar(A::$a[$b]);
                    }',
            ],
            'staticPropertyArrayIssetAssertionWithVariableOffsetAndElse' => [
                'code' => '<?php
                    function bar(string $s): void { }

                    class A {
                        /** @var array<string, string> */
                        public static $a = [];
                    }

                    function foo(): void {
                        $b = "hello";

                        if (!isset(A::$a[$b])) {
                            $g = "bar";
                        } else {
                            bar(A::$a[$b]);
                            $g = "foo";
                        }

                        bar($g);
                    }',
            ],
            'traitConstructor' => [
                'code' => '<?php
                    trait T {
                      /** @var string **/
                      public $foo;

                      public function __construct() {
                        $this->foo = "hello";
                      }
                    }

                    class A {
                        use T;
                    }',
            ],
            'abstractClassWithNoConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;
                    }',
            ],
            'abstractClassConstructorAndChildConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;

                        public function __construct() {
                            $this->foo = "";
                        }
                    }

                    class B extends A {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
            ],
            'abstractClassConstructorAndImplicitChildConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;

                        public function __construct(int $bar) {
                            $this->foo = (string)$bar;
                        }
                    }

                    class B extends A {}

                    class E extends \Exception{}',
            ],
            'notSetInEmptyConstructor' => [
                'code' => '<?php
                    /** @psalm-suppress PropertyNotSetInConstructor */
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() { }
                    }',
            ],
            'extendsClassWithPrivateConstructorSet' => [
                'code' => '<?php
                    namespace Q;

                    class Base
                    {
                        /**
                         * @var string
                         */
                        private $aString;

                        public function __construct()
                        {
                            $this->aString = "aa";
                            echo($this->aString);
                        }
                    }

                    class Descendant extends Base
                    {
                        /**
                         * @var bool
                         */
                        private $aBool;

                        public function __construct()
                        {
                            parent::__construct();
                            $this->aBool = true;
                        }
                    }',
            ],
            'extendsClassWithPrivateAndException' => [
                'code' => '<?php
                    abstract class A extends \Exception {
                        /** @var string **/
                        private $p;

                        /** @param string $p **/
                        final public function __construct($p) {
                            $this->p = $p;
                        }
                    }

                    final class B extends A {}',
            ],
            'setInAbstractMethod' => [
                'code' => '<?php
                    interface I {
                        public function foo(): void;
                    }

                    /** @psalm-suppress PropertyNotSetInConstructor */
                    abstract class A implements I {
                        /** @var string */
                        public $bar;

                        public function __construct() {
                            $this->foo();
                        }
                    }

                    class B extends A {
                        public function foo(): void {
                            $this->bar = "hello";
                        }
                    }',
            ],
            'callsPrivateParentMethodThenUsesParentInitializedProperty' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $bar;

                        public function __construct() {
                            $this->setBar();
                        }

                        private function setBar(): void {
                            $this->bar = "hello";
                        }
                    }

                    class B extends A {
                        public function __construct() {
                            parent::__construct();

                            echo $this->bar;
                        }
                    }',
            ],
            'setInFinalMethod' => [
                'code' => '<?php
                    class C
                    {
                        /**
                         * @var string
                         */
                        private $a;

                        /**
                         * @var string
                         */
                        private $b;

                        /**
                         * @param string[] $opts
                         * @psalm-param array{a:string,b:string} $opts
                         */
                        public function __construct(array $opts)
                        {
                            $this->setOptions($opts);
                        }

                        /**
                         * @param string[] $opts
                         * @psalm-param array{a:string,b:string} $opts
                         */
                        final public function setOptions(array $opts): void
                        {
                            $this->a = $opts["a"] ?? "defaultA";
                            $this->b = $opts["b"] ?? "defaultB";
                        }
                    }',
            ],
            'setInFinalClass' => [
                'code' => '<?php
                    final class C
                    {
                        /**
                         * @var string
                         */
                        private $a;

                        /**
                         * @var string
                         */
                        private $b;

                        /**
                         * @param string[] $opts
                         * @psalm-param array{a:string,b:string} $opts
                         */
                        public function __construct(array $opts)
                        {
                            $this->setOptions($opts);
                        }

                        /**
                         * @param string[] $opts
                         * @psalm-param array{a:string,b:string} $opts
                         */
                        public function setOptions(array $opts): void
                        {
                            $this->a = $opts["a"] ?? "defaultA";
                            $this->b = $opts["b"] ?? "defaultB";
                        }
                    }',
            ],
            'selfPropertyType' => [
                'code' => '<?php
                    class Node
                    {
                        /** @var self|null */
                        public $next;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->next = new Node();
                            }
                        }
                    }

                    $node = new Node();
                    $next = $node->next;',
                'assertions' => [
                    '$next' => 'Node|null',
                ],
            ],
            'perPropertySuppress' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var int
                         * @psalm-suppress PropertyNotSetInConstructor
                         */
                        public $a;

                        public function __construct() { }
                    }',
            ],
            'analyzePropertyMappedClass' => [
                'code' => '<?php
                    namespace PhpParser\Node\Stmt;

                    use PhpParser\Node;

                    class Finally_ extends Node\Stmt
                    {
                        /** @var list<Node\Stmt> Statements */
                        public $stmts;

                        /**
                         * Constructs a finally node.
                         *
                         * @param list<Node\Stmt> $stmts      Statements
                         * @param array<string, mixed>  $attributes Additional attributes
                         */
                        public function __construct(array $stmts = array(), array $attributes = array()) {
                            parent::__construct($attributes);
                            $this->stmts = $stmts;
                        }

                        public function getSubNodeNames() : array {
                            return array("stmts");
                        }

                        public function getType() : string {
                            return "Stmt_Finally";
                        }
                    }',
                'assertions' => [],
            ],
            'propertyMapOfPhpParserNodesAllowsWhatTheLibraryDeclares' => [
                'code' => '<?php
                    namespace PhpParser\Node {
                        interface Expr {}

                        class InterpolatedStringPart {}

                        class MatchArm {
                            /** @var mixed */
                            public $conds = null;
                        }
                    }

                    namespace PhpParser\Node\Expr {
                        class ShellExec {
                            /** @var mixed */
                            public $parts = [];
                        }
                    }

                    namespace App {
                        use PhpParser\Node\Expr;
                        use PhpParser\Node\Expr\ShellExec;
                        use PhpParser\Node\MatchArm;

                        function literalParts(ShellExec $e): int {
                            $literals = 0;

                            foreach ($e->parts as $part) {
                                if (!$part instanceof Expr) {
                                    $literals++;
                                }
                            }

                            return $literals;
                        }

                        function isDefault(MatchArm $arm): bool {
                            return $arm->conds === [] || $arm->conds === null;
                        }
                    }',
            ],
            'staticPropertyOfAStaticTypeInAFinalClassIsTheClass' => [
                'code' => '<?php
                    trait Singleton {
                        /** @var static|null */
                        private static $instance;

                        public static function get(): static {
                            return self::$instance ??= new static();
                        }

                        public static function reset(): void {
                            self::$instance = new static();
                        }
                    }

                    final class Registry {
                        use Singleton;
                    }

                    final class Config {
                        /** @var static|null */
                        public static $current;

                        public static function use(Config $config): void {
                            self::$current = $config;
                        }
                    }',
            ],
            'privatePropertyAccessible' => [
                'code' => '<?php
                    class A {
                      /** @var string */
                      private $foo;

                      public function __construct(string $foo) {
                        $this->foo = $foo;
                      }

                      private function bar() : void {}
                    }

                    class B extends A {
                      /** @var string */
                      private $foo;

                      public function __construct(string $foo) {
                        $this->foo = $foo;
                        parent::__construct($foo);
                      }
                    }',
            ],
            'privatePropertyAccessibleDifferentType' => [
                'code' => '<?php
                    class A {
                      /** @var int */
                      private $foo;

                      public function __construct(string $foo) {
                        $this->foo = 5;
                      }

                      private function bar() : void {}
                    }

                    class B extends A {
                      /** @var string */
                      private $foo;

                      public function __construct(string $foo) {
                        $this->foo = $foo;
                        parent::__construct($foo);
                      }
                    }',
            ],
            'privatePropertyAccessibleInTwoSubclasses' => [
                'code' => '<?php
                    class A {
                        public function __construct() {}
                    }
                    class B extends A {
                        /**
                         * @var int
                         */
                        private $prop;

                        public function __construct()
                        {
                            parent::__construct();
                            $this->prop = 1;
                        }
                    }
                    class C extends A {
                        /**
                         * @var int
                         */
                        private $prop;

                        public function __construct()
                        {
                            parent::__construct();
                            $this->prop = 2;
                        }
                    }',
            ],
            'noIssueWhenSuppressingMixedAssignmentForProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string|null */
                        public $foo;

                        /** @param mixed $a */
                        public function barBar($a): void
                        {
                            $this->foo = $a;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment',
                ],
            ],
            'propertyAssignmentToMixed' => [
                'code' => '<?php
                    class C {
                        /** @var string|null */
                        public $foo;
                    }

                    /** @param mixed $a */
                    function barBar(C $c, $a): void
                    {
                        $c->foo = $a;
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment',
                ],
            ],
            'propertySetInBothIfBranches' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var int */
                        private $status;

                        public function __construct(int $in)
                        {
                            if (rand(0, 1)) {
                                $this->status = 1;
                            } else {
                                $this->status = $in;
                            }
                        }
                    }',
            ],
            'propertySetInPrivateMethodWithIfAndElse' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->foo();
                            } else {
                                $this->bar();
                            }
                        }

                        private function foo(): void {
                            $this->a = 5;
                        }

                        private function bar(): void {
                            $this->a = 5;
                        }
                    }',
            ],
            'allowMixedAssignmentWhenDesired' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var mixed
                         */
                        private $mixed = "hello";

                        /**
                         * @param mixed $value
                         */
                        public function setMixed($value): void
                        {
                            $this->mixed = $value;
                        }
                    }',
            ],
            'suppressUndefinedThisPropertyFetch' => [
                'code' => '<?php
                    class A {
                        public function __construct() {
                            /** @psalm-suppress UndefinedThisPropertyAssignment */
                            $this->bar = rand(0, 1) ? "hello" : null;
                        }

                        /** @psalm-suppress UndefinedThisPropertyFetch */
                        public function foo() : void {
                            if ($this->bar === null && rand(0, 1)) {}
                        }
                    }',
            ],
            'suppressUndefinedPropertyFetch' => [
                'code' => '<?php
                    class A {
                        public function __construct() {
                            /** @psalm-suppress UndefinedThisPropertyAssignment */
                            $this->bar = rand(0, 1) ? "hello" : null;
                        }
                    }

                    $a = new A();
                    /** @psalm-suppress UndefinedPropertyFetch */
                    if ($a->bar === null && rand(0, 1)) {}',
            ],
            'setPropertiesOfStdClass' => [
                'code' => '<?php
                    $a = new stdClass();
                    $a->b = "c";',
                'assertions' => [
                    '$a' => 'stdClass',
                    '$a->b' => 'string',
                ],
            ],
            'getPropertiesOfSimpleXmlElement' => [
                'code' => '<?php
                    $a = new SimpleXMLElement("<person><child role=\"son\"></child></person>");
                    $b = $a->b;',
                'assertions' => [
                    '$a' => 'SimpleXMLElement',
                    '$a->b' => 'SimpleXMLElement|null',
                    '$b' => 'SimpleXMLElement|null',
                ],
            ],
            'allowLessSpecificReturnTypeForOverriddenMethod' => [
                'code' => '<?php
                    class A {
                        public function aa(): ?string {
                            return "bar";
                        }
                    }

                    class B extends A {
                        public static function aa(): ?string {
                            return rand(0, 1) ? "bar" : null;
                        }
                    }

                    class C extends A {
                        public static function aa(): ?string {
                            return "bar";
                        }
                    }',
            ],
            'allowLessSpecificReturnTypeForInterfaceMethod' => [
                'code' => '<?php
                    interface Foo {
                        public static function foo(): ?string;
                    }

                    class Bar implements Foo {
                        public static function foo(): ?string
                        {
                            return "bar";
                        }
                    }

                    class Baz implements Foo {
                        /**
                         * @return string $baz
                         */
                        public static function foo(): ?string
                        {
                            return "baz";
                        }
                    }

                    class Bax implements Foo {
                        /**
                         * @return null|string $baz
                         */
                        public static function foo(): ?string
                        {
                            return "bax";
                        }
                    }

                    class Baw implements Foo {
                        /**
                         * @return null|string $baz
                         */
                        public static function foo(): ?string
                        {
                            /** @var null|string $val */
                            $val = "baw";

                            return $val;
                        }
                    }',
            ],
            'staticPropertyMethodCall' => [
                'code' => '<?php
                    class A {
                        /** @var self|null */
                        public static $instance;

                        /** @var string|null */
                        public $bat;

                        public function foo() : void {
                            if (self::$instance) {
                                self::$instance->bar();
                                echo self::$instance->bat;
                            }
                        }

                        public function bar() : void {}
                    }

                    if (A::$instance) {
                        A::$instance->bar();
                        echo A::$instance->bat;
                    }',
            ],
            'nonStaticPropertyMethodCall' => [
                'code' => '<?php
                    class A {
                        /** @var self|null */
                        public $instance;

                        /** @var string|null */
                        public $bat;

                        public function foo() : void {
                            if ($this->instance) {
                                $this->instance->bar();
                                echo $this->instance->bat;
                            }
                        }

                        public function bar() : void {}
                    }

                    $a = new A();

                    if ($a->instance) {
                        $a->instance->bar();
                        echo $a->instance->bat;
                    }',
            ],
            'staticPropertyOfStaticTypeMethodCall' => [
                'code' => '<?php
                    class A {
                        /** @var static|null */
                        public $instance;
                    }

                    class B extends A {
                        /** @var string|null */
                        public $bat;

                        public function foo() : void {
                            if ($this->instance) {
                                $this->instance->bar();
                                echo $this->instance->bat;
                            }
                        }

                        public function bar() : void {}
                    }',
            ],
            'classStringPropertyType' => [
                'code' => '<?php
                    class C {
                        /** @psalm-var array<class-string, int> */
                        public $member = [
                            InvalidArgumentException::class => 1,
                        ];
                    }',
            ],
            'allowPrivatePropertySetAfterInstanceof' => [
                'code' => '<?php
                    class A {
                        /** @var string|null */
                        private $foo;

                        public function bar() : void {
                            if (!$this instanceof B) {
                                return;
                            }

                            $this->foo = "hello";
                        }
                    }

                    class B extends A {}',
            ],
            'noCrashForAbstractConstructorWithInstanceofInterface' => [
                'code' => '<?php
                    abstract class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            if ($this instanceof I) {
                                $this->a = $this->bar();
                            } else {
                                $this->a = 6;
                            }
                        }
                    }

                    interface I {
                        public function bar() : int;
                    }',
            ],
            'abstractConstructorWithInstanceofClass' => [
                'code' => '<?php
                    abstract class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            if ($this instanceof B) {
                                $this->a = $this->bar();
                            } else {
                                $this->a = 6;
                            }
                        }
                    }

                    class B extends A {
                        public function bar() : int {
                            return 3;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
            ],
            'inheritDocPropertyTypes' => [
                'code' => '<?php
                    class X {
                        /**
                         * @var string|null
                         */
                        public $a;

                        /**
                         * @var string|null
                         */
                        public static $b;
                    }

                    class Y extends X {
                        public $a = "foo";
                        public static $b = "foo";
                    }

                    (new Y)->a = "hello";
                    echo (new Y)->a;
                    Y::$b = "bar";
                    echo Y::$b;',
            ],
            'subclassPropertySetInParentConstructor' => [
                'code' => '<?php
                    class Base {
                        /** @var string */
                        protected $prop;
                        public function __construct(string $s) {
                            $this->prop = $s;
                        }
                    }

                    class Child extends Base {
                        /** @var string */
                        protected $prop;
                    }',
            ],
            'callInParentContext' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $i = 1;
                    }

                    abstract class B
                    {
                        /**
                         * @var string
                         */
                        protected $foo;

                        /**
                         * @var A[]
                         */
                        private $as = [];

                        public function __construct()
                        {
                            $this->foo = "";
                            $this->bar();
                        }

                        public function bar(): void
                        {
                            \usort($this->as, function (A $a, A $b): int {
                                return $b->i <=> $a->i;
                            });
                        }
                    }

                    class C extends B
                    {
                        public function __construct()
                        {
                            parent::__construct();
                        }
                    }',
            ],
            'staticVarSelf' => [
                'code' => '<?php
                    class Foo {
                        /** @var self */
                        public static $current;
                    }

                    $a = Foo::$current;',
                'assertions' => [
                    '$a' => 'Foo',
                ],
            ],
            'noMixedErrorWhenAssignmentExpectsMixed' => [
                'code' => '<?php
                    class A {
                        /** @var array<string, mixed> $bar */
                        public $bar = [];

                        /** @param mixed $b */
                        public function foo($b) : void {
                            $this->bar["a"] = $b;
                        }
                    }',
            ],
            'propertySetInGrandparentExplicitly' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var string
                         */
                        public $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }
                    }
                    class B extends A {}
                    class C extends B {
                        public function __construct(string $s) {
                            A::__construct($s);
                        }
                    }',
            ],
            'propertySetInGrandparentImplicitly' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var string
                         */
                        public $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }
                    }
                    class B extends A {}
                    class C extends B {}',
            ],
            'uninitializedPropertySuppressPropertyNotSetInConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function __construct() {
                            $this->setFoo(); // public method that circumvents checks
                            echo strlen($this->foo);
                        }

                        public function setFoo() : void {
                            $this->foo = "foo";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['PropertyNotSetInConstructor'],
            ],
            'uninitializedPropertySuppressPropertyNotSetInAbstractConstructor' => [
                'code' => '<?php
                    abstract class A {
                          /** @readonly */
                          public string $s;

                          abstract public function __construct(string $s);
                    }',
            ],
            'setTKeyedArrayPropertyType' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @psalm-var array{from:bool, to:bool}
                         */
                        protected $changed = [
                            "from" => false,
                            "to" => false,
                        ];

                        /**
                         * @psalm-param "from"|"to" $property
                         */
                        public function ChangeThing(string $property) : void {
                            $this->changed[$property] = true;
                        }
                    }',
            ],
            'noRedundantConditionWhenCheckingInitializations' => [
                'code' => '<?php
                    final class Clazz {
                        /**
                         * @var bool
                         */
                        public $x;

                        /**
                         * @var int
                         */
                        public $y = 0;

                        public function func1 (): bool {
                            if ($this->y) {
                                return true;
                            }
                            return false;
                        }

                        public function func2 (): int {
                            if ($this->y) {
                                return 1;
                            }
                            return 2;
                        }

                        public function __construct () {
                            $this->x = false;
                            if ($this->func1()) {
                                $this->y = $this->func2();
                            }
                            $this->func2();
                        }
                    }',
            ],
            'noRedundantConditionWhenCheckingInitializationsEdgeCases' => [
                'code' => '<?php
                    final class Clazz {
                        /**
                         * @var bool
                         */
                        public $x;

                        /**
                         * @var int
                         */
                        public $y = 0;

                        public function func1 (): bool {
                            if ($this->y !== 0) {
                                return true;
                            }
                            return false;
                        }

                        public function func2 (): int {
                            if ($this->y !== 0) {
                                return $this->y;
                            }
                            return 2;
                        }

                        public function __construct () {
                            $this->x = false;
                            if ($this->func1()) {
                                $this->y = $this->func2();
                            }
                            $this->func2();
                        }
                    }',
            ],
            'propertySetInProtectedMethodWithConstant' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            $this->foo();
                        }

                        protected function foo(): void {
                            $this->a = 5;
                        }
                    }

                    class B extends A {
                        const HELLO = "HELLO";

                        protected function foo() : void {
                            $this->a = 6;

                            echo self::HELLO;
                        }
                    }',
            ],
            'setPropertyInParentProtectedMethodExplicitCall' => [
                'code' => '<?php
                    abstract class A {
                        public function __construct() {
                            $this->overriddenByB();
                        }

                        protected function overriddenByB(): void {
                            // do nothing
                        }
                    }

                    class B extends A {
                        /** @var int */
                        private $foo;

                        /** @var int */
                        protected $bar;

                        public function __construct() {
                            parent::__construct();
                        }

                        protected final function overriddenByB(): void {
                            $this->foo = 1;
                            $this->bar = 1;
                        }
                    }',
            ],
            'setPropertyInParentProtectedMethodImplicitCall' => [
                'code' => '<?php
                    abstract class A {
                        public function __construct() {
                            $this->overriddenByB();
                        }

                        protected function overriddenByB(): void {
                            // do nothing
                        }
                    }

                    class B extends A {
                        /** @var int */
                        private $foo;

                        /** @var int */
                        protected $bar;

                        protected final function overriddenByB(): void {
                            $this->foo = 1;
                            $this->bar = 1;
                        }
                    }',
            ],
            'setPropertyInParentWithPrivateConstructor' => [
                'code' => '<?php
                    namespace NS;

                    class Base
                    {
                        /**
                         * @var int
                         */
                        protected $a;

                        final private function __construct()
                        {
                            $this->setA();
                        }

                        private function setA() : void {
                            $this->a = 5;
                        }

                        public static function getInstance(): self { return new static; }
                    }

                    class Concrete extends Base {}',
            ],
            'preventCrashWhenCallingInternalMethodInPropertyInitialisationChecks' => [
                'code' => '<?php
                    class Foo extends \RuntimeException {
                        /** @var array */
                        protected $serializableTrace;

                        public function __construct() {
                            parent::__construct("hello", 0);
                            $this->serializableTrace = $this->getTrace();
                        }
                    }

                    class Bar extends Foo {}',
            ],
            'inferPropertyTypesForSimpleConstructors' => [
                'code' => '<?php
                    class A {
                        private $foo;
                        private $bar;

                        public function __construct(int $foot, string $bart) {
                            $this->foo = $foot;
                            $this->bar = $bart;
                        }

                        public function getFoo() : int {
                            return $this->foo;
                        }

                        public function getBar() : string {
                            return $this->bar;
                        }
                    }',
            ],
            'nullableDocblockTypedPropertyNoConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private $foo;
                    }',
            ],
            'nullableDocblockTypedPropertyEmptyConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private $foo;

                        public function __construct() {}
                    }',
            ],
            'nullableDocblockTypedPropertyUseBeforeInitialised' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private $foo;

                        public function __construct() {
                            echo $this->foo;
                        }
                    }',
            ],
            'dontAlterClosureParams' => [
                'code' => '<?php
                    class C {
                      /** @var array */
                      public $i;

                      public function __construct() {
                        $this->i = [
                          function (Exception $e): void {},
                          function (LogicException $e): void {},
                        ];
                      }
                    }',
            ],
            'inferSpreadParamType' => [
                'code' => '<?php
                    class Tag {}
                    class EntityTags {
                        private $tags;

                        /** @no-named-arguments */
                        public function __construct(Tag ...$tags) {
                            $this->tags = $tags;
                        }
                    }',
            ],
            'staticPropertyDefaultWithStaticType' => [
                'code' => '<?php
                    class Test {
                        /** @var array<int, static> */
                        private static $t1 = [];

                        /** @var array<int, static> */
                        private $t2 = [];
                    }',
            ],
            'propagateIgnoreNullableOnPropertyFetch' => [
                'code' => '<?php
                    class A {
                        public string $s = "hey";
                    }

                    /**
                     * @psalm-ignore-nullable-return
                     */
                    function foo() : ?A {
                        return rand(0, 1) ? new A : null;
                    }

                    function takesString(string $_s) : void {}

                    $foo = foo();

                    if ($foo->s !== null) {}
                    echo $foo->s ?? "bar";
                    takesString($foo->s);',
            ],
            'noMissingPropertyWhenArrayTypeProvided' => [
                'code' => '<?php

                    class Foo {
                        private $bar;

                        /** @psalm-param array{key: string} $bar */
                        public function __construct(array $bar) {
                            $this->bar = $bar;
                        }

                        public function bar(): void {
                            echo $this->bar["key"];
                        }
                    }',
            ],
            'rememberThisPropertyAsssignmentsInMethod' => [
                'code' => '<?php
                    class A {
                        public bool $foo = false;

                        public function bar() : void {
                            $this->foo = false;
                            $this->maybeChange();

                            if ($this->foo) {}
                        }

                        public function maybeChange() : void {
                            if (rand(0, 1)) {
                                $this->foo = true;
                            }
                        }
                    }',
            ],
            'testRemoveClauseAfterReassignment' => [
                'code' => '<?php
                    class Test {
                        /** @var ?bool */
                        private $foo;

                        public function run(): void {
                            $this->foo = false;
                            $this->bar();
                            if ($this->foo === true) {}
                        }

                        private function bar(): void {
                            if (mt_rand(0, 1)) {
                                $this->foo = true;
                            }
                        }
                    }',
            ],
            'allowIssetOnTypedProperty' => [
                'code' => '<?php
                    class A {
                        public string $a;

                        public function __construct(bool $b) {
                            if ($b) {
                                $this->a = "hello";
                            }

                            if (isset($this->a)) {
                                echo $this->a;
                                $this->a = "bello";
                            }

                            $this->a = "bar";
                        }
                    }',
            ],
            'allowGoodArrayPushOnArrayValue' => [
                'code' => '<?php
                    class MyClass {
                        /**
                         * @var int[]
                         */
                        private $prop = [];

                        /**
                         * @return void
                         */
                        public function foo() {
                            array_push($this->prop, 5);
                        }
                    }',
            ],
            'someConditionalCallToParentConstructor' => [
                'code' => '<?php
                    class GrandParentClassDoesNotDefine {
                        public function __construct() {}
                    }

                    class ParentClassDefinesVar extends GrandParentClassDoesNotDefine {
                        protected string $val;

                        public function __construct() {
                            $this->val = "hello";
                            if (rand(0, 1)) {
                                parent::__construct();
                            }
                        }
                    }

                    class ChildClass extends ParentClassDefinesVar {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
            ],
            'noConditionalCallToParentConstructor' => [
                'code' => '<?php
                    class GrandParentClassDoesNotDefine {
                        public function __construct() {}
                    }

                    class ParentClassDefinesVar extends GrandParentClassDoesNotDefine {
                        protected string $val;

                        public function __construct() {
                            $this->val = "hello";
                            parent::__construct();
                        }
                    }

                    class ChildClass extends ParentClassDefinesVar {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
            ],
            'allowByReferenceAssignmentToUninitializedNullableProperty' => [
                'code' => '<?php
                    class C {
                        private ?\Closure $onCancel;

                        public function __construct() {
                            $this->foo($this->onCancel);
                        }

                        /**
                         * @param mixed $onCancel
                         * @param-out \Closure $onCancel
                         */
                        public function foo(&$onCancel) : void {
                            $onCancel = function (): void {};
                        }
                    }',
            ],
            'dontCarryAssertionsOver' => [
                'code' => '<?php
                    class A
                    {
                        private string $network;

                        public function __construct(string $s)
                        {
                            $this->network = $s;
                            $this->firstCheck();
                            $this->secondCheck();
                        }

                        public function firstCheck(): void
                        {
                            if ($this->network === "x") {
                                return;
                            }
                        }

                        public function secondCheck(): void
                        {
                            if ($this->network === "x") {
                                return;
                            }
                        }
                    }',
            ],
            'useVariableAccessInStatic' => [
                'code' => '<?php
                    class A2 {
                        public static string $title = "foo";
                        public static string $label = "bar";
                    }

                    $model = new A2();
                    $message = $model::$title;
                    $message .= $model::$label;
                    echo $message;',
            ],
            'staticPropertyInFinalMethod' => [
                'code' => '<?php
                    abstract class Foo {
                        /** @var static */
                        protected Foo $foo;
                    }

                    final class Bar extends Foo {
                        public function __construct(Bar $bar) {
                            $this->foo = $bar;
                        }

                        public function baz(): Bar {
                            return $this->foo;
                        }
                    }',
            ],
            'aliasedFinalMethod' => [
                'code' => '<?php
                    trait A {
                        private int $prop;
                        public final function setProp(int $prop): void {
                            $this->prop = $prop;
                        }
                    }

                    class B {
                        use A {
                            setProp as setPropFinal;
                        }

                        public function __construct() {
                            $this->setPropFinal(1);
                        }
                    }',
            ],
            'aliasedAsFinalMethod' => [
                'code' => '<?php
                    trait A {
                        private int $prop;
                        public function setProp(int $prop): void {
                            $this->prop = $prop;
                        }
                    }

                    class B {
                        use A {
                            setProp as final setPropFinal;
                        }

                        public function __construct() {
                            $this->setPropFinal(1);
                        }
                    }',
            ],
            'staticPropertyAssertion' => [
                'code' => '<?php
                    class Foo {
                        /** @var int */
                        private static $transactionDepth;

                        function bar(): void {
                            if (self::$transactionDepth === 0) {
                            } else {
                                --self::$transactionDepth;

                                if (self::$transactionDepth === 0) {

                                }
                            }
                        }
                    }',
            ],
            'dontMemoizePropertyTypeAfterRootVarAssertion' => [
                'code' => '<?php
                    class A {
                        public string $i = "";
                    }

                    class B {
                        public int $i = 0;
                    }

                    /** @param A|B $o */
                    function takesAorB(object $o) : void {
                        echo $o->i;
                        if ($o instanceof A) {
                            echo strlen($o->i);
                        }
                    }',
            ],
            'unionPropertyType' => [
                'code' => '<?php
                    class A {
                        public string|int $i;

                        public function __construct() {
                            $this->i = 5;
                            $this->i = "hello";
                        }
                    }

                    $a = new A();

                    if ($a->i === 3) {}
                    if ($a->i === "foo") {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'setClassStringOfStatic' => [
                'code' => '<?php
                    class A {
                        public static array $stack = [];

                        public static function foo() : void {
                            $class = get_called_class();
                            $class::$stack[] = 1;
                        }
                    }',
            ],
            'staticPropertyFetchViaGetClass' => [
                'code' => '<?php
                    final class A {
                        public static int $p = 1;
                    }

                    function f(A $a): int {
                        $class = get_class($a);
                        return $class::$p;
                    }',
            ],
            'staticPropertyFetchViaGetClassOfUnion' => [
                'code' => '<?php
                    final class A {
                        public static int $p = 1;
                    }

                    final class B {
                        public static string $p = "b";
                    }

                    function f(A|B $a): int|string {
                        $class = get_class($a);
                        return $class::$p;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticPropertyFetchViaClassStringOfUnknownClass' => [
                'code' => '<?php
                    /** @param class-string $class */
                    function f(string $class): mixed {
                        return $class::$p;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticPropertyAssignmentViaClassString' => [
                'code' => '<?php
                    final class A {
                        public static int $p = 1;
                    }

                    /** @param class-string<A> $class */
                    function f(string $class): void {
                        $class::$p = 2;
                    }',
            ],
            'staticPropertyAssignmentViaClassStringOfUnknownClass' => [
                'code' => '<?php
                    /** @param class-string $class */
                    function f(string $class): void {
                        $class::$p = 2;
                    }',
            ],
            'staticPropertyFetchViaThis' => [
                'code' => '<?php
                    trait T {
                        public function fromTrait(): int {
                            return $this::$declared_in_user;
                        }
                    }

                    class Base {
                        public static int $public_prop = 1;
                        private static int $private_prop = 2;
                        protected static int $protected_prop = 3;

                        public function fromBase(): int {
                            return $this::$private_prop + $this::$protected_prop;
                        }
                    }

                    final class Child extends Base {
                        use T;

                        public static int $declared_in_user = 4;

                        public function fromChild(): int {
                            return $this::$public_prop + $this::$protected_prop;
                        }
                    }',
            ],
            'staticPropertyFetchViaObjectOfUnknownClass' => [
                'code' => '<?php
                    function f(object $o): void {
                        /** @psalm-suppress MixedAssignment */
                        $x = $o::$p;
                        echo gettype($x);
                    }',
            ],
            'staticPropertyFetchViaIntersectionOfObjects' => [
                'code' => '<?php
                    class A {
                        public static int $p = 1;
                    }

                    function narrowed(Countable $o): int {
                        if ($o instanceof A) {
                            return $o::$p;
                        }

                        return 0;
                    }

                    /** @param Countable&A $o */
                    function fromDocblock($o): int {
                        return $o::$p;
                    }',
            ],
            'staticPropertyAssignmentViaIntersectionOfObjects' => [
                'code' => '<?php
                    class A {
                        public static int $p = 1;
                    }

                    /** @param Countable&A $o */
                    function f($o): void {
                        $o::$p = 2;
                    }',
            ],
            'staticPropertyFetchViaStaticObject' => [
                'code' => '<?php
                    class A {
                        public static int $p = 1;

                        /** @psalm-suppress UnsafeInstantiation */
                        public function f(): int {
                            $a = new static();
                            return $a::$p;
                        }
                    }

                    final class B extends A {}

                    function g(B $b): int {
                        return $b::$p;
                    }',
            ],
            'promotedPublicPropertyWithDefault' => [
                'code' => '<?php
                    class A {
                        public function __construct(public int $foo = 5) {}
                    }

                    echo (new A)->foo;',
            ],
            'promotedPublicPropertyWithoutDefault' => [
                'code' => '<?php
                    class A {
                        public function __construct(public int $foo) {}
                    }

                    echo (new A(5))->foo;',
            ],
            'promotedProtectedProperty' => [
                'code' => '<?php
                    class A {
                        public function __construct(protected int $foo) {}
                    }

                    class AChild extends A {
                        public function bar() : int {
                            return $this->foo;
                        }
                    }',
            ],
            'skipConstructor' => [
                'code' => '<?php
                    class A {
                        protected string $s;

                        public function __construct() {
                            $this->s = "hello";
                        }
                    }

                    class B extends A {}

                    class C extends B {
                        public function __construct()
                        {
                            parent::__construct();

                            echo $this->s;
                        }
                    }',
            ],
            'getPropertyThatMayNotBeSet' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MissingConstructor
                     */
                    class A {
                        public string $bar;

                        public function getBar() : string {
                            /** @psalm-suppress RedundantPropertyInitializationCheck */
                            if (!isset($this->bar)) {
                                return "hello";
                            }

                            return $this->bar;
                        }

                        public function getBarAgain() : string {
                            /** @psalm-suppress RedundantPropertyInitializationCheck */
                            if (isset($this->bar)) {
                                return $this->bar;
                            }

                            return "hello";
                        }
                    }',
            ],
            'issetOfUninitializedPropertyInConstructor' => [
                'code' => '<?php
                    class X {
                        private string $x;

                        /** @param string[] $a */
                        public function __construct(array $a) {
                            foreach ($a as $key => $val) {
                                if ($key === "x") {
                                    $this->x = $val;
                                }
                            }

                            $this->x ??= "";
                        }
                    }

                    class Y {
                        private string $y;

                        public function __construct() {
                            $this->y ??= "";
                        }
                    }

                    class Z {
                        private string $z;

                        public function __construct() {
                            if (!isset($this->z)) {
                                $this->z = "";
                            }
                        }
                    }',
            ],
            'memoizePropertyAfterSetting' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {
                            /** @psalm-suppress UndefinedThisPropertyAssignment */
                            $this->b = "c";
                            echo strlen($this->b);
                        }
                    }',
            ],
            'noErrorForSplatArgs' => [
                'code' => '<?php
                    class Foo {
                        protected array $b;

                        protected function __construct(?string ...$bb) {
                            $this->b = $bb;
                        }
                    }

                    class Bar extends Foo {}',
            ],
            'noUndefinedPropertyIssueAfterSuppressingOnInterface' => [
                'code' => '<?php
                    interface I {}

                    function bar(I $i) : void {
                        /**
                         * @psalm-suppress NoInterfaceProperties
                         * @psalm-suppress MixedArgument
                         */
                        echo $i->foo;
                    }',
            ],
            'noRedundantCastWhenCheckingProperties' => [
                'code' => '<?php
                    class Foo
                    {
                        public array $map;

                        public function __construct()
                        {
                            $this->map = [];
                            $this->map["test"] = "test";

                            $this->useMap();
                        }

                        public function useMap(): void
                        {
                            $keys = array_keys($this->map);
                            $key = reset($keys);
                            echo (string) $key;
                        }
                    }',
            ],
            'ignoreUndefinedMethodOnUnion' => [
                'code' => '<?php
                    /** @psalm-no-seal-properties */
                    class NullObject {
                        /**
                         * @return null
                         */
                        public function __get(string $s) {
                            return null;
                        }
                    }

                    class User {
                        public string $name = "Dave";
                    }

                    function takesNullableUser(User|NullObject $user) : ?string {
                        $name = $user->name;

                        if ($name === null) {}

                        return $name;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'dynamicPropertyFetch' => [
                'code' => '<?php
                    class Foo {
                        public int $a = 0;
                    }

                    function takesFoo(?Foo $foo, string $b) : void {
                        /** @psalm-suppress MixedArgument */
                        echo $foo->{$b} ?? null;
                    }',
            ],
            'nullCoalesceWithNullablePropertyAccess' => [
                'code' => '<?php
                    class Bar {
                        public ?string $a = null;
                    }

                    function takesBar(?Bar $bar) : string {
                        return $bar?->a ?? "default";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'possiblyNullOnFunctionCallCoalesced' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var int */
                        public $a = 0;
                    }

                    function accessOnVar(?Foo $bar, string $b) : void {
                        /** @psalm-suppress MixedArgument */
                        echo $bar->{$b} ?? null;
                    }',
            ],
            'dontMemoizeConditionalAssignment' => [
                'code' => '<?php
                    class A {}

                    class B {
                        protected ?A $a = null;

                        public function test(): void {
                            if (!$this->a) {
                                $this->mayBeSetA();
                            }
                            if ($this->a instanceof A) {
                            }
                        }

                        protected function mayBeSetA(): void {
                            if (mt_rand(0, 1)) {
                                $this->a = new A();
                            }
                        }
                    }',
            ],
            'allowDefaultForTemplatedProperty' => [
                'code' => '<?php
                    /**
                     * @template T as string|null
                     */
                    abstract class A {
                        /** @var list<T> */
                        public $foo = [];
                    }

                    /**
                     * @extends A<string>
                     */
                    class AChild extends A {
                        public $foo = ["hello"];
                    }',
            ],
            'allowBuiltinPropertyDocblock' => [
                'code' => '<?php
                    class FooException extends LogicException {
                        /** @var int */
                        protected $code = 404;
                    }',
            ],
            'dontMemoizeFinalMutationFreeInferredMethod' => [
                'code' => '<?php
                    final class ExecutionMode
                    {
                        private bool $isAutoCommitEnabled = true;

                        public function enableAutoCommit(): void
                        {
                            $this->isAutoCommitEnabled = true;
                        }

                        public function disableAutoCommit(): void
                        {
                            $this->isAutoCommitEnabled = false;
                        }

                        public function isAutoCommitEnabled(): bool
                        {
                            return $this->isAutoCommitEnabled;
                        }
                    }

                    $mode = new ExecutionMode();
                    $mode->disableAutoCommit();
                    assert($mode->isAutoCommitEnabled() === false);

                    $mode->enableAutoCommit();
                    assert($mode->isAutoCommitEnabled() === true);',
            ],
            'promotedInheritedPropertyWithDocblock' => [
                'code' => '<?php
                    abstract class A {
                        /** @var array */
                        public array $arr;
                    }

                    final class B extends A {
                        /** @param array $arr */
                        protected function __construct(public array $arr){}
                    }',
            ],
            'nullsafeShortCircuit' => [
                'code' => '<?php
                    class Foo {
                        private ?self $nullableSelf = null;

                        public function __construct(private self $self) {}

                        public function doBar(): ?self
                        {
                            return $this->nullableSelf?->self->self;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafePropertyFetchIdenticalConstAssertsNotNull' => [
                'code' => '<?php
                    class Foo {
                        public ?string $source = null;
                    }

                    function test(?Foo $v): Foo {
                        if ("db20" === $v?->source) {
                            return $v;
                        }

                        return new Foo();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafePropertyFetchIdenticalConstReversedAssertsNotNull' => [
                'code' => '<?php
                    class Foo {
                        public ?string $source = null;
                    }

                    function test(?Foo $v): Foo {
                        if ($v?->source === "db20") {
                            return $v;
                        }

                        return new Foo();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'impossibleIntersection' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}
                    /** @psalm-suppress MissingConstructor */
                    class Baz
                    {
                        private Foo&Bar $foobar;
                    }
                    ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'intersectionPropertyAccess' => [
                'code' => '<?php

                    /** @property int $test1 */
                    class a {
                        public function __get(string $name)
                        {
                            return 0;
                        }
                    }

                    /** @var a&object{test2: "lmao"} */
                    $r = null;

                    $test1 = $r->test1;
                    $test2 = $r->test2;',
                'assertions' => [
                    '$test1===' => 'int',
                    '$test2===' => "'lmao'",
                ],
            ],
            'virtualPropertyWithGetHookIsNotSetInConstructor' => [
                'code' => '<?php
                    final class WithConstructor {
                        public int $prop { get => 5 * 40; }

                        public function __construct() {}
                    }

                    final class WithoutConstructor {
                        public int $prop { get => 5 * 40; }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'virtualPropertyWithGetAndSetHooksIsNotSetInConstructor' => [
                'code' => '<?php
                    final class MyLocale {
                        public string $languageCode;

                        public string $countryCode {
                            set (string $countryCode) {
                                $this->countryCode = strtoupper($countryCode);
                            }
                        }

                        public string $combinedCode {
                            get => sprintf("%s_%s", $this->languageCode, $this->countryCode);
                            set (string $value) {
                                $parts = explode("_", $value, 2);
                                $this->languageCode = $parts[0];
                                $this->countryCode = $parts[1] ?? "";
                            }
                        }

                        public function __construct(string $languageCode, string $countryCode) {
                            $this->languageCode = $languageCode;
                            $this->countryCode = $countryCode;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'propertyReadOnlyInClosureOfItsHookIsVirtual' => [
                'code' => '<?php
                    final class A {
                        public int $prop { get => (fn(): int => $this->prop)(); }

                        public function __construct() {}
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'nullsafeFetchOnAnUnreadNonNullPropertyReceiverIsNotNullable' => [
                'code' => '<?php
                    final class Item { public bool $flag = true; }
                    final class Holder { public function __construct(public Item $item) {} }

                    /** @var Holder $h */
                    $h = null;
                    $_r = $h->item?->flag;',
                'assertions' => ['$_r===' => 'bool'],
                'ignored_issues' => ['RedundantCondition', 'TypeDoesNotContainNull'],
                'php_version' => '8.0',
            ],
            'nullsafeMethodCallOnAnUnreadNonNullPropertyReceiverIsNotNullable' => [
                'code' => '<?php
                    final class Item { public function get(): bool { return true; } }
                    final class Holder { public function __construct(public Item $item) {} }

                    /** @var Holder $h */
                    $h = null;
                    $_r = $h->item?->get();',
                'assertions' => ['$_r===' => 'bool'],
                'ignored_issues' => ['RedundantCondition', 'TypeDoesNotContainNull'],
                'php_version' => '8.0',
            ],
            'nullsafeFetchOnAnUnreadNonNullPropertyChainIsNotNullable' => [
                'code' => '<?php
                    final class Item { public bool $flag = true; }
                    final class B { public function __construct(public Item $item) {} }
                    final class A { public function __construct(public B $b) {} }

                    /** @var A $a */
                    $a = null;
                    $_r = $a->b->item?->flag;',
                'assertions' => ['$_r===' => 'bool'],
                'ignored_issues' => ['RedundantCondition', 'TypeDoesNotContainNull'],
                'php_version' => '8.0',
            ],
            'nullsafeFetchOnAnUnreadNullablePropertyReceiverStaysNullable' => [
                'code' => '<?php
                    final class Item { public bool $flag = true; }
                    final class Holder { public function __construct(public ?Item $item) {} }

                    /** @var Holder $h */
                    $h = null;
                    $_r = $h->item?->flag;',
                'assertions' => ['$_r===' => 'bool|null'],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafeCallOnAnUnreadPropertyOfANullableHolderStaysNullable' => [
                'code' => '<?php
                    final class Item { public function get(): bool { return true; } }
                    final class Holder { public function __construct(public Item $item) {} }

                    /** @var ?Holder $h */
                    $h = null;
                    $_h = $h;
                    $_r = $h->item?->get();',
                'assertions' => ['$_h===' => 'Holder|null', '$_r===' => 'bool|null'],
                'ignored_issues' => ['PossiblyNullPropertyFetch'],
                'php_version' => '8.0',
            ],
            'nullsafeDoubleChainOnAnUnreadNonNullPropertyReceiverIsNotNullable' => [
                'code' => '<?php
                    final class Sub { public bool $flag = true; }
                    final class Item { public function __construct(public Sub $sub) {} }
                    final class Holder { public function __construct(public Item $item) {} }

                    /** @var Holder $h */
                    $h = null;
                    $_r = $h->item?->sub?->flag;',
                'assertions' => ['$_r===' => 'bool'],
                'ignored_issues' => ['RedundantCondition', 'TypeDoesNotContainNull'],
                'php_version' => '8.0',
            ],
            'nullsafeDoubleChainWithANullableInnerPropertyStaysNullable' => [
                'code' => '<?php
                    final class Sub { public bool $flag = true; }
                    final class Item { public function __construct(public ?Sub $sub) {} }
                    final class Holder { public function __construct(public Item $item) {} }

                    /** @var Holder $h */
                    $h = null;
                    $_r = $h->item?->sub?->flag;',
                'assertions' => ['$_r===' => 'bool|null'],
                'ignored_issues' => ['RedundantCondition', 'TypeDoesNotContainNull'],
                'php_version' => '8.0',
            ],
            'nullsafeFetchOnAMagicPropertyReceiverIsNotNullable' => [
                'code' => '<?php
                    final class Item { public bool $flag = true; }

                    /** @property Item $item */
                    final class Holder {
                        public function __get(string $name): mixed {
                            return new Item();
                        }
                    }

                    /** @var Holder $h */
                    $h = null;
                    $_r = $h->item?->flag;',
                'assertions' => ['$_r===' => 'bool'],
                'ignored_issues' => ['RedundantCondition', 'TypeDoesNotContainNull', 'DocblockTypeContradiction'],
                'php_version' => '8.0',
            ],
            'nullsafeFetchOfANonNullablePropertyChecksTheReceiver' => [
                'code' => '<?php
                    final class T {}

                    final class U {
                        public function __construct(public T $valueType) {}
                    }

                    final class N {
                        public ?U $unsealed = null;
                    }

                    function truthy(N $n): bool {
                        if ($n->unsealed?->valueType) {
                            return get_class($n->unsealed->valueType) === T::class;
                        }

                        return false;
                    }

                    function notNull(N $n): bool {
                        if ($n->unsealed?->valueType !== null) {
                            return get_class($n->unsealed->valueType) === T::class;
                        }

                        return false;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafeFetchOfANonNullablePropertyOfATemplateReceiverChecksTheReceiver' => [
                'code' => '<?php
                    final class U {
                        public string $s = "";
                    }

                    /**
                     * @template T of U
                     * @param T|null $u
                     */
                    function check($u): bool {
                        if ($u?->s !== null) {
                            return strlen($u->s) > 0;
                        }

                        return false;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafeChainNotNullNarrowsEveryReceiver' => [
                'code' => '<?php
                    final class B {
                        public ?string $value = null;
                    }

                    final class A {
                        public ?B $b = null;
                    }

                    final class Root {
                        public ?A $a = null;
                    }

                    function t(Root $r): void {
                        if ($r->a?->b?->value !== null) {
                            echo strlen($r->a->b->value);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafeChainIdenticalToNullNarrowsEveryReceiverOutsideTheCheck' => [
                'code' => '<?php
                    final class B {
                        public ?string $value = null;
                    }

                    final class A {
                        public ?B $b = null;
                    }

                    final class Root {
                        public ?A $a = null;
                    }

                    function afterReturn(Root $r): void {
                        if ($r->a?->b?->value === null) {
                            return;
                        }

                        echo strlen($r->a->b->value);
                    }

                    function inElse(Root $r): void {
                        if ($r->a?->b?->value === null) {
                            echo 1;
                        } else {
                            echo strlen($r->a->b->value);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafeFetchOfANonNullablePropertyIdenticalToNullChecksTheReceiver' => [
                'code' => '<?php
                    final class T {}

                    final class U {
                        public function __construct(public T $valueType) {}
                    }

                    final class N {
                        public ?U $unsealed = null;
                    }

                    function identical(N $n): bool {
                        if ($n->unsealed?->valueType === null) {
                            return false;
                        }

                        return get_class($n->unsealed->valueType) === T::class;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafePropertyNotNullNarrowsTheReceiver' => [
                'code' => '<?php
                    final class B { public ?string $value = null; }
                    final class A { public ?B $b = null; }
                    /** @var A $a */
                    $a = $_GET["a"];
                    if ($a->b?->value !== null) {
                        $x = $a->b->value;
                    } else {
                        $x = "";
                    }',
                'assertions' => [
                    '$x===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafePropertyNotNullNarrowsTheNullsafeFetch' => [
                'code' => '<?php
                    final class B { public ?string $value = null; }
                    final class A { public ?B $b = null; }
                    /** @var A $a */
                    $a = $_GET["a"];
                    if ($a->b?->value !== null) {
                        $x = $a->b?->value;
                    } else {
                        $x = "";
                    }',
                'assertions' => [
                    '$x===' => 'string',
                ],
                'ignored_issues' => ['TypeDoesNotContainNull', 'RedundantCondition'],
                'php_version' => '8.0',
            ],
            'nullsafePropertyTruthyNarrowsTheReceiver' => [
                'code' => '<?php
                    final class B { public ?string $value = null; }
                    final class A { public ?B $b = null; }
                    /** @var A $a */
                    $a = $_GET["a"];
                    if ($a->b?->value) {
                        $x = $a->b;
                    } else {
                        $x = new B();
                    }',
                'assertions' => [
                    '$x===' => 'B',
                ],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'nullsafePropertyStaysNullableOutsideTheCheck' => [
                'code' => '<?php
                    final class B { public ?string $value = null; }
                    final class A { public ?B $b = null; }
                    /** @var A $a */
                    $a = $_GET["a"];
                    if ($a->b?->value !== null) {
                        echo "set";
                    }
                    $x = $a->b?->value;',
                'assertions' => [
                    '$x===' => 'null|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inheritedConstructorCallingAMethodReturningStaticInAFinalChild' => [
                'code' => '<?php
                    abstract class Base
                    {
                        protected stdClass $prop;

                        public function __construct(stdClass $prop) {
                            $this->prop = $prop;
                            $this->touch();
                        }

                        /** @return $this */
                        public function touch(): static
                        {
                            return $this;
                        }
                    }

                    final class Child extends Base
                    {
                    }',
            ],
            'setInFinalMethodOfAParentClass' => [
                'code' => '<?php
                    abstract class Event {
                        protected string $uniqueId;

                        final protected function generateUniqueId(): void {
                            $this->uniqueId = "test";
                        }
                    }

                    final class SomeEvent extends Event {
                        public function __construct(string $someValue) {
                            $this->generateUniqueId();
                        }
                    }',
            ],
            'setInPrivateTraitMethodByAParentConstructor' => [
                'code' => '<?php
                    trait CreatedAt {
                        protected DateTimeImmutable $createdAt;

                        private function onCreated(): void {
                            $this->createdAt = new DateTimeImmutable();
                        }
                    }

                    class Foo {
                        use CreatedAt;

                        public function __construct() {
                            $this->onCreated();
                        }
                    }

                    class Bar extends Foo {}',
            ],
            'setInFinalTraitMethodByAParentConstructor' => [
                'code' => '<?php
                    trait Tr {
                        protected int $a;

                        final protected function set(): void {
                            $this->a = 5;
                        }
                    }

                    class B {
                        use Tr;

                        protected function __construct() {
                            $this->set();
                        }
                    }

                    final class Cl extends B {}',
            ],
            'setByATraitMethodCalledByItsAliasWhileTheClassOverridesTheName' => [
                'code' => '<?php
                    trait T {
                        protected int $prop;

                        protected function init(): void {
                            $this->prop = 1;
                        }
                    }

                    class A {
                        use T { init as alias; }

                        protected function init(): void {}

                        public function __construct() {
                            $this->alias();
                        }
                    }',
            ],
            'aTypedPropertyThatNoConstructorSetsIsNotNullableAfterACoalesce' => [
                'code' => '<?php
                    final class Meta {
                        public string $name;
                    }

                    function e(Meta $m): int {
                        $label = $m->name ?? "default";
                        return strlen($m->name) + strlen($label);
                    }',
                'assertions' => [],
                'ignored_issues' => ['PropertyNotSetInConstructor', 'MissingConstructor'],
            ],
            'aTypedPropertyThatNoConstructorSetsIsNotNullableAfterAnIssetTernary' => [
                'code' => '<?php
                    final class Meta {
                        public string $name;
                    }

                    function f(Meta $m): int {
                        $x = isset($m->name) ? 1 : 0;
                        return strlen($m->name) + $x;
                    }',
                'assertions' => [],
                'ignored_issues' => ['PropertyNotSetInConstructor', 'MissingConstructor'],
            ],
            'aTypedPropertyThatNoConstructorSetsIsNotNullableInATruthyCoalesce' => [
                'code' => '<?php
                    abstract class Gen {}

                    final class M2 {
                        public Gen $g;

                        public function set(Gen $g): void {
                            $this->g = $g;
                        }
                    }

                    function h(M2 $a, M2 $b): void {
                        if ($b->g ?? null) {
                            $a->set($b->g);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['PropertyNotSetInConstructor', 'MissingConstructor'],
            ],
            'aTypedPropertyThatNoConstructorSetsHasItsTypeAfterACoalesce' => [
                'code' => '<?php
                    final class Meta {
                        public string $name;
                    }

                    $m = new Meta();
                    $label = $m->name ?? "default";
                    $name = $m->name;',
                'assertions' => [
                    '$label===' => 'string',
                    '$name===' => 'string',
                ],
                'ignored_issues' => ['PropertyNotSetInConstructor', 'MissingConstructor'],
            ],
            'emptyOnATypedPropertyThatNoConstructorSetsAllowsAnArrayAppend' => [
                'code' => '<?php
                    final class Builder {
                        public ?array $orders;
                    }

                    function append(Builder $q): void {
                        if (empty($q->orders)) {
                            $q->orders[] = 1;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingConstructor', 'RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'issetOnATypedPropertyDoesNotMakeAFollowingReadPossiblyUndefined' => [
                'code' => '<?php
                    final class Book {
                        public string $name;
                    }

                    function viaVariable(Book $book): string {
                        $has = isset($book->name);

                        if ($has) {
                            return $book->name;
                        }

                        return "";
                    }

                    function viaAssignment(Book $book): string {
                        $set = isset($book->name);
                        $name = $book->name;

                        return $name;
                    }

                    function viaArgument(Book $book): int {
                        $set = isset($book->name);

                        return strlen($book->name);
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingConstructor', 'UnusedVariable'],
                'php_version' => '8.0',
            ],
            'issetOnATypedPropertyAsTheLeftOfAndChecksWhetherItWasSet' => [
                'code' => '<?php
                    final class Book {
                        public string $name;
                    }

                    function hasName(Book $book): bool {
                        return isset($book->name) && $book->name !== "";
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingConstructor'],
                'php_version' => '8.0',
            ],
            'issetOnATypedPropertyThatNoConstructorSetsChecksWhetherItWasSet' => [
                'code' => '<?php
                    final class Book {
                        public string $name;

                        public function __construct() {
                        }
                    }

                    function title(Book $book): string {
                        return $book->name ?? "untitled";
                    }',
                'assertions' => [],
                'ignored_issues' => ['PropertyNotSetInConstructor'],
                'php_version' => '8.0',
            ],
            'issetOnATypedPropertyOfAClassWithoutAConstructorChecksWhetherItWasSet' => [
                'code' => '<?php
                    final class Book {
                        public string $name;
                    }

                    function title(Book $book): string {
                        return $book->name ?? throw new \\Exception("Give the book a name first");
                    }

                    function hasName(Book $book): bool {
                        return isset($book->name);
                    }

                    function describe(Book $book): string {
                        if (!isset($book->name)) {
                            return "nameless";
                        }

                        return $book->name;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingConstructor'],
                'php_version' => '8.0',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'nullOfAValueDoesNotMakeItPossiblyValidForANullableTemplateProperty' => [
                'code' => '<?php
                    final class Foo {}
                    final class Bar {}

                    /** @template T of ?Bar */
                    final class Box {
                        /** @var T */
                        public $v;

                        /** @param T $v */
                        public function __construct($v) {
                            $this->v = $v;
                        }

                        public function set(?Foo $foo): void {
                            $this->v = $foo;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'parentTypeAndInvalidTypeAssignedToProperty' => [
                'code' => '<?php
                    final class Foo {}

                    final class A {
                        public ?Foo $p = null;

                        public function set(object|int $o): void {
                            $this->p = $o;
                        }
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'parentTypeAndInvalidTypeAssignedToStaticProperty' => [
                'code' => '<?php
                    final class Foo {}

                    final class A {
                        public static ?Foo $p = null;

                        public static function set(object|int $o): void {
                            self::$p = $o;
                        }
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'templateOfBoolMakesAValuePossiblyValidForABoolProperty' => [
                'code' => '<?php
                    final class Foo {}
                    final class Bar {}

                    final class A {
                        public Bar|bool $p = false;

                        /**
                         * @template T of bool
                         * @param Foo|T $foo
                         */
                        public function set($foo): void {
                            $this->p = $foo;
                        }
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'falseOfAValueDoesNotMakeItPossiblyValidForABoolProperty' => [
                'code' => '<?php
                    final class Foo {}
                    final class Bar {}

                    final class A {
                        public Bar|bool $p = false;

                        public function set(Foo|false $foo): void {
                            $this->p = $foo;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'writingAPropertyOfAnUndefinedDocblockClassIsADocblockIssue' => [
                'code' => '<?php
                    /** @return mixed */
                    function k() {
                        return null;
                    }

                    /**
                     * @psalm-suppress UndefinedDocblockClass
                     * @var Foobar $o
                     */
                    $o = k();
                    $o->a = 1;',
                'error_message' => 'UndefinedDocblockClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:21 - Cannot set properties of undefined docblock class Foobar',
            ],
            'offsetWriteToAnUninitializedReadonlyArrayProperty' => [
                'code' => '<?php
                    final class Bag
                    {
                        /** @var array<string, string> */
                        public readonly array $values;

                        public function __construct(string $name)
                        {
                            $this->values["name"] = $name;
                        }
                    }',
                'error_message' => 'UninitializedProperty',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'offsetWriteToAnUninitializedPropertyOfAReadonlyClass' => [
                'code' => '<?php
                    final readonly class Bag
                    {
                        /** @var array<string, string> */
                        public array $values;

                        public function __construct(string $name)
                        {
                            $this->values["name"] = $name;
                        }
                    }',
                'error_message' => 'UninitializedProperty',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'compoundOffsetWriteToAnUninitializedArrayProperty' => [
                'code' => '<?php
                    final class Bag
                    {
                        /** @var array<string, string> */
                        private array $values;

                        public function __construct()
                        {
                            $this->values["a"] .= "x";
                        }
                    }',
                'error_message' => 'UninitializedProperty',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'readOfAnUninitializedArrayPropertyBeforeItsOffsetIsWritten' => [
                'code' => '<?php
                    final class Bag
                    {
                        /** @var array<string, string> */
                        private array $values;

                        public function __construct(string $name)
                        {
                            echo count($this->values);
                            $this->values["name"] = $name;
                        }
                    }',
                'error_message' => 'UninitializedProperty',
            ],
            'nameOfPhpParserFiveHasNoParts' => [
                'code' => '<?php
                    namespace PhpParser\\Node {
                        class Name {
                            public string $name = "";
                        }
                    }

                    namespace {
                        function f(PhpParser\\Node\\Name $n): array {
                            return $n->parts;
                        }
                    }',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'finalClassFollowsItsOwnOverrideOfTheTraitMethod' => [
                'code' => '<?php
                    interface H {}

                    trait T {
                        private H $h;

                        public function setH(H $h): void {
                            $this->h = $h;
                        }

                        public function getH(): H {
                            return $this->h;
                        }
                    }

                    final class C {
                        use T;

                        public function setH(H $h): void {}

                        public function __construct(H $h) {
                            $this->setH($h);
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'classThatIsNotFinalDoesNotInitialiseThePropertyThroughAnOverridableTraitMethod' => [
                'code' => '<?php
                    interface ViewHandler {}

                    trait ControllerTrait {
                        private ViewHandler $handler;

                        public function setHandler(ViewHandler $handler): void {
                            $this->handler = $handler;
                        }

                        public function getHandler(): ViewHandler {
                            return $this->handler;
                        }
                    }

                    class Controller {
                        use ControllerTrait;

                        public function __construct(ViewHandler $handler) {
                            $this->setHandler($handler);
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'staticPropertyOfAStaticTypeInANonFinalClassIsNotTheClass' => [
                'code' => '<?php
                    class Config {
                        /** @var static|null */
                        public static $current;

                        public static function use(Config $config): void {
                            self::$current = $config;
                        }
                    }',
                'error_message' => 'PropertyTypeCoercion',
            ],
            'nullsafeOnAnUnreadNonNullPropertyReceiverIsARedundantCondition' => [
                'code' => '<?php
                    final class Item { public bool $flag = true; }
                    final class Holder { public function __construct(public Item $item) {} }

                    /** @var Holder $h */
                    $h = null;
                    $_r = $h->item?->flag;',
                'error_message' => 'RedundantCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:27 - Type Item for $h->item is never null',
                'error_levels' => ['TypeDoesNotContainNull'],
                'php_version' => '8.0',
            ],
            'declaredPropertyOfAStdClassSubclassRejectsAWrongValue' => [
                'code' => '<?php
                    final class Item extends stdClass {
                        public int $count = 0;
                    }

                    $item = new Item();
                    $item->count = "three";',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'declaredPropertyOfAStdClassSubclassHasItsTypeWhenRead' => [
                'code' => '<?php
                    final class Item extends stdClass {
                        public string $label = "";
                    }

                    function takesInt(int $n): int {
                        return $n;
                    }

                    $item = new Item();
                    takesInt($item->label);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'promotedPropertyRedeclaresPropertyOfTheSameClass' => [
                'code' => '<?php
                    final class Order {
                        public int $id;

                        public function __construct(public int $id) {}
                    }',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:64 - Promoted property id clashes',
            ],
            'staticPropertyOfATraitIsSharedWithTheSubclassesOfTheUsingClass' => [
                'code' => '<?php
                    trait Holder {
                        public static ?int $v = null;
                    }

                    class A {
                        use Holder;

                        public static function clear(): void {
                            self::$v = null;
                        }
                    }

                    final class Child extends A {
                        public static function get(): int {
                            Child::$v = 1;
                            A::clear();

                            return Child::$v;
                        }
                    }',
                'error_message' => 'NullableReturnStatement',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'staticPropertyIsKeptAfterACallThatDoesNotAssignIt' => [
                'code' => '<?php
                    final class Widget {
                        private static bool $isSomething = false;

                        public function run(): void {
                            self::$isSomething = false;
                            $this->helper();

                            if (self::$isSomething) {
                                echo "never";
                            }
                        }

                        private function helper(): void {
                            echo "nothing";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:33 - Operand of type false is always falsy',
            ],
            'privateTemplatedPropertyNotInitializedByAFinalParentConstructorChain' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Box {
                        /**
                         * @return Box<int>
                         */
                        public static function factory(): self {
                            /** @var Box<int> $instance */
                            $instance = new self();

                            return $instance;
                        }
                    }

                    abstract class Base {
                        final public function __construct() {
                            $this->init();
                        }

                        abstract protected function init(): void;
                    }

                    final class Child extends Base {
                        /** @var Box<int> */
                        private $box;

                        /** @var Box<int> */
                        private $other;

                        protected function init(): void {
                            $this->box = Box::factory();
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:30:33 - Property Child::$other',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'privateTraitPropertyIsNotVisibleInASubclass' => [
                'code' => '<?php
                    trait IdTrait {
                        private int $id = 1;
                    }

                    class BaseUser {
                        use IdTrait;
                    }

                    class Admin extends BaseUser {
                        public function id(): int {
                            return $this->id;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:36 - Instance property Admin::$id is not defined',
            ],
            'privateTraitPropertyNotInitializedIsStillReportedInTheUsingClass' => [
                'code' => '<?php
                    trait IdTrait {
                        private int $id;

                        public function id(): int {
                            return $this->id;
                        }
                    }

                    class BaseUser {
                        use IdTrait;

                        public function __construct() {}
                    }

                    class Admin extends BaseUser {
                        public function __construct() {
                            parent::__construct();
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:27 - Property BaseUser::$id',
            ],
            'propertyReadThroughCoalesceAndWithoutAGuardIsNotInitialisedByAReturnOfConstructor' => [
                'code' => '<?php
                    final class Canary {
                        public function cancel(): void {}
                    }

                    final class Response {
                        private Canary $canary;

                        public function __construct(bool $pushed) {
                            if ($pushed) {
                                return;
                            }

                            $this->canary = new Canary();
                        }

                        public function canary(): ?Canary {
                            return $this->canary ?? null;
                        }

                        public function close(): void {
                            $this->canary->cancel();
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:40 - Property Response::$canary',
                'error_levels' => ['RedundantPropertyInitializationCheck'],
            ],
            'propertyNotInitializedByAnInterfaceStaticMethodCalledInTheConstructor' => [
                'code' => '<?php
                    interface Limits {
                        public static function minimum(): int;
                    }

                    abstract class Value implements Limits {
                        public int $value;

                        public function __construct() {
                            echo self::minimum();
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'assignPrivatePropertyOfCurrentClassOnChildInstanceChecksItsType' => [
                'code' => '<?php
                    class A {
                        private int $value = 0;

                        public function handle(A $v): void {
                            if ($v instanceof B) {
                                $v->value = "x";
                            }
                        }
                    }

                    class B extends A {}',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'assignPrivatePropertyOfParentOnChildInstanceFromChildContext' => [
                'code' => '<?php
                    class A {
                        private int $value = 0;
                    }

                    class B extends A {}

                    class C extends A {
                        public function handle(B $v): void {
                            $v->value = 1;
                        }
                    }',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'protectedPropertyOfAnUnrelatedClassIsInaccessible' => [
                'code' => '<?php
                    class A
                    {
                        protected string $prop = "";
                    }

                    class B extends A
                    {
                        protected string $prop = "b";
                    }

                    class Unrelated
                    {
                        public function read(B $b): string
                        {
                            return $b->prop;
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'undefinedPropertyAssignment' => [
                'code' => '<?php
                    class A {
                    }

                    (new A)->foo = "cool";',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'staticPropertyFetchViaPlainString' => [
                'code' => '<?php
                    function f(string $class): mixed {
                        return $class::$p;
                    }',
                'error_message' => 'InvalidStringClass',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'privateStaticPropertyFetchViaGetClass' => [
                'code' => '<?php
                    final class A {
                        private static int $p = 1;
                    }

                    function f(A $a): int {
                        $class = get_class($a);
                        return $class::$p;
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'staticPropertyAssignmentViaClassStringWithWrongType' => [
                'code' => '<?php
                    final class A {
                        public static int $p = 1;
                    }

                    /** @param class-string<A> $class */
                    function f(string $class): void {
                        $class::$p = "x";
                    }',
                'error_message' => "A::\$p with declared type 'int' cannot be assigned type",
            ],
            'staticPropertyAssignmentViaGetClassWithWrongType' => [
                'code' => '<?php
                    final class A {
                        public static int $p = 1;
                    }

                    function f(A $a): void {
                        $class = get_class($a);
                        $class::$p = "x";
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'staticPropertyAssignmentViaObjectWithWrongType' => [
                'code' => '<?php
                    final class A {
                        public static int $p = 1;
                    }

                    function f(A $a): void {
                        $a::$p = "x";
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'staticPropertyAssignmentViaLiteralClassStringWithWrongType' => [
                'code' => '<?php
                    final class A {
                        public static int $p = 1;
                    }

                    $class = A::class;
                    $class::$p = "x";',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'privateStaticPropertyAssignmentViaClassString' => [
                'code' => '<?php
                    final class A {
                        private static int $p = 1;
                    }

                    /** @param class-string<A> $class */
                    function f(string $class): void {
                        $class::$p = 2;
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'instancePropertyAssignmentViaClassString' => [
                'code' => '<?php
                    final class A {
                        public int $p = 1;
                    }

                    /** @param class-string<A> $class */
                    function f(string $class): void {
                        $class::$p = 2;
                    }',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'staticPropertyArrayAppendViaClassStringNamesProperty' => [
                'code' => '<?php
                    final class A {
                        /** @var list<int> */
                        public static array $stack = [];
                    }

                    /** @param class-string<A> $class */
                    function f(string $class): void {
                        $class::$stack[] = "x";
                    }',
                'error_message' => "A::\$stack with declared type 'list<int>' cannot be assigned type",
            ],
            'staticPropertyAssignmentViaPlainString' => [
                'code' => '<?php
                    function f(string $class): void {
                        $class::$p = 2;
                    }',
                'error_message' => 'InvalidStringClass',
            ],
            'undefinedStaticPropertyFetchViaStdClassObject' => [
                'code' => '<?php
                    function f(stdClass $o): void {
                        echo (string) $o::$p;
                    }',
                'error_message' => 'UndefinedPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:39 - Static property stdClass::$p is not defined',
            ],
            'undefinedStaticPropertyFetchViaObjectWithMagicGetter' => [
                'code' => '<?php
                    final class A {
                        public function __get(string $name): int {
                            return 1;
                        }
                    }

                    function f(A $a): int {
                        return $a::$p;
                    }',
                'error_message' => 'UndefinedPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:32 - Static property A::$p is not defined',
            ],
            'instancePropertyFetchedStaticallyViaObject' => [
                'code' => '<?php
                    final class A {
                        public int $p = 1;
                    }

                    function f(A $a): int {
                        return $a::$p;
                    }',
                'error_message' => 'UndefinedPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:32 - Static property A::$p is not defined',
            ],
            'instancePropertyAssignedStaticallyViaObject' => [
                'code' => '<?php
                    final class A {
                        public int $p = 1;
                    }

                    function f(A $a): void {
                        $a::$p = 2;
                    }',
                'error_message' => 'UndefinedPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:25 - Static property A::$p is not defined',
            ],
            'undefinedStaticPropertyFetchViaUnionOfObjects' => [
                'code' => '<?php
                    final class A {
                        public static int $p = 1;
                    }

                    final class B {
                        public int $p = 1;
                    }

                    function f(A|B $o): int {
                        return $o::$p;
                    }',
                'error_message' => 'UndefinedPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:32 - Static property B::$p is not defined',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'privateStaticPropertyFetchViaObjectFromOutside' => [
                'code' => '<?php
                    final class A {
                        private static int $p = 1;
                    }

                    function f(A $a): int {
                        return $a::$p;
                    }',
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:32 - Cannot access private property A::$p',
            ],
            'nullsafePropertyFetchLooselyEqualDoesNotAssertNotNull' => [
                'code' => '<?php
                    class Foo {
                        public ?string $source = null;
                    }

                    function test(?Foo $v): Foo {
                        if (0 == $v?->source) {
                            return $v;
                        }

                        return new Foo();
                    }',
                'error_message' => 'NullableReturnStatement',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'undefinedPropertyFetch' => [
                'code' => '<?php
                    class A {
                    }

                    echo (new A)->foo;',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'propertyMapDoesNotAddLowercaseTwinOfCamelCaseProperty' => [
                'code' => '<?php
                    namespace MongoDB\Driver;

                    final class WriteResult {
                        /** @var mixed */
                        public $insertedCount;
                    }

                    function foo(WriteResult $r): void {
                        echo $r->insertedcount;
                    }',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'propertyMapDoesNotAddLowercaseTwinOfStubbedDomProperty' => [
                'code' => '<?php
                    function foo(DOMDocumentType $d): string {
                        return $d->publicid;
                    }',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'undefinedThisPropertyAssignment' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(): void {
                            $this->foo = "cool";
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyAssignment',
            ],
            'undefinedStaticPropertyAssignment' => [
                'code' => '<?php
                    class A {
                        public static function barBar(): void
                        {
                            /** @psalm-suppress UndefinedPropertyFetch */
                            self::$foo = 5;
                        }
                    }',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'undefinedThisPropertyFetch' => [
                'code' => '<?php
                    class A {
                        public function fooFoo(): void {
                            echo $this->foo;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'missingPropertyType' => [
                'code' => '<?php
                    class A {
                        public $foo = null;

                        public function assignToFoo(): void {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property A::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'missingPropertyTypeWithConstructorInit' => [
                'code' => '<?php
                    class A {
                        public $foo;

                        public function __construct() {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property A::$foo does not have a ' .
                    'declared type - consider int',
            ],
            'missingPropertyTypeWithConstructorInitAndNull' => [
                'code' => '<?php
                    class A {
                        public $foo;

                        public function __construct() {
                            $this->foo = 5;
                        }

                        public function makeNull(): void {
                            $this->foo = null;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property A::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'missingPropertyTypeWithConstructorInitAndNullDefault' => [
                'code' => '<?php
                    class A {
                        public $foo = null;

                        public function __construct() {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property A::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'missingPropertyTypeWithConstructorInitConditionallySet' => [
                'code' => '<?php
                    class A {
                        public $foo;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->foo = 5;
                            }
                        }
                    }',
                'error_message' => 'MissingPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32 - Property A::$foo does not have a ' .
                    'declared type - consider int|null',
            ],
            'badAssignment' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function barBar(): void
                        {
                            $this->foo = 5;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'badStaticAssignment' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public static $foo = "a";

                        public static function barBar(): void
                        {
                            self::$foo = 5;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'typeCoercion' => [
                'code' => '<?php
                    class A {
                        /** @var B|null */
                        public $foo;

                        public function barBar(A $a): void
                        {
                            $this->foo = $a;
                        }
                    }

                    class B extends A {}',
                'error_message' => 'PropertyTypeCoercion',
            ],
            'mixedTypeCoercion' => [
                'code' => '<?php
                    class A {
                        /** @var array<int, A> */
                        public $foo = [];

                        /** @param A[] $arr */
                        public function barBar(array $arr): void
                        {
                            $this->foo = $arr;
                        }
                    }',
                'error_message' => 'MixedPropertyTypeCoercion',
            ],
            'staticTypeCoercion' => [
                'code' => '<?php
                    class A {
                        /** @var B|null */
                        public static $foo;

                        public static function barBar(A $a): void
                        {
                            self::$foo = $a;
                        }
                    }

                    class B extends A {}',
                'error_message' => 'PropertyTypeCoercion',
            ],
            'staticMixedTypeCoercion' => [
                'code' => '<?php
                    class A {
                        /** @var array<int, A> */
                        public static $foo = [];

                        /** @param A[] $arr */
                        public static function barBar(array $arr): void
                        {
                            self::$foo = $arr;
                        }
                    }',
                'error_message' => 'MixedPropertyTypeCoercion',
            ],
            'possiblyBadAssignment' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function barBar(): void
                        {
                            $this->foo = rand(0, 1) ? 5 : "hello";
                        }
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
            ],
            'possiblyBadStaticAssignment' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public static $foo = "a";

                        public function barBar(): void
                        {
                            self::$foo = rand(0, 1) ? 5 : "hello";
                        }
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
            ],
            'badAssignmentAsWell' => [
                'code' => '<?php
                    $a = "hello";
                    $a->foo = "bar";',
                'error_message' => 'InvalidPropertyAssignment',
            ],
            'badFetch' => [
                'code' => '<?php
                    $a = "hello";
                    echo $a->foo;',
                'error_message' => 'InvalidPropertyFetch',
            ],
            'possiblyBadFetch' => [
                'code' => '<?php
                    $a = rand(0, 5) > 3 ? "hello" : new stdClass;
                    echo $a->foo;',
                'error_message' => 'PossiblyInvalidPropertyFetch',
            ],
            'mixedPropertyFetch' => [
                'code' => '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    /** @var mixed */
                    $a = (new Foo());

                    echo $a->foo;',
                'error_message' => 'MixedPropertyFetch',
            ],
            'mixedPropertyAssignment' => [
                'code' => '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    /** @var mixed */
                    $a = (new Foo());

                    $a->foo = "hello";',
                'error_message' => 'MixedPropertyAssignment',
            ],
            'possiblyNullablePropertyAssignment' => [
                'code' => '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new Foo() : null;

                    $a->foo = "hello";',
                'error_message' => 'PossiblyNullPropertyAssignment',
            ],
            'nullablePropertyAssignment' => [
                'code' => '<?php
                    $a = null;

                    $a->foo = "hello";',
                'error_message' => 'NullPropertyAssignment',
            ],
            'possiblyNullablePropertyFetch' => [
                'code' => '<?php
                    class Foo {
                        /** @var string */
                        public $foo = "";
                    }

                    $a = rand(0, 10) ? new Foo() : null;

                    echo $a->foo;',
                'error_message' => 'PossiblyNullPropertyFetch',
            ],
            'nullablePropertyFetch' => [
                'code' => '<?php
                    $a = null;

                    echo $a->foo;',
                'error_message' => 'NullPropertyFetch',
            ],
            'badArrayProperty' => [
                'code' => '<?php
                    class A {}

                    class B {}

                    class C {
                        /** @var array<B> */
                        public $bb;
                    }

                    $c = new C;
                    $c->bb = [new A, new B];',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'possiblyBadArrayProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int[] */
                        public $bb = [];
                    }

                    class B {
                        /** @var string[] */
                        public $bb;
                    }

                    $c = rand(0, 1) ? new A : new B;
                    $c->bb = ["hello", "world"];',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
            ],
            'notSetInEmptyConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() { }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:4',
            ],
            'noConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'abstractClassInheritsNoConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;
                    }

                    class B extends A {}',
                'error_message' => 'MissingConstructor',
            ],
            'firstClassCallableOfParentConstructorDoesNotInitializePromotedProperty' => [
                'code' => '<?php
                    class A
                    {
                        public function __construct(public string $name) {}
                    }

                    final class B extends A
                    {
                        public function __construct()
                        {
                            $f = parent::__construct(...);
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:33 - Property B::$name',
                'error_levels' => ['UnusedVariable'],
                'php_version' => '8.1',
            ],
            'firstClassCallableOfAncestorConstructorDoesNotInitializePromotedProperty' => [
                'code' => '<?php
                    class A
                    {
                        public function __construct(public string $name) {}
                    }

                    final class B extends A
                    {
                        public function __construct()
                        {
                            $f = A::__construct(...);
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:33 - Property B::$name',
                'error_levels' => ['UnusedVariable'],
                'php_version' => '8.1',
            ],
            'parentConstructorDoesNotInitializePropertyShadowingAPrivatePromotedOne' => [
                'code' => '<?php
                    class A
                    {
                        public function __construct(private string $name) {}
                    }

                    final class B extends A
                    {
                        private string $name;

                        public function __construct()
                        {
                            parent::__construct("a");
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:40 - Property B::$name',
                'error_levels' => ['UnusedProperty'],
                'php_version' => '8.0',
            ],
            'ancestorConstructorDoesNotInitializePropertyShadowingAPrivatePromotedOne' => [
                'code' => '<?php
                    class A
                    {
                        public function __construct(private string $name) {}
                    }

                    final class B extends A
                    {
                        private string $name;

                        public function __construct()
                        {
                            A::__construct("a");
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:40 - Property B::$name',
                'error_levels' => ['UnusedProperty'],
                'php_version' => '8.0',
            ],
            'inheritedConstructorOfAStubbedBuiltinClass' => [
                'code' => '<?php
                    final class E extends Exception {
                        public string $foo;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'abstractClassInheritsPrivateConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;

                        private function __construct() {
                            $this->foo = "hello";
                        }
                    }

                    class B extends A {}

                    $b = new B();',
                'error_message' => 'InaccessibleMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:13',
            ],
            'classInheritsPrivateConstructorWithImplementedConstructor' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;

                        private function __construct() {
                            $this->foo = "hello";
                        }
                    }

                    class B extends A {
                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:11',
            ],
            'propertyCheckedWithIssetButReadUnconditionallyElsewhereIsReportedAtAReturnOfConstructor' => [
                'code' => '<?php
                    final class Canary {
                        public function cancel(): void {}
                    }

                    final class Response {
                        private Canary $canary;

                        public function __construct(bool $pushed) {
                            if ($pushed) {
                                return;
                            }

                            $this->canary = new Canary();
                        }

                        public function isCancellable(): bool {
                            return isset($this->canary);
                        }

                        public function cancel(): void {
                            $this->canary->cancel();
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'propertyReadUnconditionallyInATraitIsReportedAtAReturnOfConstructor' => [
                'code' => '<?php
                    final class Canary {
                        public function cancel(): void {}
                    }

                    trait CancelsTheCanary {
                        public function cancel(): void {
                            $this->canary->cancel();
                        }
                    }

                    final class Response {
                        use CancelsTheCanary;

                        private Canary $canary;

                        public function __construct(bool $pushed) {
                            if ($pushed) {
                                return;
                            }

                            $this->canary = new Canary();
                        }

                        public function isCancellable(): bool {
                            return isset($this->canary);
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'propertyReadUnconditionallyInATraitUsedByAnAliasIsReportedAtAReturnOfConstructor' => [
                'code' => '<?php
                    final class Canary {
                        public function cancel(): void {}
                    }

                    trait CancelsTheCanary {
                        public function cancel(): void {
                            $this->canary->cancel();
                        }
                    }
                    class_alias(CancelsTheCanary::class, "CancelsTheCanaryAlias");

                    final class Response {
                        use CancelsTheCanaryAlias;

                        private Canary $canary;

                        public function __construct(bool $pushed) {
                            if ($pushed) {
                                return;
                            }

                            $this->canary = new Canary();
                        }

                        public function isCancellable(): bool {
                            return isset($this->canary);
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'propertyReadAsAnArgumentOfACoalesceOperandIsReportedAtAReturnOfConstructor' => [
                'code' => '<?php
                    final class Canary {
                        public function name(): string {
                            return "canary";
                        }
                    }

                    function label(Canary $canary): string {
                        return $canary->name();
                    }

                    final class Response {
                        private Canary $canary;

                        public function __construct(bool $pushed) {
                            if ($pushed) {
                                return;
                            }

                            $this->canary = new Canary();
                        }

                        public function a(): bool {
                            return isset($this->canary);
                        }

                        public function b(): string {
                            return label($this->canary) ?? "none";
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
                'error_levels' => ['RedundantCondition', 'TypeDoesNotContainNull'],
            ],
            'propertyReadAsTheIndexOfAnIssetIsReportedAtAReturnOfConstructor' => [
                'code' => '<?php
                    final class Response {
                        private string $key;

                        /** @var array<string, int> */
                        private array $items = [];

                        public function __construct(bool $pushed) {
                            if ($pushed) {
                                return;
                            }

                            $this->key = "key";
                        }

                        public function a(): bool {
                            return isset($this->key);
                        }

                        public function b(): bool {
                            return isset($this->items[$this->key]);
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'notSetBeforeReturnOfConstructor' => [
                'code' => '<?php
                    final class A {
                        public string $value;

                        public function __construct(string $v) {
                            if ($v === "") {
                                return;
                            }

                            $this->value = $v;
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:3',
            ],
            'returnOfClosureInConstructorDoesNotInitialise' => [
                'code' => '<?php
                    final class A {
                        public string $value;

                        public function __construct(string $v) {
                            $f = function (): void {
                                return;
                            };
                            $f();

                            if ($v !== "") {
                                $this->value = $v;
                            }
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:3',
            ],
            'notSetInAllBranchesOfIf' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->a = 5;
                            }
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:4',
            ],
            'propertySetInProtectedMethod' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            $this->foo();
                        }

                        protected function foo(): void {
                            $this->a = 5;
                        }
                    }

                    class B extends A {
                        protected function foo() : void {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:15',
            ],
            'definedInTraitNotSetInEmptyConstructor' => [
                'code' => '<?php
                    trait A {
                        /** @var string **/
                        public $a;
                    }
                    class B {
                        use A;

                        public function __construct() {
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:6',
            ],
            'propertySetInPrivateMethodWithIf' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->foo();
                            }
                        }

                        private function foo(): void {
                            $this->a = 5;
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:4',
            ],
            'privatePropertySameNameNotSetInConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        private $b;

                        public function __construct() {
                            $this->b = "foo";
                        }
                    }

                    class B extends A {
                        /** @var string */
                        private $b;
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:13',
            ],
            'privateMethodCalledInParentConstructor' => [
                'code' => '<?php
                    class C extends B {}

                    abstract class B extends A {
                        /** @var string */
                        private $b;

                        /** @var string */
                        protected $c;
                    }

                    class A {
                        public function __construct() {
                            $this->publicMethod();
                        }

                        public function publicMethod() : void {
                            $this->privateMethod();
                        }

                        private function privateMethod() : void {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:2',
            ],
            'privatePropertySetInParentConstructorReversedOrder' => [
                'code' => '<?php
                    class B extends A {
                        /** @var string */
                        private $b;
                    }

                    class A {
                        public function __construct() {
                            if ($this instanceof B) {
                                $this->b = "foo";
                            }
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'privatePropertySetInParentConstructor' => [
                'code' => '<?php
                    class A {
                        public function __construct() {
                            if ($this instanceof B) {
                                $this->b = "foo";
                            }
                        }
                    }

                    class B extends A {
                        /** @var string */
                        private $b;
                    }

                    ',
                'error_message' => 'InaccessibleProperty',
            ],
            'undefinedPropertyClass' => [
                'code' => '<?php
                    class A {
                        /** @var B */
                        public $foo;
                    }',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'abstractClassWithNoConstructorButChild' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public $foo;
                    }

                    class B extends A {
                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:7',
            ],
            'badAssignmentToUndefinedVars' => [
                'code' => '<?php
                    $x->$y = 4;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'echoUndefinedPropertyFetch' => [
                'code' => '<?php
                    echo $x->$y;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'toStringPropertyAssignment' => [
                'code' => '<?php
                    class A {
                      /** @var ?string */
                      public $foo;
                    }

                    class B {
                      public function __toString() {
                        return "bar";
                      }
                    }

                    $a = new A();
                    $a->foo = new B;',
                'error_message' => 'ImplicitToStringCast',
            ],
            'noInfiniteLoop' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function __construct() {
                            $this->doThing();
                        }

                        private function doThing(): void {
                            if (rand(0, 1)) {
                                $this->doOtherThing();
                            }
                        }

                        private function doOtherThing(): void {
                            if (rand(0, 1)) {
                                $this->doThing();
                            }
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:4',
            ],
            'invalidPropertyDefault' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $a = "hello";
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'prohibitMixedAssignmentNormally' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var string
                         */
                        private $mixed;

                        /**
                         * @param mixed $value
                         */
                        public function setMixed($value): void
                        {
                            $this->mixed = $value;
                        }
                    }',
                'error_message' => 'MixedAssignment',
            ],
            'assertPropertyTypeHasImpossibleType' => [
                'code' => '<?php
                    class A {
                        /** @var ?B */
                        public $foo;
                    }
                    class B {}
                    $a = new A();
                    if (is_string($a->foo)) {}',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'impossiblePropertyCheck' => [
                'code' => '<?php
                    class Bar {}
                    class Foo {
                        /** @var Bar */
                        private $bar;

                        public function __construct() {
                            $this->bar = new Bar();
                        }

                        public function getBar(): void {
                            if (!$this->bar) {}
                        }
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'staticPropertyOfStaticTypeMethodCallWithUndefinedMethod' => [
                'code' => '<?php
                    class A {
                        /** @var static|null */
                        public $instance;

                        public function foo() : void {
                            if ($this->instance) {
                                $this->instance->bar();
                            }
                        }
                    }

                    class B extends A {
                        public function bar() : void {}
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'misnamedPropertyByVariable' => [
                'code' => '<?php
                    class B {
                        /** @var string|null */
                        public $foo;

                        public function bar(string $var_name) : ?string {
                            if ($var_name === "bar") {
                                return $this->$var_name;
                            }

                            return null;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'inheritDocPropertyTypesIncorrectAssignmentToInstanceProperty' => [
                'code' => '<?php
                    class X {
                        /**
                         * @var string|null
                         */
                        public $a;
                    }

                    class Y extends X {
                        public $a = "foo";
                    }

                    (new Y)->a = 5;
                    echo (new Y)->a;
                    Y::$b = "bar";
                    echo Y::$b;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'inheritDocPropertyTypesIncorrectAssignmentToStaticProperty' => [
                'code' => '<?php
                    class X {
                        /**
                         * @var string|null
                         */
                        public static $b;
                    }

                    class Y extends X {
                        public static $b = "foo";
                    }

                    Y::$b = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'uninitializedProperty' => [
                'code' => '<?php
                    class A {
                        /** @var string */
                        public $foo;

                        public function __construct() {
                            echo strlen($this->foo);
                            $this->foo = "foo";
                        }
                    }',
                'error_message' => 'UninitializedProperty',
            ],
            'uninitializedPropertyWithoutType' => [
                'code' => '<?php
                    class A {
                        public $foo;

                        public function __construct() {
                            echo strlen($this->foo);
                            $this->foo = "foo";
                        }
                    }',
                'error_message' => 'UninitializedProperty',
                'error_levels' => ['MixedArgument'],
            ],
            'uninitializedObjectProperty' => [
                'code' => '<?php
                    class Foo {
                        /** @var int */
                        public $bar = 5;
                    }
                    function takesInt(int $i) : void {}
                    class A {
                        /** @var Foo */
                        public $foo;

                        public function __construct(Foo $foo) {
                            takesInt($this->foo->bar);
                            $this->foo = $foo;
                        }
                    }',
                'error_message' => 'UninitializedProperty',
            ],
            'possiblyNullArg' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        public $foo;

                        public function __construct() {
                            echo strlen($this->foo);
                            $this->foo = "foo";
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'noCrashOnMagicCall' => [
                'code' => '<?php
                    /** @method void setA() */
                    class A {
                        /** @var string */
                        private $a;

                        public function __construct() {
                            $this->setA();
                        }

                        public function __call(string $var, array $args) {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:5',
            ],
            'reportGoodLocationForPropertyError' => [
                'code' => '<?php
                    class C {
                        /** @var string */
                        public $s;

                        public function __construct() {
                            $this->setS();
                        }

                        public function setS() : void {
                            $this->s = "hello";
                        }
                    }

                    class D extends C {
                        public function setS() : void {
                            // nothing happens here
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:15',
            ],
            'noCrashWhenUnsettingPropertyWithoutDefaultInConstructor' => [
                'code' => '<?php
                    class A {
                        /** @var bool */
                        private $foo;

                        public function __construct() {
                            unset($this->foo);
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'promotedPropertyNotSetInExtendedConstructor' => [
                'code' => '<?php
                    class A
                    {
                        public function __construct(
                            public string $name,
                        ) {}
                    }

                    class B extends A
                    {
                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'nullableTypedPropertyNoConstructor' => [
                'code' => '<?php
                    class A {
                        private ?bool $foo;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'nullableTypedPropertyEmptyConstructor' => [
                'code' => '<?php
                    class A {
                        private ?bool $foo;

                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'nullableTypedPropertyUseBeforeInitialised' => [
                'code' => '<?php
                    class A {
                        private ?bool $foo;

                        public function __construct() {
                            echo $this->foo;
                        }
                    }',
                'error_message' => 'UninitializedProperty',
            ],
            'nullableTypedPropertyNoConstructorWithDocblock' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private ?bool $foo;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'nullableTypedPropertyEmptyConstructorWithDocblock' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private ?bool $foo;

                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'nullableTypedPropertyUseBeforeInitialisedWithDocblock' => [
                'code' => '<?php
                    class A {
                        /** @var ?bool */
                        private ?bool $foo;

                        public function __construct() {
                            echo $this->foo;
                        }
                    }',
                'error_message' => 'UninitializedProperty',
            ],
            'badStaticPropertyDefault' => [
                'code' => '<?php
                    class TestStatic {
                        /**
                         * @var array<string, bool>
                         */
                        public static $test = ["string-key" => 1];
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'addNullToMixedAfterNullablePropertyFetch' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var mixed
                         */
                        public $foo;
                    }

                    function takesString(string $s) : void {}

                    function takesA(?A $a) : void {
                        /**
                         * @psalm-suppress PossiblyNullPropertyFetch
                         * @psalm-suppress MixedArgument
                         */
                        takesString($a->foo);
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'catchBadArrayStaticProperty' => [
                'code' => '<?php
                    namespace Bar;

                    class Foo {}
                    class A {
                        /** @var array<string, object> */
                        public array $map = [];

                        /**
                         * @param string $class
                         */
                        public function get(string $class) : void {
                            $this->map[$class] = 5;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'preventArrayPushOnArrayValue' => [
                'code' => '<?php
                    class MyClass {
                        /**
                         * @var int[]
                         */
                        private $prop = [];

                        /**
                         * @return void
                         */
                        public function foo() {
                            array_push($this->prop, "bad");
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'overriddenConstructorCalledMethod' => [
                'code' => '<?php
                    class ParentClass {
                        private string $prop;

                        public function __construct() {
                            $this->init();
                        }

                        public function init(): void {
                            $this->prop = "zxc";
                        }
                    }

                    class ChildClass extends ParentClass {
                        public function init(): void {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'backedPropertyWithGetHookNotSetInConstructor' => [
                'code' => '<?php
                    final class A {
                        public int $prop { get => $this->prop * 2; }

                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'backedPropertyWithShortSetHookNotSetInConstructor' => [
                'code' => '<?php
                    final class A {
                        public int $prop { set => $value * 2; }

                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'propertyWithSameNameUndefined' => [
                'code' => '<?php
                    class Foo {}

                    class Bar {
                        public int $id = 3;

                        public function __construct(Foo $model) {
                            echo $model->id;
                        }
                    }',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'missingPropertyTypeWithDocblock' => [
                'code' => '<?php
                    class C {
                        /**
                         * @varr int
                         */
                        public $var;
                    }',
                'error_message' => 'MissingPropertyType',
            ],
            'promotedPrivateProperty' => [
                'code' => '<?php
                    class A {
                        public function __construct(private int $foo = 5) {}
                    }

                    echo (new A)->foo;',
                'error_message' => 'InaccessibleProperty',
            ],
            'undefinedClassInDocblockOfTypedPropertyIsReportedAtTheDocblock' => [
                'code' => '<?php
                    class A {
                        /** @var class-string<Foobar> */
                        public string $s = "";
                    }',
                'error_message' => 'UndefinedDocblockClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:34 - Docblock-defined class, interface or enum named Foobar does not exist',
            ],
            'mismatchingDocblockOfTypedPropertyIsReportedAtTheDocblock' => [
                'code' => '<?php
                    class A {
                        /** @var array */
                        public string $s = [];
                    }',
                'error_message' => 'MismatchingDocblockPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:34 - Property A::$s has wrong type',
            ],
            'overwritePropertyType' => [
                'code' => '<?php
                    class A {
                        /** @var array */
                        public string $s = [];
                    }',
                'error_message' => 'MismatchingDocblockPropertyType',
            ],
            'mismatchingDocblockPropertyTypeShowsExactType' => [
                'code' => '<?php
                    class A {
                        /** @var numeric-string */
                        public int $s;
                    }',
                'error_message' => 'MismatchingDocblockPropertyType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:34'
                    . ' - Property A::$s has wrong type \'numeric-string\', should be \'int',
            ],
            'possiblyNullOnFunctionCallNotCoalesced' => [
                'code' => '<?php
                    function getC() : ?C {
                        return rand(0, 1) ? new C() : null;
                    }

                    function foo() : void {
                        echo getC()->id;
                    }

                    class C {
                        public int $id = 1;
                    }',
                'error_message' => 'PossiblyNullPropertyFetch',
            ],
            'noCrashWhenCallingMagicSet' => [
                'code' => '<?php
                    class A {
                        public function __set(string $s, mixed $value) : void {}
                    }

                    (new A)->__set("foo");',
                'error_message' => 'TooFewArguments',
            ],
            'noCrashWhenCallingMagicGet' => [
                'code' => '<?php
                    class A {
                        public function __get(string $s) : mixed {}
                    }

                    (new A)->__get();',
                'error_message' => 'TooFewArguments',
            ],
            'staticReadOfNonStaticProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $prop = 1;
                    }
                    echo A::$prop;
                ',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'staticWriteToNonStaticProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public $prop = 1;
                    }
                    A::$prop = 42;
                ',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'nonStaticReadOfStaticProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public static $prop = 1;
                    }
                    echo (new A)->prop;
                ',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'nonStaticWriteToStaticProperty' => [
                'code' => '<?php
                    class A {
                        /** @var int */
                        public static $prop = 1;
                    }
                    (new A)->prop = 42;
                ',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'nativeMixedPropertyWithNoConstructor' => [
                'code' => <<< 'PHP'
                    <?php
                    class A {
                        public mixed $foo;
                    }
                PHP,
                'error_message' => 'MissingConstructor',
            ],
            'nullsafePropertyIdenticalToNullDoesNotNarrowTheReceiverInTheBranch' => [
                'code' => '<?php
                    final class B {
                        public ?string $value = null;
                        public function m(): int { return 1; }
                    }

                    final class A {
                        public ?B $b = null;
                    }

                    function t(A $a): void {
                        if ($a->b?->value === null) {
                            echo $a->b->value;
                        }
                    }',
                'error_message' => 'PossiblyNullPropertyFetch',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'nullsafePropertyLooselyEqualToNullDoesNotNarrowTheReceiverInTheBranch' => [
                'code' => '<?php
                    final class B {
                        public ?string $value = null;
                        public function m(): int { return 1; }
                    }

                    final class A {
                        public ?B $b = null;
                    }

                    function t(A $a): void {
                        if ($a->b?->value == null) {
                            echo $a->b->value;
                        }
                    }',
                'error_message' => 'PossiblyNullPropertyFetch',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'nullsafePropertyNotIssetDoesNotNarrowTheReceiverInTheBranch' => [
                'code' => '<?php
                    final class B {
                        public ?string $value = null;
                        public function m(): int { return 1; }
                    }

                    final class A {
                        public ?B $b = null;
                    }

                    function t(A $a): void {
                        if (!isset($a->b?->value)) {
                            echo $a->b->value;
                        }
                    }',
                'error_message' => 'PossiblyNullPropertyFetch',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'mixedObjectOfPropertyAssignmentIsNamed' => [
                'code' => '<?php
                    function f(): mixed { return 1; }
                    f()->name = "x";',
                'error_message' => 'MixedPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - f() of type mixed cannot be assigned to',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'arrayOffsetObjectOfPropertyAssignmentIsNamed' => [
                'code' => '<?php
                    function f(array $metadata, int $i, string $column): void {
                        $metadata[$i]->name = $column;
                    }',
                'error_message' => 'MixedPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25 - $metadata[$i] of type mixed cannot be assigned to',
            ],
            'closureCallObjectOfPropertyAssignmentIsNamedOnOneLine' => [
                'code' => '<?php
                    (function () {
                        return null;
                    })()->name = "x";',
                'error_message' => 'NullPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:21 - (function () { return null; })() of type null cannot be assigned to',
            ],
            'nullObjectOfPropertyAssignmentIsNamed' => [
                'code' => '<?php
                    function f(): null { return null; }
                    f()->name = "x";',
                'error_message' => 'NullPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - f() of type null cannot be assigned to',
            ],
            'nullableObjectOfPropertyAssignmentIsNamed' => [
                'code' => '<?php
                    function f(): ?stdClass { return null; }
                    f()->name = "x";',
                'error_message' => 'PossiblyNullPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - f() with possibly null type',
            ],
            'partialApplicationInTheObjectOfPropertyAssignmentIsNamed' => [
                'code' => '<?php
                    function wrap(Closure $c): ?stdClass { return rand(0, 1) ? null : new stdClass; }
                    function add(int $a, int $b): int { return $a + $b; }
                    wrap(add(1, ?))->x = 1;',
                'error_message' => 'PossiblyNullPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:21 - wrap(add(1, ?)) with possibly null type',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'nonObjectOfPropertyAssignmentIsNamed' => [
                'code' => '<?php
                    function f(): int { return 1; }
                    f()->name = "x";',
                'error_message' => 'InvalidPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - f() with non-object type',
            ],
            'possiblyNonObjectOfPropertyAssignmentIsNamed' => [
                'code' => '<?php
                    function f(): int|stdClass { return 1; }
                    f()->name = "x";',
                'error_message' => 'PossiblyInvalidPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - f() with possible non-object type',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'nullObjectOfPropertyFetchIsNamed' => [
                'code' => '<?php
                    function f(): null { return null; }
                    echo f()->name;',
                'error_message' => 'NullPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot get property on null variable f',
            ],
            'possiblyNullObjectOfPropertyFetchIsNamed' => [
                'code' => '<?php
                    function f(): ?stdClass { return null; }
                    echo f()->name;',
                'error_message' => 'PossiblyNullPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot get property on possibly null variable f() of type',
            ],
            'invalidObjectOfMethodCallIsNamed' => [
                'code' => '<?php
                    function f(): int { return 1; }
                    f()->m();',
                'error_message' => 'InvalidMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot call method on int variable f',
            ],
            'possiblyInvalidObjectOfMethodCallIsNamed' => [
                'code' => '<?php
                    function f(): int|stdClass { return 1; }
                    f()->m();',
                'error_message' => 'PossiblyInvalidMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot call method on possible int variable f',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'notSetWhenTheChildOverridesTheMethodOfAParentClass' => [
                'code' => '<?php
                    abstract class Event {
                        protected string $uniqueId;

                        protected function generateUniqueId(): void {
                            $this->uniqueId = "test";
                        }
                    }

                    final class SomeEvent extends Event {
                        protected function generateUniqueId(): void {}

                        public function __construct() {
                            $this->generateUniqueId();
                        }
                    }',
                'error_message' => 'Property SomeEvent::$uniqueId is not defined in constructor',
            ],
            'notSetInFinalMethodOfAParentClassThatSetsAnotherProperty' => [
                'code' => '<?php
                    abstract class Event {
                        protected string $uniqueId;

                        protected string $when;

                        final protected function generateUniqueId(): void {
                            $this->uniqueId = "test";
                        }
                    }

                    final class SomeEvent extends Event {
                        public function __construct() {
                            $this->generateUniqueId();
                        }
                    }',
                'error_message' => 'Property SomeEvent::$when is not defined in constructor',
            ],
            'notSetByAParentConstructorThatTheChildDoesNotCall' => [
                'code' => '<?php
                    trait CreatedAt {
                        protected DateTimeImmutable $createdAt;

                        private function onCreated(): void {
                            $this->createdAt = new DateTimeImmutable();
                        }
                    }

                    class Foo {
                        use CreatedAt;

                        public function __construct() {
                            $this->onCreated();
                        }
                    }

                    class Bar extends Foo {
                        public function __construct() {}
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:18',
            ],
            'notSetByATraitMethodCalledByItsAliasWhileTheClassOverridesTheName' => [
                'code' => '<?php
                    trait T {
                        protected function init(): void {}
                    }

                    class A {
                        use T { init as alias; }

                        protected int $prop;

                        protected function init(): void {
                            $this->prop = 1;
                        }

                        public function __construct() {
                            $this->alias();
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'notSetByAnInheritedTraitMethodCalledByItsAliasWhileTheParentOverridesTheName' => [
                'code' => '<?php
                    trait T {
                        protected function init(): void {}
                    }

                    class P {
                        use T { init as alias; }

                        protected int $prop;

                        public function __construct() {
                            $this->init();
                        }

                        protected function init(): void {
                            $this->prop = 1;
                        }
                    }

                    class B extends P {
                        public function __construct() {
                            $this->alias();
                        }
                    }',
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:20:27 - Property B::$prop is not defined in constructor of B',
            ],
            'propertyMapWrongCasePropertyIsUndefined' => [
                'code' => '<?php
                    function foo(DOMDocumentType $t): string {
                        return $t->publicid;
                    }',
                'error_message' => 'UndefinedPropertyFetch',
                'error_levels' => [],
                'php_version' => '8.4',
            ],
            'issetOnATypedPropertyThatAParentConstructorSetsIsRedundantInTheChild' => [
                'code' => '<?php
                    class Base {
                        public string $name;

                        public function __construct() {
                            $this->name = "x";
                        }
                    }

                    final class Child extends Base {
                        public function __construct() {
                            parent::__construct();
                        }
                    }

                    function hasName(Child $child): bool {
                        if (isset($child->name)) {
                            return true;
                        }

                        return false;
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'nullCoalescingOnATypedPropertyThatNoConstructorSetsKeepsTheNullableConcatenation' => [
                'code' => '<?php
                    final class Box {
                        public ?string $tag;

                        public function __construct() {
                        }
                    }

                    function tag(Box $box): void {
                        $t = $box->tag ?? null;
                        $box->tag .= "x";
                    }',
                'error_message' => 'PossiblyNullOperand',
                'error_levels' => ['MissingConstructor', 'PropertyNotSetInConstructor'],
                'php_version' => '8.0',
            ],
            'issetOnADocblockTypedPropertyIsRedundant' => [
                'code' => '<?php
                    final class Book {
                        /** @var string */
                        public $name = "x";
                    }

                    function hasName(Book $book): bool {
                        if (isset($book->name)) {
                            return true;
                        }

                        return false;
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'issetOnATypedPropertyWithADefaultIsRedundant' => [
                'code' => '<?php
                    final class Book {
                        public string $name = "x";
                    }

                    function hasName(Book $book): bool {
                        if (isset($book->name)) {
                            return true;
                        }

                        return false;
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'issetOnAPromotedPropertyIsRedundant' => [
                'code' => '<?php
                    final class Book {
                        public function __construct(public string $name) {
                        }
                    }

                    function hasName(Book $book): bool {
                        if (isset($book->name)) {
                            return true;
                        }

                        return false;
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'issetOnATypedPropertyThatAConstructorSetsIsRedundant' => [
                'code' => '<?php
                    final class Book {
                        public string $name;

                        public function __construct() {
                            $this->name = "x";
                        }
                    }

                    function hasName(Book $book): bool {
                        if (isset($book->name)) {
                            return true;
                        }

                        return false;
                    }',
                'error_message' => 'RedundantCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:29 - Type string for $book->name is always isset',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'nullAssignedToAStaticProperty' => [
                'code' => '<?php
                    final class A {
                        public static int $i = 0;
                    }

                    A::$i = null;',
                'error_message' => 'PossiblyNullPropertyAssignmentValue',
            ],
            'possiblyNullAssignedToAStaticProperty' => [
                'code' => '<?php
                    final class A {
                        public static int $i = 0;

                        public static function set(?int $i): void {
                            self::$i = $i;
                        }
                    }',
                'error_message' => 'PossiblyNullPropertyAssignmentValue',
            ],
            'possiblyFalseAssignedToAStaticProperty' => [
                'code' => '<?php
                    final class A {
                        public static int $i = 0;

                        public static function set(int|false $i): void {
                            self::$i = $i;
                        }
                    }',
                'error_message' => 'PossiblyFalsePropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'nullOfAValueDoesNotMakeItPossiblyValidForANullableStaticProperty' => [
                'code' => '<?php
                    final class A {
                        public static ?int $i = null;

                        public static function set(?string $s): void {
                            self::$i = $s;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'possiblyFalseAssignedToAPropertyOfTrueOrInt' => [
                'code' => '<?php
                    final class A {
                        public true|int $p = 1;

                        public function set(int|false $i): void {
                            $this->p = $i;
                        }
                    }',
                'error_message' => 'PossiblyFalsePropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'possiblyFalseAssignedToAStaticPropertyOfTrueOrInt' => [
                'code' => '<?php
                    final class A {
                        public static true|int $p = 1;

                        public static function set(int|false $i): void {
                            self::$p = $i;
                        }
                    }',
                'error_message' => 'PossiblyFalsePropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'stringableAssignedToAStringPropertyUnderStrictTypes' => [
                'code' => '<?php
                    declare(strict_types=1);

                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    final class A {
                        public string $s = "";

                        public function set(S $s): void {
                            $this->s = $s;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'stringableAssignedToAStaticStringPropertyUnderStrictTypes' => [
                'code' => '<?php
                    declare(strict_types=1);

                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    final class A {
                        public static string $s = "";

                        public static function set(S $s): void {
                            self::$s = $s;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'stringableAssignedToANonEmptyStringPropertyUnderStrictTypes' => [
                'code' => '<?php
                    declare(strict_types=1);

                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    final class A {
                        /** @var non-empty-string */
                        public string $s = "a";

                        public function set(S $s): void {
                            $this->s = $s;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'stringableAssignedToANonEmptyStringStaticPropertyUnderStrictTypes' => [
                'code' => '<?php
                    declare(strict_types=1);

                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    final class A {
                        /** @var non-empty-string */
                        public static string $s = "a";

                        public static function set(S $s): void {
                            self::$s = $s;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'stringableAssignedToADocblockStringPropertyUnderStrictTypes' => [
                'code' => '<?php
                    declare(strict_types=1);

                    final class S {
                        public function __toString(): string {
                            return "s";
                        }
                    }

                    final class A {
                        /** @var string */
                        public $s = "";

                        public function set(S $s): void {
                            $this->s = $s;
                        }
                    }',
                'error_message' => 'ImplicitToStringCast',
            ],
        ];
    }
}
