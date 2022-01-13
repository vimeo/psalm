<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class EnumTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:array<string>}>
     */
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
                'php_version' => '8.1'
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
                'php_version' => '8.1'
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
                'php_version' => '8.1'
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
                    // xxx: we should be able to do better when we reference a case explicitly, like above
                    '$z===' => '1|2',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1'
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
                    '$a===' => '"DRAFT"',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1'
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
                'php_version' => '8.1'
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
                'php_version' => '8.1'
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
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:array<string>,php_version?:string}>
     */
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
                'php_version' => '8.1'
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
                'php_version' => '8.1'
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
                'php_version' => '8.1'
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
                'php_version' => '8.1'
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
                'php_version' => '8.1'
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
                'php_version' => '8.1'
            ],
            'invalidBackingType' => [
                'code' => '<?php
                    enum Status: array {}
                ',
                'error_message' => 'InvalidEnumBackingType',
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
        ];
    }
}
