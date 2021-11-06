<?php
namespace Psalm\Tests;

class EnumTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

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
        ];
    }
}
