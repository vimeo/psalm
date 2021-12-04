<?php
namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class EnumTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'example' => [
                '<?php
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
                [],
                [],
                '8.1'
            ],
            'enumValue' => [
                '<?php
                    enum Suit: string {
                        case Hearts = "h";
                        case Diamonds = "d";
                        case Clubs = "c";
                        case Spades = "s";
                    }

                    if (Suit::Hearts->value === "h") {}',
                [],
                [],
                '8.1'
            ],
            'enumCases' => [
                '<?php
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
                [],
                [],
                '8.1'
            ],
            'literalExpressionAsCaseValue' => [
                '<?php
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
                [],
                '8.1'
            ],
            'namePropertyFromOutside' => [
                '<?php
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
                [],
                '8.1'
            ],
            'namePropertyFromInside' => [
                '<?php
                    enum Status
                    {
                        case DRAFT;
                        case PUBLISHED;
                        case ARCHIVED;

                        public function get(): string
                        {
                            return $this->name;
                        }
                    }
                ',
                'assertions' => [],
                [],
                '8.1'
            ],
            'valuePropertyFromInside' => [
                '<?php
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
                [],
                '8.1'
            ],
            'wildcardEnumAsParam' => [
                '<?php
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
                [],
                '8.1',
            ],
            'wildcardEnumAsReturn' => [
                '<?php
                    enum E {
                        const A = 1;
                        case B;
                    }

                    /** @return E::* */
                    function f(): mixed {
                        return E::B;
                    }',
                'assertions' => [],
                [],
                '8.1',
            ],
            'wildcardConstantsOnEnum' => [
                '<?php
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
                [],
                '8.1',
            ],
            'constantOfAVariableEnumClassString' => [
                '<?php
                    enum A { const C = 3; }
                    $e = A::class;
                    $_z = $e::C;
                ',
                'assertions' => [
                    '$_z===' => '3',
                ],
                [],
                '8.1',
            ],
            'constantOfAVariableEnumInstance' => [
                '<?php
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
                [],
                '8.1',
            ],
            'EnumCaseInAttribute' => [
                '<?php
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
                [],
                '8.1',
            ],
            'casesOnEnumWithNoCasesReturnEmptyArray' => [
                '<?php
                    enum Status: int {}
                    $_z = Status::cases();
                ',
                'assertions' => [
                    '$_z===' => 'array<empty, empty>',
                ],
                [],
                '8.1',
            ],
            'backedEnumFromReturnsInstanceOfThatEnum' => [
                '<?php
                    enum Status: int {
                        case Open = 1;
                        case Closed = 2;
                    }

                    function f(): Status {
                        return Status::from(1);
                    }
                ',
                'assertions' => [],
                [],
                '8.1',
            ],
            'backedEnumTryFromReturnsInstanceOfThatEnum' => [
                '<?php
                    enum Status: int {
                        case Open = 1;
                        case Closed = 2;
                    }

                    function f(): Status {
                        return Status::tryFrom(rand(1, 10)) ?? Status::Open;
                    }
                ',
                'assertions' => [],
                [],
                '8.1',
            ],
            'backedEnumFromReturnsSpecificCase' => [
                '<?php
                    enum Status: int {
                        case Open = 1;
                        case Closed = 2;
                    }

                    $_z = Status::from(2);
                ',
                'assertions' => [
                    '$_z===' => 'enum(Status::Closed)',
                ],
                [],
                '8.1',
            ],
            'backedEnumTryFromReturnsSpecificCase' => [
                '<?php
                    enum Status: int {
                        case Open = 1;
                        case Closed = 2;
                    }

                    $_z = Status::tryFrom(2);
                ',
                'assertions' => [
                    '$_z===' => 'enum(Status::Closed)|null',
                ],
                [],
                '8.1',
            ],
            'backedEnumFromReturnsUnionOfCases' => [
                '<?php
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
                [],
                '8.1',
            ],
            'backedEnumTryFromReturnsUnionOfCases' => [
                '<?php
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
                [],
                '8.1',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'enumValueIsNot' => [
                '<?php
                    enum Suit: string {
                        case Hearts = "h";
                        case Diamonds = "d";
                        case Clubs = "c";
                        case Spades = "s";
                    }

                    if (Suit::Hearts->value === "a") {}',
                'error_message' => 'TypeDoesNotContainType',
                [],
                false,
                '8.1'
            ],
            'enumValueNotBacked' => [
                '<?php
                    enum Suit {
                        case Hearts;
                        case Diamonds;
                        case Clubs;
                        case Spades;
                    }

                    echo Suit::Hearts->value;',
                'error_message' => 'UndefinedPropertyFetch',
                [],
                false,
                '8.1'
            ],
            'badSuit' => [
                '<?php
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
                [],
                false,
                '8.1'
            ],
            'cantCompareToSuitTwice' => [
                '<?php
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
                [],
                false,
                '8.1'
            ],
            'insufficientMatches' => [
                '<?php
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
                [],
                false,
                '8.1'
            ],
            'insufficientMatchesForCases' => [
                '<?php
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
                [],
                false,
                '8.1'
            ],
            'invalidBackingType' => [
                '<?php
                    enum Status: array {}
                ',
                'error_message' => 'InvalidEnumBackingType',
                [],
                false,
                '8.1',
            ],
            'duplicateValues' => [
                '<?php
                    enum Status: string
                    {
                        case Foo = "foo";
                        case Bar = "bar";
                        case Baz = "bar";
                    }
                ',
                'error_message' => 'DuplicateEnumCaseValue',
                [],
                false,
                '8.1',
            ],
            'duplicateCases' => [
                '<?php
                    enum Status
                    {
                        case Foo;
                        case Foo;
                    }
                ',
                'error_message' => 'DuplicateEnumCase',
                [],
                false,
                '8.1',
            ],
            'caseWithAValueOfANonBackedEnum' => [
                '<?php
                    enum Status
                    {
                        case Foo = 1;
                    }
                ',
                'error_message' => 'InvalidEnumCaseValue',
                [],
                false,
                '8.1',
            ],
            'caseWithoutAValueOfABackedEnum' => [
                '<?php
                    enum Status: int
                    {
                        case Foo;
                    }
                ',
                'error_message' => 'InvalidEnumCaseValue',
                [],
                false,
                '8.1',
            ],
            'caseTypeMismatch' => [
                '<?php
                    enum Status: int
                    {
                        case Foo = "one";
                    }
                ',
                'error_message' => 'InvalidEnumCaseValue',
                [],
                false,
                '8.1',
            ],
            'propsOnEnum' => [
                '<?php
                    enum Status {
                        public $prop;
                    }
                ',
                'error_message' => 'NoEnumProperties',
                [],
                false,
                '8.1',
            ],
            'enumInstantiation' => [
                '<?php
                    enum Status {}
                    new Status;
                ',
                'error_message' => 'UndefinedClass',
                [],
                false,
                '8.1',
            ],
            'enumsAsAttributes' => [
                '<?php
                    #[Attribute(Attribute::TARGET_CLASS)]
                    enum Status { }
                    ',
                'error_message' => 'InvalidAttribute',
                [],
                false,
                '8.1',
            ],
        ];
    }
}
