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
        ];
    }
}
