<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\InvalidCodeAnalysisWithIssuesTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;

use const DIRECTORY_SEPARATOR;

final class MagicPropertyTest extends TestCase
{
    use InvalidCodeAnalysisWithIssuesTestTrait;
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testNotSetChildOfASimpleXmlElementWithoutItsStubIsStillReadThroughGet(): void
    {
        $config = Config::getInstance();
        self::assertInstanceOf(TestConfig::class, $config);
        $config->disableExtension('simplexml');

        $this->addFile(
            'somefile.php',
            '<?php
                function f(SimpleXMLElement $xml): string {
                    if (!isset($xml->title)) {
                        return $xml->title->getName();
                    }

                    return "";
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPhpDocPropertyWithoutGet(): void
    {
        Config::getInstance()->use_phpdoc_property_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @property string $hello
                 */
                class Child {}

                $child = new Child();

                $a = $child->hello;',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPhpDocPropertyWithoutMagicGetterIsExpanded(): void
    {
        Config::getInstance()->use_phpdoc_property_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                enum Foo: string {
                    case Bar = "bar";
                    case Lee = "lee";
                }

                /** @property value-of<Foo> $attr */
                class Holder {}

                $holder = new Holder();
                $attr = $holder->attr;
                /** @psalm-check-type-exact $attr = \'bar\'|\'lee\' */',
        );

        $this->project_analyzer->setPhpVersion('8.1', 'tests');
        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @param list<string> $expected_issues
     */
    #[DataProvider('providerPseudoPropertyOnUnionWithNonObject')]
    public function testPseudoPropertyOnUnionWithNonObject(
        string $code,
        array $expected_issues,
        bool $use_phpdoc_property_without_magic_or_parent = false,
    ): void {
        $this->testConfig->throw_exception = false;
        Config::getInstance()->use_phpdoc_property_without_magic_or_parent
            = $use_phpdoc_property_without_magic_or_parent;

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, $code);
        $this->project_analyzer->setPhpVersion('8.0', 'tests');
        $this->analyzeFile($file_path, new Context());

        $issues = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            $issues[] = $issue->line_from . ':' . $issue->type;
        }

        $this->assertSame($expected_issues, $issues);
    }

    /**
     * @return iterable<string, array{0: string, 1: list<string>, 2?: bool}>
     * @psalm-pure
     */
    public static function providerPseudoPropertyOnUnionWithNonObject(): iterable
    {
        yield 'magicGetterOfUnsealedProperties' => [
            '<?php
                /** @psalm-no-seal-properties */
                final class A {
                    public function __get(string $n): string { return ""; }
                }
                function f(A|int $a): string { return $a->foo; }',
            ['6:PossiblyInvalidPropertyFetch'],
        ];
        yield 'magicGetterOfSealedPropertiesStaysInvalid' => [
            '<?php
                final class A {
                    public function __get(string $n): string { return ""; }
                }
                function f(A|int $a): mixed { return $a->foo; }',
            ['5:UndefinedMagicPropertyFetch', '5:InvalidPropertyFetch'],
        ];
        yield 'phpDocProperty' => [
            '<?php
                /** @property string $foo */
                final class A {
                    public function __get(string $n): mixed { return ""; }
                }
                function f(A|int $a): string { return $a->foo; }',
            ['6:PossiblyInvalidPropertyFetch'],
        ];
        yield 'phpDocPropertyRead' => [
            '<?php
                /** @property-read string $foo */
                final class A {
                    public function __get(string $n): mixed { return ""; }
                }
                function f(A|int $a): string { return $a->foo; }',
            ['6:PossiblyInvalidPropertyFetch'],
        ];
        yield 'magicGetterOfAnInaccessibleProperty' => [
            '<?php
                /** @psalm-no-seal-properties */
                final class A {
                    private string $foo = "";
                    public function __get(string $n): string { return $this->foo; }
                }
                function f(A|int $a): string { return $a->foo; }',
            ['7:PossiblyInvalidPropertyFetch'],
        ];
        yield 'phpDocPropertyWithoutMagicGetter' => [
            '<?php
                /** @property string $foo */
                final class A {}
                function f(A|int $a): string { return $a->foo; }',
            ['4:PossiblyInvalidPropertyFetch'],
            true,
        ];
        yield 'undeclaredPropertyOfSealedPropertiesStaysInvalid' => [
            '<?php
                /**
                 * @property string $foo
                 * @psalm-seal-properties
                 */
                final class A {
                    public function __get(string $n): mixed { return ""; }
                }
                function f(A|int $a): mixed { return $a->bar; }',
            ['9:UndefinedMagicPropertyFetch', '9:InvalidPropertyFetch'],
        ];
        yield 'magicSetterOfUnsealedProperties' => [
            '<?php
                /** @psalm-no-seal-properties */
                final class A {
                    public function __set(string $n, mixed $v): void {}
                }
                function f(A|int $a): void { $a->foo = "x"; }',
            ['6:PossiblyInvalidPropertyAssignment'],
        ];
        yield 'phpDocPropertyReadOfFalsableReceiver' => [
            '<?php
                /** @property-read string $foo */
                final class A {
                    public function __get(string $n): mixed { return ""; }
                }
                function f(A|false $a): string { return $a->foo; }',
            ['6:PossiblyInvalidPropertyFetch'],
        ];
        yield 'magicGetterOfIntersectionMember' => [
            '<?php
                /** @psalm-no-seal-properties */
                class U {
                    public function __get(string $n): bool { return true; }
                }
                /** @property int $foo */
                class N {
                    public function __get(string $n): mixed { return 0; }
                }
                /** @param U&N $x */
                function f(object $x): int {
                    $y = $x->foo;
                    /** @psalm-check-type-exact $y = int */
                    return $y;
                }',
            [],
        ];
        yield 'magicSetterOnUnionWithRealProperty' => [
            '<?php
                /** @psalm-no-seal-properties */
                final class A {
                    public function __set(string $n, mixed $v): void {}
                }
                final class B {
                    public string $foo = "";
                }
                function f(A|B $a): void { $a->foo = "x"; }',
            [],
        ];
        yield 'magicSetterOfFalsableReceiver' => [
            '<?php
                /** @psalm-no-seal-properties */
                final class A {
                    public function __set(string $n, mixed $v): void {}
                }
                function f(A|false $a): void { $a->foo = "x"; }',
            ['6:PossiblyInvalidPropertyAssignment'],
        ];
        yield 'magicSetterOfFreshReceiverInPureFunction' => [
            '<?php
                /** @psalm-no-seal-properties */
                final class A {
                    public int $n = 0;
                    /** @psalm-external-mutation-free */
                    public function __set(string $k, mixed $v): void { $this->n = 1; }
                }
                /** @psalm-pure */
                function f(): int { $a = new A(); $a->foo = 1; return 1; }',
            [],
        ];
        yield 'magicGetterOfFreshReceiverInPureFunction' => [
            '<?php
                /** @psalm-no-seal-properties */
                final class A {
                    public int $n = 0;
                    /** @psalm-mutation-free */
                    public function __get(string $k): int { return $this->n; }
                }
                /** @psalm-pure */
                function f(): int { $a = new A(); return $a->foo; }',
            [],
        ];
        yield 'magicSetterOfNullableReceiver' => [
            '<?php
                /** @psalm-no-seal-properties */
                final class A {
                    public function __set(string $n, mixed $v): void {}
                }
                function f(?A $a): void { $a->foo = "x"; }',
            ['6:PossiblyNullPropertyAssignment'],
        ];
        yield 'phpDocPropertyAssignment' => [
            '<?php
                /** @property string $foo */
                final class A {
                    public function __set(string $n, mixed $v): void {}
                }
                function f(A|int $a): void { $a->foo = "x"; }',
            ['6:PossiblyInvalidPropertyAssignment'],
        ];
    }

    public function testPhpDocPropertyAssignmentWithoutSetInheritedFromParent(): void
    {
        Config::getInstance()->use_phpdoc_property_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @property string $b */
                abstract class A {
                    public string $a = "";
                }

                class B extends A {
                    public function init(): void {
                        $this->b = "init";
                    }
                }

                $b = new B();
                $b->a = "a";
                $b->b = "b";',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPhpDocPropertyAssignmentWithoutSetChecksTheType(): void
    {
        Config::getInstance()->use_phpdoc_property_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @property string $b */
                class A {}

                $a = new A();
                $a->b = 1;',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('InvalidPropertyAssignmentValue');
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPhpDocPropertyReadIsNotAssignableWithoutSet(): void
    {
        Config::getInstance()->use_phpdoc_property_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @property-read string $b */
                class A {}

                $a = new A();
                $a->b = "b";',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UndefinedPropertyAssignment');
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPhpDocPropertyAssignmentWithoutSetNeedsTheConfigOption(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                /** @property string $b */
                class A {}

                $a = new A();
                $a->b = "b";',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UndefinedPropertyAssignment');
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPhpDocPropertyDoesNotMakeAnInaccessiblePropertyAssignable(): void
    {
        Config::getInstance()->use_phpdoc_property_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @property string $p */
                class Priv {
                    private string $p = "";
                }

                $x = new Priv();
                $x->p = "a";',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('InaccessibleProperty - somefile.php:8:17 - Cannot access private property Priv::$p');
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPhpDocPropertyAssignmentOfAClassAllowingDynamicPropertiesChecksTheType(): void
    {
        $this->project_analyzer->setPhpVersion('8.2', 'tests');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @property string $d */
                #[AllowDynamicProperties]
                class D {}

                $d = new D();
                $d->d = "a";
                $d->d = 1;',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage(
            'InvalidPropertyAssignmentValue - somefile.php:8:25 - $d->d with declared type \'string\' cannot be assigned type \'1\'',
        );
        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'issetOfAMagicPropertyThatNeverReturnsNullCanFail' => [
                'code' => '<?php
                    final class Bag {
                        public function __get(string $name): string {
                            return $name;
                        }

                        public function __isset(string $name): bool {
                            return $name === "known";
                        }
                    }

                    function read(Bag $bag): string {
                        if (!isset($bag->known)) {
                            return "unknown";
                        }

                        return $bag->known;
                    }',
            ],
            'magicPropertyThatIsNotSetIsStillReadThroughGet' => [
                'code' => '<?php
                    final class Node {
                        public function __get(string $name): Node {
                            return new Node();
                        }

                        public function __isset(string $name): bool {
                            return $name === "title";
                        }

                        public function getName(): string {
                            return "node";
                        }
                    }

                    function f(Node $node): string {
                        if (isset($node->title)) {
                            error_log("t");
                        }

                        return $node->title->getName();
                    }

                    $node = new Node();
                    if (!isset($node->title)) {
                        $not_set = $node->title;
                    }
                    $after = $node->title;',
                'assertions' => [
                    '$not_set===' => 'Node',
                    '$after===' => 'Node',
                ],
            ],
            'magicPropertyThatCanBeNullIsNullWhereItIsNotSet' => [
                'code' => '<?php
                    final class Node {
                        public function __get(string $name): ?Node {
                            return null;
                        }

                        public function __isset(string $name): bool {
                            return false;
                        }
                    }

                    $node = new Node();
                    if (!isset($node->title)) {
                        $not_set = $node->title;
                    }',
                'assertions' => [
                    '$not_set===' => 'null',
                ],
            ],
            'nestedIssetOfMagicPropertiesInAnAnd' => [
                'code' => '<?php
                    final class Node {
                        public function __get(string $name): Node {
                            return new Node();
                        }

                        public function __isset(string $name): bool {
                            return $name === "a";
                        }
                    }

                    function g(Node $node): void {
                        if (isset($node->a) && isset($node->a->b)) {
                            error_log("set");
                        }
                    }',
            ],
            'coalesceOfAMagicPropertyThatNeverReturnsNull' => [
                'code' => '<?php
                    final class Bag {
                        public function __get(string $name): string {
                            return $name;
                        }

                        public function __isset(string $name): bool {
                            return $name === "known";
                        }
                    }

                    $bag = new Bag();
                    $t = $bag->x ?? null;',
                'assertions' => [
                    '$t' => 'null|string',
                ],
            ],
            'issetOfAPseudoPropertyCanFail' => [
                'code' => '<?php
                    /**
                     * @property string $known
                     */
                    final class Bag {
                        public function __get(string $name): string {
                            return $name;
                        }

                        public function __isset(string $name): bool {
                            return $name === "known";
                        }
                    }

                    function read(Bag $bag): string {
                        if (!isset($bag->known)) {
                            return "unknown";
                        }

                        return $bag->known;
                    }',
            ],
            'propertyAnnotationWithATemplateOfAUsedTraitIsLocalizedInTheClass' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @property T|null $at
                     */
                    trait Tr {
                        public function __get(string $name): mixed {
                            return null;
                        }
                        public function __set(string $name, mixed $value): void {}
                    }

                    final class C {
                        /** @use Tr<int> */
                        use Tr;

                        public function fill(): void {
                            $this->at = 5;
                        }
                    }

                    $at = (new C())->at;',
                'assertions' => [
                    '$at===' => 'int|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyAnnotationWithATemplateOfTheParentIsLocalizedInTheChild' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @property T|null $at
                     */
                    abstract class Base {
                        public function __get(string $name): mixed {
                            return null;
                        }
                        public function __set(string $name, mixed $value): void {}
                    }

                    /** @extends Base<int> */
                    class Child extends Base {
                        public function fill(): void {
                            $this->at = 5;
                        }
                    }

                    final class GrandChild extends Child {}

                    $at = (new Child())->at;
                    $grandChildAt = (new GrandChild())->at;',
                'assertions' => [
                    '$at===' => 'int|null',
                    '$grandChildAt===' => 'int|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'valueOfPseudoPropertyIsNarrowedByAnEqualityCheck' => [
                'code' => '<?php
                    enum Foo: string {
                        case Bar = "bar";
                        case Lee = "lee";
                    }

                    /** @property value-of<Foo> $attr */
                    class Holder {
                        public function __set(string $name, mixed $value): void {}
                        public function __get(string $name): mixed {
                            return null;
                        }

                        public function check(): string {
                            if ($this->attr === Foo::Bar->value) {
                                return "bar";
                            }

                            if ($this->attr === Foo::Lee->value) {
                                return "lee";
                            }

                            return "";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantConditionGivenDocblockType', 'UnevaluatedCode'],
                'php_version' => '8.1',
            ],
            'magicGetNameKeepsItsClassStringType' => [
                'code' => '<?php
                    interface MakeMe {
                        public function __construct();
                    }

                    final class A implements MakeMe {
                        public function __construct() {}
                    }

                    final class IAmMagic {
                        /** @psalm-param class-string<MakeMe> $name */
                        public function __get(string $name): object {
                            return new $name();
                        }
                    }

                    $magic = new IAmMagic();
                    $made = $magic->{A::class};',
                'assertions' => [],
                'ignored_issues' => ['UndefinedMagicPropertyFetch', 'MixedAssignment'],
            ],
            'magicGetNameOfAStringVariableKeepsItsType' => [
                'code' => '<?php
                    final class IAmMagic {
                        /** @psalm-param non-empty-string $name */
                        public function __get(string $name): int {
                            return strlen($name);
                        }
                    }

                    /** @var non-empty-string $key */
                    $key = "a";
                    $magic = new IAmMagic();
                    $value = $magic->$key;',
                'assertions' => [],
                'ignored_issues' => ['UndefinedMagicPropertyFetch', 'MixedAssignment'],
            ],
            'propertyDocblock' => [
                'code' => '<?php
                    namespace Bar;

                    /**
                     * @property string $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = "hello";',
            ],
            'propertyOfTypeClassDocblock' => [
                'code' => '<?php
                    namespace Bar;

                    class PropertyType {}

                    /**
                     * @property PropertyType $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = new PropertyType();',
            ],
            'propertySealedDocblockDefinedPropertyFetch' => [
                'code' => '<?php
                    namespace Bar;
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    echo $a->foo;',
            ],
            /**
             * With a magic setter and no annotations specifying properties or types, we can
             * set anything we want on any variable name. The magic setter is trusted to figure
             * it out.
             */
            'magicSetterUndefinedPropertyNoAnnotation' => [
                'code' => '<?php
                    /** @psalm-no-seal-properties */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function goodSet(): void {
                            $this->__set("foo", new stdClass());
                        }
                    }',
            ],
            /**
             * With a magic getter and no annotations specifying properties or types, we can
             * get anything we want with any variable name. The magic getter is trusted to figure
             * it out.
             */
            'magicGetterUndefinedPropertyNoAnnotation' => [
                'code' => '<?php
                    /** @psalm-no-seal-properties */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function goodGet(): void {
                            echo $this->__get("foo");
                        }
                    }',
            ],
            /**
             * The property $foo is defined as a string with the `@property` annotation. We
             * use the magic setter to set it to a string, so everything is cool.
             */
            'magicSetterValidAssignmentType' => [
                'code' => '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function goodSet(): void {
                            $this->__set("foo", "value");
                        }
                    }',
            ],
            'magicGetterOnArrayValue' => [
                'code' => '<?php
                    /**
                     * @property string $send_type
                     * @psalm-seal-properties
                     */
                    final class b {
                        public function __set(string $key, mixed $value) {}
                    }

                    /**
                     * @param array<string, b> $objs
                     */
                    function test(string $k, array $objs): void {
                        $objs[$k]->send_type = "test";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyDocblockAssignmentToMixed' => [
                'code' => '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    /** @param mixed $b */
                    function foo($b) : void {
                        $a = new A();
                        $a->__set("foo", $b);
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedPropertyTypeCoercion'],
            ],
            'namedPropertyByVariable' => [
                'code' => '<?php
                    class A {
                        /** @var string|null */
                        public $foo;

                        public function __get(string $var_name) : ?string {
                            if ($var_name === "foo") {
                                return $this->$var_name;
                            }

                            return null;
                        }
                    }',
            ],
            'getPropertyExplicitCall' => [
                'code' => '<?php
                    class A {
                        public function __get(string $name) {}

                        /**
                         * @param mixed $value
                         */
                        public function __set(string $name, $value) {}
                    }

                    /**
                     * @property string $test
                     */
                    class B extends A {
                        public function test(): string {
                            return $this->__get("test");
                        }
                    }',
            ],
            'inheritedGetPropertyExplicitCall' => [
                'code' => '<?php
                    /**
                     * @property string $test
                     */
                    class A {
                        public function __get(string $name) {}

                        /**
                         * @param mixed $value
                         */
                        public function __set(string $name, $value) {}
                    }

                    class B extends A {
                        public function test(): string {
                            return $this->__get("test");
                        }
                    }',
            ],
            'undefinedThisPropertyFetchWithMagic' => [
                'code' => '<?php
                    /**
                     * @property-read string $name
                     * @property string $otherName
                     */
                    class A {
                        public function __get(string $name): void {
                        }

                        public function goodGet(): void {
                            echo $this->name;
                        }
                        public function goodGet2(): void {
                            echo $this->otherName;
                        }
                    }
                    $a = new A();
                    echo $a->name;
                    echo $a->otherName;',
            ],
            'psalmUndefinedThisPropertyFetchWithMagic' => [
                'code' => '<?php
                    /**
                     * @psalm-property-read string $name
                     * @property string $otherName
                     */
                    class A {
                        public function __get(string $name): void {
                        }

                        public function goodGet(): void {
                            echo $this->name;
                        }
                        public function goodGet2(): void {
                            echo $this->otherName;
                        }
                    }
                    $a = new A();
                    echo $a->name;
                    echo $a->otherName;',
            ],
            'directFetchForMagicProperty' => [
                'code' => '<?php
                    /**
                     * @property string $test
                     */
                    class C {
                        public function __get(string $name)
                        {
                        }

                        /**
                         * @param mixed $value
                         */
                        public function __set(string $name, $value)
                        {
                        }

                        public function test(): string
                        {
                            return $this->test;
                        }
                    }',
            ],
            'magicPropertyFetchOnProtected' => [
                'code' => '<?php
                    /** @psalm-no-seal-properties */
                    class C {
                        /** @var string */
                        protected $foo = "foo";

                        public function __get(string $name) {}

                        /**
                         * @param mixed $value
                         */
                        public function __set(string $name, $value)
                        {
                        }
                    }

                    $c = new C();
                    $c->foo = "bar";
                    echo $c->foo;',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument'],
            ],
            'dontAssumeNonNullAfterPossibleMagicFetch' => [
                'code' => '<?php
                    /** @psalm-no-seal-properties */
                    class C {
                        public function __get(string $name) : string {
                            return "hello";
                        }
                    }

                    function foo(?C $c) : void {
                        echo $c->foo;

                        if ($c) {}
                    }',
                'assertions' => [],
                'ignored_issues' => ['PossiblyNullPropertyFetch'],
            ],
            'accessInMagicGet' => [
                'code' => '<?php
                    /** @psalm-no-seal-properties */
                    class X {
                        public function __get(string $name) : string {
                            switch ($name) {
                                case "a":
                                    return $this->other;
                                case "other":
                                    return "foo";
                            }
                            return "default";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedReturnStatement'],
            ],
            'overrideInheritedProperty' => [
                'code' => '<?php
                    interface ServiceInterface {}

                    class ConcreteService implements ServiceInterface {
                        public function getById(int $i) : void {}
                    }

                    class Foo
                    {
                        /** @var ServiceInterface */
                        protected $service;

                        public function __construct(ServiceInterface $service)
                        {
                            $this->service = $service;
                        }
                    }

                    /** @property ConcreteService $service */
                    class FooBar extends Foo
                    {
                        public function __construct(ConcreteService $concreteService)
                        {
                            parent::__construct($concreteService);
                        }

                        public function doSomething(): void
                        {
                            $this->service->getById(123);
                        }
                    }',
            ],
            'magicInterfacePropertyRead' => [
                'code' => '<?php
                    /**
                     * @property-read string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : string {
                        return $o->foo;
                    }',
            ],
            'phanMagicInterfacePropertyRead' => [
                'code' => '<?php
                    /**
                     * @psalm-property-read string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : string {
                        return $o->foo;
                    }',
            ],
            'magicInterfacePropertyWrite' => [
                'code' => '<?php
                    /**
                     * @property-write string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : void {
                        $o->foo = "hello";
                    }',
            ],
            'psalmMagicInterfacePropertyWrite' => [
                'code' => '<?php
                    /**
                     * @psalm-property-write string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : void {
                        $o->foo = "hello";
                    }',
            ],
            'psalmPropertyDocblock' => [
                'code' => '<?php
                    namespace Bar;

                    /**
                     * @psalm-property string $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = "hello";',
            ],
            'overridePropertyAnnotations' => [
                'code' => '<?php
                    namespace Bar;

                    /**
                     * @property int $foo
                     * @psalm-property string $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = "hello";',
            ],
            'overrideWithReadWritePropertyAnnotations' => [
                'code' => '<?php
                    namespace Bar;

                    /**
                     * @psalm-property int $foo
                     * @property-read string $foo
                     * @property-write array $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }

                        public function takesString(string $s): void {}
                    }

                    $a = new A();
                    $a->foo = [];

                    $a = new A();
                    $a->takesString($a->foo);',
            ],
            'removeAssertionsAfterCall' => [
                'code' => '<?php
                    /** @psalm-no-seal-properties */
                    class C {
                        /**
                         * @return mixed
                         */
                        public function __get(string $name) {
                            return rand();
                        }

                        /**
                         * @param mixed $value
                         * @return void
                         */
                        public function __set(string $name, $value) {}

                        public function main() : void {
                            if (!isset($this->a)) {
                                $this->a = ["something"];

                                /**
                                 * @psalm-suppress MixedArrayAccess
                                 * @psalm-suppress MixedArgument
                                 */
                                echo $this->a[0];
                            }
                        }
                    }',
            ],
            'magicPropertyDefinedOnTrait' => [
                'code' => '<?php
                    class UserRecord
                    {
                        use UserFields;

                        private array $props = [];

                        public function __get(string $field)
                        {
                            return $this->props[$field];
                        }

                        /**
                         * @param mixed $value
                         */
                        public function __set(string $field, $value) : void
                        {
                            $this->props[$field] = $value;
                        }
                    }

                    /**
                     * @property mixed $email
                     * @property mixed $password
                     * @property mixed $last_login_at
                     */
                    trait UserFields {}

                    $record = new UserRecord();
                    $record->email;
                    $record->password;
                    $record->last_login_at = new DateTimeImmutable("now");',
            ],
            'reconcileMagicProperties' => [
                'code' => '<?php
                    /**
                     * @property string|null $a A
                     * @property string|null $b B
                     */
                    class Foo
                    {
                        private array $props = [];

                        public function __construct() {
                            $this->props["a"] = "hello";
                            $this->props["b"] = "goodbye";
                        }

                        /**
                         * @psalm-mutation-free
                         */
                        public function __get(string $prop){
                            return $this->props[$prop] ?? null;
                        }

                        /** @param mixed $b */
                        public function __set(string $a, $b){
                            $this->props[$a] = $b;
                        }

                        public function bar(): string {
                            if (is_null($this->a) || is_null($this->b)) {

                            } else {
                                return $this->b;
                            }

                            return "hello";
                        }
                    }',
            ],
            'propertyReadIsExpanded' => [
                'code' => '<?php
                    /** @property self::TYPE_* $type */
                    class A {
                        public const TYPE_A = 1;
                        public const TYPE_B = 2;

                        public function __get(string $_prop) {}
                        /** @param mixed $_value */
                        public function __set(string $_prop, $_value) {}
                    }
                    $a = (new A)->type;
                ',
                'assertions' => [
                    '$a===' => '1|2',
                ],
            ],
            'propertyWriteIsExpanded' => [
                'code' => '<?php
                    /** @property self::TYPE_* $type */
                    class A {
                        public const TYPE_A = 1;
                        public const TYPE_B = 2;

                        public function __get(string $_prop) {}
                        /** @param mixed $_value */
                        public function __set(string $_prop, $_value) {}
                    }
                    $a = (new A);
                    $a->type = A::TYPE_B;
                ',
            ],
            'propertyWithSelfWildcardConstantComparison' => [
                'code' => '<?php
                    /** @property self::STATUS_* $status */
                    final class Thing {
                        public const STATUS_A = "a";
                        public const STATUS_B = "b";

                        public function __get(string $_prop): mixed { return null; }
                        /** @param mixed $_value */
                        public function __set(string $_prop, $_value): void {}
                    }

                    function handle(Thing $t): void {
                        if ($t->status === Thing::STATUS_B) {}
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'propertyWithParentWildcardConstantComparison' => [
                'code' => '<?php
                    class Base {
                        public const FOO_A = "a";
                        public const FOO_B = "b";
                    }
                    /** @property parent::FOO_* $f */
                    final class C extends Base {
                        public function __get(string $_prop): mixed { return null; }
                        /** @param mixed $_value */
                        public function __set(string $_prop, $_value): void {}
                    }

                    function handle(C $c): void {
                        if ($c->f === Base::FOO_A) {}
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'propertyWithSelfLiteralConstant' => [
                'code' => '<?php
                    /** @property self::FOO_A $f */
                    final class C {
                        public const FOO_A = "a";
                        public const FOO_B = "b";

                        public function __get(string $_prop): mixed { return null; }
                        /** @param mixed $_value */
                        public function __set(string $_prop, $_value): void {}
                    }
                    $c = new C();
                    $v = $c->f;
                ',
                'assertions' => [
                    '$v===' => '\'a\'',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'propertyReadWithSelfWildcardConstant' => [
                'code' => '<?php
                    /** @property-read self::FOO_* $f */
                    final class C {
                        public const FOO_A = "a";
                        public const FOO_B = "b";

                        public function __get(string $_prop): mixed { return null; }
                    }

                    function handle(C $c): void {
                        if ($c->f === C::FOO_A) {}
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'propertyWriteWithSelfWildcardConstant' => [
                'code' => '<?php
                    /** @property-write self::FOO_* $f */
                    final class C {
                        public const FOO_A = "a";
                        public const FOO_B = "b";

                        /** @param mixed $_value */
                        public function __set(string $_prop, $_value): void {}
                    }
                    $c = new C();
                    $c->f = C::FOO_A;
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'propertyWithSelfWildcardConstantInheritedConstants' => [
                'code' => '<?php
                    class Base {
                        public const FOO_A = "a";
                        public const FOO_B = "b";
                    }
                    /** @property self::FOO_* $f */
                    final class C extends Base {
                        public function __get(string $_prop): mixed { return null; }
                        /** @param mixed $_value */
                        public function __set(string $_prop, $_value): void {}
                    }

                    function handle(C $c): void {
                        if ($c->f === C::FOO_A) {}
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'impureMethodTest' => [
                'code' => '<?php
                    /**
                     * @property array<string, string> $errors
                     *
                     * @psalm-seal-properties
                     */
                    final class OrganizationObject {

                        public function __get(string $key)
                        {
                            return [];
                        }

                        /**
                         * @param mixed $a
                         */
                        public function __set(string $key, $a): void
                        {
                        }

                        public function updateErrors(): void {
                            /** @var array<string, string> */
                            $errors = [];
                            $this->errors = $errors;
                        }
                        /** @return array<string, string> */
                        public function updateStatus(): array {
                            $_ = $this->errors;
                            $this->updateErrors();
                            $errors = $this->errors;
                            return $errors;
                        }
                    }',
            ],
            'propertyFetchWithNullCoalesce' => [
                'code' => '<?php
                    /**
                     * @property-read string|null $p
                     */
                    class A {
                        public function __get(string $name) {
                            return null;
                        }
                        public function f(): string {
                           return $this->p ?? \'\';
                        }
                    }',
            ],
            'pseudoPropertyFetchOnNullableReceiverInCoalesce' => [
                'code' => '<?php
                    /**
                     * @property string $value
                     */
                    class Foo {
                        public function __set(string $name, mixed $value): void {}
                        public function __get(string $name): mixed { return null; }
                    }

                    function coalesce(?Foo $foo): string {
                        return $foo->value ?? "default";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'pseudoPropertyReadFetchOnNullableReceiverInCoalesce' => [
                'code' => '<?php
                    /**
                     * @property-read string $value
                     */
                    class Foo {
                        public function __get(string $name): mixed { return null; }
                    }

                    function coalesce(?Foo $foo): string {
                        return $foo->value ?? "default";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'pseudoPropertyWithoutMagicGetOnNullableReceiverInCoalesce' => [
                'code' => '<?php
                    /**
                     * @property string $value
                     */
                    #[AllowDynamicProperties]
                    class Foo {}

                    function coalesce(?Foo $foo): string {
                        return $foo->value ?? "default";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'pseudoPropertyFetchKeepsDeclaredPropertyTypeOnUnionReceiver' => [
                'code' => '<?php
                    class Real {
                        public int $value = 0;
                    }

                    /**
                     * @property string $value
                     */
                    class Pseudo {
                        public function __set(string $name, mixed $value): void {}
                        public function __get(string $name): mixed { return null; }
                    }

                    /** @var Real|Pseudo $x */
                    $x = new Real();
                    $y = $x->value;',
                'assertions' => [
                    '$y' => 'int|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'propertyTypesWithSpacesAroundPipe' => [
                'code' => '<?php
                    final class B {}

                    /**
                     * @property B |null $a
                     * @property-read B | null $b
                     * @psalm-property B  |  null $c
                     * @property B
                     *     |null $d
                     */
                    class A {
                        public function __get(string $name) {
                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {}
                    }

                    $a = (new A)->a;
                    $b = (new A)->b;
                    $c = (new A)->c;
                    $d = (new A)->d;',
                'assertions' => [
                    '$a' => 'B|null',
                    '$b' => 'B|null',
                    '$c' => 'B|null',
                    '$d' => 'B|null',
                ],
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
            'propertyAnnotationWithATemplateOfTheParentRejectsAnotherTypeInTheChild' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @property T|null $at
                     */
                    abstract class Base {
                        public function __get(string $name): mixed {
                            return null;
                        }
                        public function __set(string $name, mixed $value): void {}
                    }

                    /** @extends Base<int> */
                    final class Child extends Base {
                        public function fill(): void {
                            $this->at = "x";
                        }
                    }',
                'error_message' => "declared type 'int|null' cannot be assigned type",
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'propertyAnnotationWithATemplateOfAUsedTraitRejectsAnotherTypeInTheClass' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @property T|null $at
                     */
                    trait Tr {
                        public function __get(string $name): mixed {
                            return null;
                        }
                        public function __set(string $name, mixed $value): void {}
                    }

                    final class C {
                        /** @use Tr<int> */
                        use Tr;

                        public function fill(): void {
                            $this->at = "x";
                        }
                    }',
                'error_message' => "declared type 'int|null' cannot be assigned type",
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'explicitSetCallSharingOnlyNullWithThePropertyIsInvalid' => [
                'code' => '<?php
                    final class Foo {}
                    final class Bar {}

                    /** @property ?Bar $p */
                    final class A {
                        /** @param mixed $value */
                        public function __set(string $name, $value): void {}
                    }

                    function f(A $a, ?Foo $foo): void {
                        $a->__set("p", $foo);
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'explicitSetCallWithParentTypeAndInvalidTypeIsPossiblyInvalid' => [
                'code' => '<?php
                    final class Foo {}

                    /** @property Foo $p */
                    final class A {
                        /** @param mixed $value */
                        public function __set(string $name, $value): void {}
                    }

                    function f(A $a, object|int $o): void {
                        $a->__set("p", $o);
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'explicitSetCallSharingAnObjectWithThePropertyIsPossiblyInvalid' => [
                'code' => '<?php
                    final class Foo {}
                    final class Bar {}

                    /** @property ?Bar $p */
                    final class A {
                        /** @param mixed $value */
                        public function __set(string $name, $value): void {}
                    }

                    function f(A $a, Foo|Bar|null $foo): void {
                        $a->__set("p", $foo);
                    }',
                'error_message' => 'PossiblyInvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'annotationWithoutGetter' => [
                'code' => '<?php
                    /**
                     * @property bool $is_protected
                     */
                    final class Page {
                        public function isProtected(): bool
                        {
                            return $this->is_protected;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'annotationWithoutGetterSaysHowToReadItThroughThis' => [
                'code' => '<?php
                    /**
                     * @property int $y
                     */
                    final class Page {
                        public function get(): int {
                            return $this->y;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:36 - Instance property Page::$y is not defined, but a docblock of Page declares it: add __get() to Page, or set usePhpDocPropertiesWithoutMagicCall="true" in the config',
            ],
            'annotationWithoutSetterSaysHowToWriteItThroughThis' => [
                'code' => '<?php
                    /**
                     * @property int $y
                     */
                    final class Page {
                        public function set(): void {
                            $this->y = 1;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:29 - Instance property Page::$y is not defined, but a docblock of Page declares it: add __set() to Page, or set usePhpDocPropertiesWithoutMagicCall="true" in the config',
            ],
            'annotationWithoutGetterSaysHowToReadIt' => [
                'code' => '<?php
                    /**
                     * @property bool $is_protected
                     */
                    final class Page {}

                    function isProtected(Page $page): bool {
                        return $page->is_protected;
                    }',
                'error_message' => 'UndefinedPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:32 - Instance property Page::$is_protected is not defined, but a docblock of Page declares it: add __get() to Page, or set usePhpDocPropertiesWithoutMagicCall="true" in the config',
            ],
            'annotationWithoutSetterSaysHowToWriteIt' => [
                'code' => '<?php
                    /**
                     * @property-write bool $is_protected
                     */
                    final class Page {}

                    function protect(Page $page): void {
                        $page->is_protected = true;
                    }',
                'error_message' => 'UndefinedPropertyAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:25 - Instance property Page::$is_protected is not defined, but a docblock of Page declares it: add __set() to Page, or set usePhpDocPropertiesWithoutMagicCall="true" in the config',
            ],
            'propertyWithSelfWildcardConstantNonMatchingString' => [
                'code' => '<?php
                    /** @property self::STATUS_* $status */
                    final class Thing {
                        public const STATUS_A = "a";
                        public const STATUS_B = "b";

                        public function __get(string $_prop): mixed { return null; }
                        /** @param mixed $_value */
                        public function __set(string $_prop, $_value): void {}
                    }

                    function handle(Thing $t): void {
                        if ($t->status === "not-a-status") {}
                    }
                ',
                'error_message' => 'DocblockTypeContradiction',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'propertyDocblockInvalidAssignment' => [
                'code' => '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertyInvalidClassAssignment' => [
                'code' => '<?php
                    namespace Bar;

                    class PropertyType {}
                    class SomeOtherPropertyType {}

                    /**
                     * @property PropertyType $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = new SomeOtherPropertyType();',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:29:31 - $a->foo with declared type'
                    . ' \'Bar\PropertyType\' cannot',
            ],
            'propertyWriteDocblockInvalidAssignment' => [
                'code' => '<?php
                    /**
                     * @property-write string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'psalmPropertyWriteDocblockInvalidAssignment' => [
                'code' => '<?php
                    /**
                     * @psalm-property-write string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertySealedDocblockUndefinedPropertyAssignment' => [
                'code' => '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->bar = 5;',
                'error_message' => 'UndefinedMagicPropertyAssignment',
            ],
            'propertySealedDocblockDefinedPropertyAssignment' => [
                'code' => '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertyReadInvalidFetch' => [
                'code' => '<?php
                    /**
                     * @property-read string $foo
                     */
                    class A {
                        /** @return mixed */
                        public function __get(string $name) {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }
                    }

                    $a = new A();
                    echo count($a->foo);',
                'error_message' => 'InvalidArgument',
            ],
            'psalmPropertyReadInvalidFetch' => [
                'code' => '<?php
                    /**
                     * @psalm-property-read string $foo
                     */
                    class A {
                        /** @return mixed */
                        public function __get(string $name) {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }
                    }

                    $a = new A();
                    echo count($a->foo);',
                'error_message' => 'InvalidArgument',
            ],
            'propertySealedDocblockUndefinedPropertyFetch' => [
                'code' => '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    echo $a->bar;',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            /**
             * The property $foo is not defined on the object, but accessed with the magic setter.
             * This is an error because `@psalm-seal-properties` is specified on the class block.
             */
            'magicSetterUndefinedProperty' => [
                'code' => '<?php
                    /**
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function badSet(): void {
                            $this->__set("foo", "value");
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyAssignment',
            ],
            /**
             * The property $foo is not defined on the object, but accessed with the magic getter.
             * This is an error because `@psalm-seal-properties` is specified on the class block.
             */
            'magicGetterUndefinedProperty' => [
                'code' => '<?php
                    /**
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function badGet(): void {
                            $this->__get("foo");
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            /**
             * The property $foo is defined as a string with the `@property` annotation, but
             * the magic setter is used to set it to an object.
             */
            'magicSetterInvalidAssignmentType' => [
                'code' => '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function badSet(): void {
                            $this->__set("foo", new stdClass());
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertyDocblockAssignmentToMixed' => [
                'code' => '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    /** @param mixed $b */
                    function foo($b) : void {
                        $a = new A();
                        $a->__set("foo", $b);
                    }',
                'error_message' => 'MixedPropertyTypeCoercion',
            ],
            'magicInterfacePropertyWrongProperty' => [
                'code' => '<?php
                    /**
                     * @property-read string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getBar(GetterSetter $o) : string {
                        return $o->bar;
                    }',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'psalmMagicInterfacePropertyWrongProperty' => [
                'code' => '<?php
                    /**
                     * @psalm-property-read string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getBar(GetterSetter $o) : string {
                        return $o->bar;
                    }',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'magicInterfaceWrongPropertyWrite' => [
                'code' => '<?php
                    /**
                     * @property-write string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : void {
                        $o->bar = "hello";
                    }',
                'error_message' => 'UndefinedMagicPropertyAssignment',
            ],
            'psalmMagicInterfaceWrongPropertyWrite' => [
                'code' => '<?php
                    /**
                     * @psalm-property-write string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : void {
                        $o->bar = "hello";
                    }',
                'error_message' => 'UndefinedMagicPropertyAssignment',
            ],
            'propertyDocblockOnProperty' => [
                'code' => '<?php
                    class A {
                       /** @property string[] */
                      public array $arr;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'sealedWithNoProperties' => [
                'code' => '<?php
                    /**
                     * @psalm-seal-properties
                     */
                    final class OrganizationObject {

                        public function __get(string $key)
                        {
                            return [];
                        }

                    }
                    echo (new OrganizationObject)->errors;',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'sealedWithNoPropertiesNoPrefix' => [
                'code' => '<?php
                    /**
                     * @seal-properties
                     */
                    final class OrganizationObject {

                        public function __get(string $key)
                        {
                            return [];
                        }
                    }

                    echo (new OrganizationObject)->errors;',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'invalidPropertyFetchWithNullCoalesce' => [
                'code' => '<?php
                    /**
                     * @property-read string|null $p
                     *
                     * @psalm-no-seal-properties // For the test
                     */
                    class A {
                        public function __get(string $name) {
                            return null;
                        }
                        public function f(): string {
                           return $this->q ?? \'\';
                        }
                    }',
                'error_message' => 'MixedReturnStatement',
            ],
            'propertyWriteTypeWithSpaceBeforePipe' => [
                'code' => '<?php
                    /**
                     * @property-write string |null $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
        ];
    }

    public function testSealAllMethodsWithoutFoo(): void
    {
        Config::getInstance()->seal_all_properties = true;

        $this->addFile(
            'somefile.php',
            '<?php
              class A {
                public function __get(string $name) {}
              }

              class B extends A {}

              $b = new B();
              $result = $b->foo;
              ',
        );

        $error_message = 'UndefinedMagicPropertyFetch';
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage($error_message);
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoSealAllProperties(): void
    {
        Config::getInstance()->seal_all_properties = true;
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              /** @psalm-no-seal-properties */
              class A {
                /** @psalm-mutation-free */
                public function __get(string $name) {}
              }

              class B extends A {}

              $b = new B();
              /** @var string $result */
              $result = $b->foo;
              ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAReadOnlyAnnotationIsNoHintForAWrite(): void
    {
        $this->testConfig->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            /**
             * @property-read bool $is_protected
             */
            final class Page {}

            function protect(Page $page): void {
                $page->is_protected = true;
            }');

        $this->analyzeFile($file_path, new Context(), false);

        $this->assertSame(
            ['Instance property Page::$is_protected is not defined'],
            array_map(
                static fn(IssueData $issue): string => $issue->message,
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }
}
