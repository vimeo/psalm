<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class EnumTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'example' => [
                'code' => '<?php
                    interface Colourful {
                        public function color(): string;
                    }

                    enum Suit implements Colourful {
                        case Hearts;
                        case Diamonds;
                        case Clubs;
                        case Spades;

                        public function color(): string {
                            return match($this) {
                                Suit::Hearts, Suit::Diamonds => "Red",
                                Suit::Clubs, Suit::Spades => "Black",
                            };
                        }

                        public function shape(): string {
                            return "Rectangle";
                        }
                    }

                    function paint(Colourful $c): void {}
                    function deal(Suit $s): void {
                        if ($s === Suit::Clubs) {
                            echo $s->color();
                        }
                    }

                    paint(Suit::Clubs);
                    deal(Suit::Spades);

                    Suit::Diamonds->shape();',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'enumValue' => [
                'code' => '<?php
                    enum Suit: string {
                        case Hearts = "h";
                        case Diamonds = "d";
                        case Clubs = "c";
                        case Spades = "s";
                    }

                    if (Suit::Hearts->value === "h") {}',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'enumCases' => [
                'code' => '<?php
                    enum Suit {
                        case Hearts;
                        case Diamonds;
                        case Clubs;
                        case Spades;
                    }

                    foreach (Suit::cases() as $case) {
                        echo match($case) {
                            Suit::Hearts, Suit::Diamonds => "Red",
                            Suit::Clubs, Suit::Spades => "Black",
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'literalExpressionAsCaseValue' => [
                'code' => '<?php
                    enum Mask: int {
                        case One = 1 << 0;
                        case Two = 1 << 1;
                    }
                    $z = Mask::Two->value;
                ',
                'assertions' => [
                    '$z===' => '2',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'EnumCaseValue #8568' => [
                'code' => '<?php
                    enum Mask: int {
                        case One = 1 << 0;
                        case Two = 1 << 1;
                    }
                    /** @return Mask */
                    function a() {
                        return Mask::One;
                    }

                    $z = a()->value;
                ',
                'assertions' => [
                    '$z===' => '1|2',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'EnumUnionAsCaseValue #8568' => [
                'code' => '<?php
                    enum Mask: int {
                        case One = 1 << 0;
                        case Two = 1 << 1;
                        case Four = 1 << 2;
                    }
                    /** @return Mask::One|Mask::Two */
                    function a() {
                        return Mask::One;
                    }

                    $z = a()->value;
                ',
                'assertions' => [
                    '$z===' => '1|2',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'matchCaseOnEnumValue #8812' => [
                'code' => '<?php
                    enum SomeType: string
                    {
                        case FOO = "FOO";
                        case BAR = "BAR";
                    }

                    function getSomething(string $moduleString): int
                    {
                        return match ($moduleString) {
                            SomeType::FOO->value => 1,
                            SomeType::BAR->value => 2,
                        };
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'namePropertyFromOutside' => [
                'code' => '<?php
                    enum Status
                    {
                        case DRAFT;
                        case PUBLISHED;
                        case ARCHIVED;
                    }
                    $a = Status::DRAFT->name;
                ',
                'assertions' => [
                    '$a===' => "'DRAFT'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'namePropertyFromInside' => [
                'code' => '<?php
                    enum Status
                    {
                        case DRAFT;
                        case PUBLISHED;
                        case ARCHIVED;

                        /**
                         * @return non-empty-string
                         */
                        public function get(): string
                        {
                            return $this->name;
                        }
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'valuePropertyFromInside' => [
                'code' => '<?php
                    enum Status: string
                    {
                        case DRAFT = "draft";
                        case PUBLISHED = "published";
                        case ARCHIVED = "archived";

                        public function get(): string
                        {
                            return $this->value;
                        }
                    }

                    echo Status::DRAFT->get();

                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'wildcardEnumAsParam' => [
                'code' => '<?php
                    enum A {
                        case C_1;
                        case C_2;
                        case C_3;

                        /**
                         * @param self::C_* $i
                         */
                        public static function foo(self $i) : void {}
                    }

                    A::foo(A::C_1);
                    A::foo(A::C_2);
                    A::foo(A::C_3);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'wildcardEnumAsReturn' => [
                'code' => '<?php
                    enum E {
                        const A = 1;
                        case B;
                    }

                    /** @return E::* */
                    function f(): mixed {
                        return E::B;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'wildcardConstantsOnEnum' => [
                'code' => '<?php
                    enum A {
                        const C_1 = 1;
                        const C_2 = 2;
                        const C_3 = 3;

                        /**
                         * @param self::C_* $i
                         */
                        public static function foo(int $i) : void {}
                    }

                    A::foo(A::C_1);
                    A::foo(A::C_2);
                    A::foo(A::C_3);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'constantOfAVariableEnumClassString' => [
                'code' => '<?php
                    enum A { const C = 3; }
                    $e = A::class;
                    $_z = $e::C;
                ',
                'assertions' => [
                    '$_z===' => '3',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'constantOfAVariableEnumInstance' => [
                'code' => '<?php
                    enum A {
                        const C = 3;
                        case AA;
                    }
                    $e = A::AA;
                    $_z = $e::C;
                ',
                'assertions' => [
                    '$_z===' => '3',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'EnumCaseInAttribute' => [
                'code' => '<?php
                    class CreateController {
                        #[Param(paramType: ParamType::FLAG)]
                        public function actionGet(): void {}
                    }

                    use Attribute;

                    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
                    class Param {
                        public function __construct(
                            public ParamType $paramType = ParamType::PARAM
                        ) {
                        }
                    }

                    enum ParamType {
                        case FLAG;
                        case PARAM;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'casesOnEnumWithNoCasesReturnEmptyArray' => [
                'code' => '<?php
                    enum Status: int {}
                    $_z = Status::cases();
                ',
                'assertions' => [
                    '$_z===' => 'array<never, never>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'backedEnumFromReturnsInstanceOfThatEnum' => [
                'code' => '<?php
                    enum Status: int {
                        case Open = 1;
                        case Closed = 2;
                    }

                    function f(): Status {
                        return Status::from(1);
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'backedEnumTryFromReturnsInstanceOfThatEnum' => [
                'code' => '<?php
                    enum Status: int {
                        case Open = 1;
                        case Closed = 2;
                    }

                    function f(): Status {
                        return Status::tryFrom(rand(1, 10)) ?? Status::Open;
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'backedEnumFromReturnsSpecificCase' => [
                'code' => '<?php
                    enum Status: int {
                        case Open = 1;
                        case Closed = 2;
                    }

                    $_z = Status::from(2);
                ',
                'assertions' => [
                    '$_z===' => 'enum(Status::Closed)',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'backedEnumTryFromReturnsSpecificCase' => [
                'code' => '<?php
                    enum Status: int {
                        case Open = 1;
                        case Closed = 2;
                    }

                    $_z = Status::tryFrom(2);
                ',
                'assertions' => [
                    '$_z===' => 'enum(Status::Closed)|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'backedEnumFromReturnsUnionOfCases' => [
                'code' => '<?php
                    enum Status: int {
                        case Open = 1;
                        case Closed = 2;
                        case Busted = 3;
                    }

                    $_z = Status::from(rand(1, 2));
                ',
                'assertions' => [
                    '$_z===' => 'enum(Status::Closed)|enum(Status::Open)',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'backedEnumTryFromReturnsUnionOfCases' => [
                'code' => '<?php
                    enum Status: int {
                        case Open = 1;
                        case Closed = 2;
                        case Busted = 3;
                    }

                    $_z = Status::tryFrom(rand(1, 2));
                ',
                'assertions' => [
                    '$_z===' => 'enum(Status::Closed)|enum(Status::Open)|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'InterfacesWithProperties' => [
                'code' => '<?php

                    static fn (\UnitEnum $tag): string => $tag->name;

                    static fn (\BackedEnum $tag): string|int => $tag->value;

                    interface ExtendedUnitEnum extends \UnitEnum {}
                    static fn (ExtendedUnitEnum $tag): string => $tag->name;

                    interface ExtendedBackedEnum extends \BackedEnum {}
                    static fn (ExtendedBackedEnum $tag): string|int => $tag->value;
                    ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'EnumCollapsing' => [
                'code' => '<?php
                    enum Code: int
                    {
                        case Ok = 0;
                        case Fatal = 1;
                    }

                    function foo(): int|Code|null
                    {
                        return null;
                    }

                    $code = foo();
                    if(!isset($code)){
                        $code = Code::Ok;
                    }',
                'assertions' => [
                    '$code' => 'Code|int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'EnumCaseReconciliation' => [
                'code' => '<?php
                    enum Code: int
                    {
                        case Ok = 0;
                        case Fatal = 1;
                    }

                    function foo(): Code|null
                    {
                        return null;
                    }

                    $code = foo();
                    $code1 = null;
                    $code2 = null;
                    if($code instanceof Code){
                        $code1 = $code;
                    }
                    if(!$code instanceof Code){
                        $code2 = $code;
                    }',
                'assertions' => [
                    '$code1' => 'Code|null',
                    '$code2' => 'null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'constantAsACaseValueOfABackedEnum' => [
                'code' => '<?php
                    enum Test: string
                    {
                        public const ENUM_VALUE = "forty two";

                        case TheAnswer = self::ENUM_VALUE;
                    }
                    $a = Test::TheAnswer->value;
                ',
                'assertions' => [
                    '$a===' => "'forty two'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'methodInheritanceByInterfaces' => [
                'code' => '<?php
                    interface I extends BackedEnum {}
                    /** @var I $i */
                    $a = $i::cases();
                    $b = $i::from(1);
                    $c = $i::tryFrom(2);
                ',
                'assertions' => [
                    '$a===' => 'list<I>',
                    '$b===' => 'I',
                    '$c===' => 'I|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'reconcileCaseWithInterface' => [
                'code' => <<<'PHP'
                    <?php
                    interface I {}
                    enum E implements I { case A; }
                    function f(I $i): void {
                        if ($i === E::A) {
                        } else {
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'valueOfBackedEnum' => [
                'code' => <<<'PHP'
                    <?php
                    enum StringEnum: string {
                        case FOO = 'foo';
                        case BAR = 'bar';
                    }

                    enum IntEnum: int {
                        case FOO = 1;
                        case BAR = 2;
                    }

                    /** @var value-of<StringEnum::FOO> $string */
                    $string = '';
                    /** @var value-of<StringEnum::*> $anyString */
                    $anyString = '';

                    /** @var value-of<IntEnum::FOO> $int */
                    $int = 0;
                    /** @var value-of<IntEnum::*> $anyInt */
                    $anyInt = 0;
                    PHP,
                'assertions' => [
                    '$string===' => '\'foo\'',
                    '$anyString===' => '\'bar\'|\'foo\'',
                    '$int===' => '1',
                    '$anyInt===' => '1|2',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'nameTypeOnKnownCases' => [
                'code' => <<<'PHP'
                    <?php
                    enum Transport: string {
                        case CAR = 'car';
                        case BIKE = 'bike';
                        case BOAT = 'boat';
                    }

                    $val = Transport::from(uniqid());
                    $_name = $val->name;
                    PHP,
                'assertions' => [
                    '$_name===' => "'BIKE'|'BOAT'|'CAR'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'nameTypeOnUnknownCases' => [
                'code' => <<<'PHP'
                    <?php
                    enum Transport: string {
                        case CAR = 'car';
                        case BIKE = 'bike';
                        case BOAT = 'boat';
                    }

                    function f(Transport $e): void {
                        $_name = $e->name;
                        /** @psalm-check-type-exact $_name='BIKE'|'BOAT'|'CAR' */;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'classStringAsBackedEnumValue' => [
                'code' => <<<'PHP'
                    <?php
                    class Foo {}

                    enum FooEnum: string {
                        case Foo = Foo::class;
                    }

                    /**
                     * @param class-string $s
                     */
                    function noop(string $s): string
                    {
                        return $s;
                    }

                    $foo = FooEnum::Foo->value;
                    noop($foo);
                    noop(FooEnum::Foo->value);
                PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'backedEnumCaseValueFromClassConstant' => [
                'code' => <<<'PHP'
                    <?php
                    class FooBar {
                        public const FOO = 'foo';
                        public const BAR = 2;
                    }

                    enum FooEnum: string {
                        case FOO = FooBar::FOO;
                    }

                    enum BarEnum: int {
                        case BAR = FooBar::BAR;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'enumValueIsNot' => [
                'code' => '<?php
                    enum Suit: string {
                        case Hearts = "h";
                        case Diamonds = "d";
                        case Clubs = "c";
                        case Spades = "s";
                    }

                    if (Suit::Hearts->value === "a") {}',
                'error_message' => 'TypeDoesNotContainType',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'enumValueNotBacked' => [
                'code' => '<?php
                    enum Suit {
                        case Hearts;
                        case Diamonds;
                        case Clubs;
                        case Spades;
                    }

                    echo Suit::Hearts->value;',
                'error_message' => 'UndefinedPropertyFetch',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'badSuit' => [
                'code' => '<?php
                    enum Suit {
                        case Hearts;
                        case Diamonds;
                        case Clubs;
                        case Spades;
                    }

                    function foo(Suit $s): void {
                        if ($s === Suit::Clu) {}
                    }',
                'error_message' => 'UndefinedConstant',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'cantCompareToSuitTwice' => [
                'code' => '<?php
                    enum Suit {
                        case Hearts;
                        case Diamonds;
                        case Clubs;
                        case Spades;
                    }

                    function foo(Suit $s): void {
                        if ($s === Suit::Clubs)  {
                            if ($s === Suit::Clubs) {
                                echo "bad";
                            }
                        }
                    }',
                'error_message' => 'RedundantCondition',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'insufficientMatches' => [
                'code' => '<?php
                    enum Suit {
                        case Hearts;
                        case Diamonds;
                        case Clubs;
                        case Spades;

                        public function color(): string {
                            return match($this) {
                                Suit::Hearts, Suit::Diamonds => "Red",
                                Suit::Clubs => "Black",
                            };
                        }
                    }',
                'error_message' => 'UnhandledMatchCondition',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'insufficientMatchesForCases' => [
                'code' => '<?php
                    enum Suit {
                        case Hearts;
                        case Diamonds;
                        case Clubs;
                        case Spades;
                    }

                    foreach (Suit::cases() as $case) {
                        echo match($case) {
                            Suit::Hearts, Suit::Diamonds => "Red",
                            Suit::Clubs => "Black",
                        };
                    }',
                'error_message' => 'UnhandledMatchCondition',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'invalidBackingType' => [
                'code' => '<?php
                    enum Status: array {}
                ',
                'error_message' => 'InvalidEnumBackingType',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'invalidCaseTypeForBackedEnum' => [
                'code' => '<?php
                    enum Status: int {
                        case Open = [];
                    }
                ',
                'error_message' => 'InvalidEnumCaseValue',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'duplicateValues' => [
                'code' => '<?php
                    enum Status: string
                    {
                        case Foo = "foo";
                        case Bar = "bar";
                        case Baz = "bar";
                    }
                ',
                'error_message' => 'DuplicateEnumCaseValue',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'duplicateCases' => [
                'code' => '<?php
                    enum Status
                    {
                        case Foo;
                        case Foo;
                    }
                ',
                'error_message' => 'DuplicateEnumCase',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'caseWithAValueOfANonBackedEnum' => [
                'code' => '<?php
                    enum Status
                    {
                        case Foo = 1;
                    }
                ',
                'error_message' => 'InvalidEnumCaseValue',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'caseWithoutAValueOfABackedEnum' => [
                'code' => '<?php
                    enum Status: int
                    {
                        case Foo;
                    }
                ',
                'error_message' => 'InvalidEnumCaseValue',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'caseTypeMismatch' => [
                'code' => '<?php
                    enum Status: int
                    {
                        case Foo = "one";
                    }
                ',
                'error_message' => 'InvalidEnumCaseValue',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'propsOnEnum' => [
                'code' => '<?php
                    enum Status {
                        public $prop;
                    }
                ',
                'error_message' => 'NoEnumProperties',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'enumInstantiation' => [
                'code' => '<?php
                    enum Status {}
                    new Status;
                ',
                'error_message' => 'UndefinedClass',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'enumsAsAttributes' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_CLASS)]
                    enum Status { }
                    ',
                'error_message' => 'InvalidAttribute',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'deprecatedAttribute' => [
                'code' => '<?php
                    enum Foo {
                        case A;

                        #[Psalm\Deprecated]
                        case B;
                    }

                    Foo::B;
                    ',
                'error_message' => 'DeprecatedConstant',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'forbiddenMethod' => [
                'code' => '<?php
                    enum Foo {
                        case A;
                        public function __get() {}
                    }
                ',
                'error_message' => 'InvalidEnumMethod',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'forbiddenUnitEnumImplementation' => [
                'code' => '<?php
                    class Foo implements UnitEnum {
                        /** @psalm-pure */
                        public static function cases(): array
                        {
                            return [];
                        }
                    }
                ',
                'error_message' => 'InvalidInterfaceImplementation',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'forbiddenBackedEnumImplementation' => [
                'code' => '<?php
                    class Foo implements BackedEnum {
                        /** @psalm-pure */
                        public static function cases(): array
                        {
                            return [];
                        }

                        /** @psalm-pure */
                        public static function from(int|string $value): static
                        {
                            throw new Exception;
                        }

                        /** @psalm-pure */
                        public static function tryFrom(int|string $value): ?static
                        {
                            return null;
                        }
                    }
                ',
                'error_message' => 'InvalidInterfaceImplementation',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'forbiddenUnitEnumCasesMethod' => [
                'code' => '<?php
                    enum Foo {
                        case A;
                        public static function cases(): array
                        {
                            return [];
                        }
                    }
                ',
                'error_message' => 'InvalidEnumMethod',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'forbiddenBackedEnumCasesMethod' => [
                'code' => '<?php
                    enum Status: string {
                        case Open = "open";
                        public static function cases(): array
                        {
                            return [];
                        }
                    }
                ',
                'error_message' => 'InvalidEnumMethod',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'forbiddenBackedEnumFromMethod' => [
                'code' => '<?php
                    enum Status: string {
                        case Open = "open";
                        public static function from(string $value): self
                        {
                            throw new Exception;
                        }
                    }
                ',
                'error_message' => 'InvalidEnumMethod',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'forbiddenBackedEnumTryFromMethod' => [
                'code' => '<?php
                    enum Status: string {
                        case Open = "open";
                        public static function tryFrom(string $value): ?self
                        {
                            return null;
                        }
                    }
                ',
                'error_message' => 'InvalidEnumMethod',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'functionCallWithInvalidCase' => [
                'code' => '<?php
                    enum Status {
                        case Open;
                        case Closed;
                    }

                    /** @param Status::Open $status */
                    function foo(Status $status): void {}

                    foo(Status::Closed);
                ',
                'error_message' => 'InvalidArgument',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'issue-7814-1' => [
                'code' => '<?php
                    enum State
                    {
                        case A;
                        case B;
                        case C;
                    }

                    /**
                     * @param State::A|State::B $_
                     */
                    function test(State $_): void {}

                    test(State::C);
                ',
                'error_message' => 'InvalidArgument',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'issue-7814-2' => [
                'code' => '<?php
                    enum State
                    {
                        case A;
                        case B;
                        case C;
                    }

                    /**
                     * @template T of State
                     */
                    final class WithState
                    {
                        /**
                         * @param T $s
                         */
                        public function __construct(
                            public readonly State $s,
                        ) {}
                    }

                    /**
                     * @param WithState<State::A> $_
                     */
                    function withA(WithState $_): void {}

                    // Should be issue here. But nothing
                    // Argument 1 of withA expects WithState<enum(State::A)>, WithState<enum(State::C)> provided
                    withA(new WithState(State::C));
                ',
                'error_message' => 'InvalidArgument',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'backedEnumDoesNotPassNativeType' => [
                'code' => '<?php
                    enum State: string
                    {
                        case A = "A";
                        case B = "B";
                        case C = "C";
                    }
                    function f(string $state): void {}
                    f(State::A);
                ',
                'error_message' => 'InvalidArgument',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'stringBackedEnumCaseValueFromClassConstant' => [
                'code' => '<?php
                    class Foo {
                        const FOO = 1;
                    }

                    enum Bar: string
                    {
                        case Foo = Foo::FOO;
                    }
                ',
                'error_message' => 'InvalidEnumCaseValue',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'intBackedEnumCaseValueFromClassConstant' => [
                'code' => '<?php
                    class Foo {
                        const FOO = "foo";
                    }

                    enum Bar: int
                    {
                        case Foo = Foo::FOO;
                    }
                ',
                'error_message' => 'InvalidEnumCaseValue',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
