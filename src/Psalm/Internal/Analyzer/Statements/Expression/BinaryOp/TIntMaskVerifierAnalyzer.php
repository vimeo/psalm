<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntMaskVerifier;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Union;

use function array_intersect;
use function array_merge;
use function array_unique;
use function array_values;
use function count;

/**
 * @internal
 */
final class TIntMaskVerifierAnalyzer
{
    public static function analyze(
        PhpParser\Node $parent,
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type = null,
        int $max_int_mask_combinations = 10,
    ): ?Union {
        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr) {
            return self::analyzeBitwiseOrOperation(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
                $max_int_mask_combinations,
            );
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd) {
            return self::analyzeBitwiseAndOperation(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
                $max_int_mask_combinations,
            );
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor) {
            return self::analyzeBitwiseXorOperation(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
            );
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft) {
            return self::analyzeShiftLeftOperation(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
            );
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight) {
            return self::analyzeShiftRightOperation(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
            );
        }

        return null;
    }

    private static function analyzeBitwiseOrOperation(
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type = null,
        int $max_int_mask_combinations = 10,
    ): ?Union {
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TIntMaskVerifier) {
            $left_potential_ints = $left_type_part->potential_ints;
            $right_potential_ints = $right_type_part->potential_ints;
            $potential_ints = array_merge($left_potential_ints, $right_potential_ints);
            $potential_ints = array_unique($potential_ints);

            $result_type = Type::combineUnionTypes(
                new Union([new TIntMaskVerifier($potential_ints)]),
                $result_type,
            );

            $has_valid_left_operand = true;
            $has_valid_right_operand = true;
            return null;
        }

        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TInt) {
            if ($right_type_part instanceof TLiteralInt) {
                $left_potential_ints = $left_type_part->potential_ints;

                $total_combinations = count($left_potential_ints);
                $left_possible_ints = $left_type_part->getPossibleInts();

                if ($total_combinations <= $max_int_mask_combinations) {
                    $calculated_masks = [];
                    foreach ($left_possible_ints as $left_int) {
                        $result_int = $left_int | $right_type_part->value;
                        $calculated_masks[] = new TLiteralInt($result_int);
                    }

                    if ($calculated_masks) {
                        $result_type = Type::combineUnionTypes(
                            new Union($calculated_masks),
                            $result_type,
                        );
                    }
                } else {
                    $new_potential_ints = $left_potential_ints;
                    $new_potential_ints[] = $right_type_part->value;

                    $new_verifier = new TIntMaskVerifier($new_potential_ints);
                    $result_type = Type::combineUnionTypes(
                        new Union([$new_verifier]),
                        $result_type,
                    );
                }

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;
                return null;
            } else {
                $potential_ints = $left_type_part->potential_ints;
                $new_verifier = new TIntMaskVerifier($potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );

                return null;
            }
        }

        if ($left_type_part instanceof TInt && $right_type_part instanceof TIntMaskVerifier) {
            if ($left_type_part instanceof TLiteralInt) {
                $right_potential_ints = $right_type_part->potential_ints;

                $total_combinations = count($right_potential_ints);
                $right_possible_ints = $right_type_part->getPossibleInts();

                if ($total_combinations <= $max_int_mask_combinations) {
                    $calculated_masks = [];
                    foreach ($right_possible_ints as $right_int) {
                        $result_int = $left_type_part->value | $right_int;
                        $calculated_masks[] = new TLiteralInt($result_int);
                    }

                    if ($calculated_masks) {
                        $result_type = Type::combineUnionTypes(
                            new Union($calculated_masks),
                            $result_type,
                        );
                    }
                } else {
                    $new_potential_ints = $right_potential_ints;
                    $new_potential_ints[] = $left_type_part->value;

                    $new_verifier = new TIntMaskVerifier($new_potential_ints);
                    $result_type = Type::combineUnionTypes(
                        new Union([$new_verifier]),
                        $result_type,
                    );
                }

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;

                return null;
            } else {
                $potential_ints = $right_type_part->potential_ints;
                $new_verifier = new TIntMaskVerifier($potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );

                return null;
            }
        }

        return null;
    }

    private static function analyzeBitwiseAndOperation(
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type = null,
        int $max_int_mask_combinations = 10,
    ): ?Union {
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TIntMaskVerifier) {
            $left_potential_ints = $left_type_part->potential_ints;
            $right_potential_ints = $right_type_part->potential_ints;
            $potential_ints = array_intersect($left_potential_ints, $right_potential_ints);
            $potential_ints = array_values($potential_ints);
            $result_type = Type::combineUnionTypes(
                new Union([new TIntMaskVerifier($potential_ints)]),
                $result_type,
            );

            $has_valid_left_operand = true;
            $has_valid_right_operand = true;

            return null;
        }

        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TInt) {
            if ($right_type_part instanceof TLiteralInt) {
                $left_potential_ints = $left_type_part->potential_ints;

                $total_combinations = count($left_potential_ints);

                if ($total_combinations <= $max_int_mask_combinations) {
                    $left_possible_ints = $left_type_part->getPossibleInts();
                    $calculated_masks = [];
                    foreach ($left_possible_ints as $left_int) {
                        $result_int = $left_int & $right_type_part->value;
                        $calculated_masks[] = new TLiteralInt($result_int);
                    }
                    if ($calculated_masks) {
                        $result_type = Type::combineUnionTypes(
                            new Union($calculated_masks),
                            $result_type,
                        );
                    }
                } else {
                    $new_potential_ints = $left_potential_ints;
                    $new_potential_ints[] = $right_type_part->value;
                    $new_potential_ints = array_unique($new_potential_ints);
                    $new_verifier = new TIntMaskVerifier($new_potential_ints);
                    $result_type = Type::combineUnionTypes(
                        new Union([$new_verifier]),
                        $result_type,
                    );
                }

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;
                return null;
            } else {
                $potential_ints = $left_type_part->potential_ints;
                $new_verifier = new TIntMaskVerifier($potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );

                return null;
            }
        }

        if ($left_type_part instanceof TInt && $right_type_part instanceof TIntMaskVerifier) {
            if ($left_type_part instanceof TLiteralInt) {
                $right_potential_ints = $right_type_part->potential_ints;

                $total_combinations = count($right_potential_ints);

                if ($total_combinations <= $max_int_mask_combinations) {
                    $right_possible_ints = $right_type_part->getPossibleInts();
                    $calculated_masks = [];
                    foreach ($right_possible_ints as $right_int) {
                        $result_int = $left_type_part->value & $right_int;
                        $calculated_masks[] = new TLiteralInt($result_int);
                    }
                    if ($calculated_masks) {
                        $result_type = Type::combineUnionTypes(
                            new Union($calculated_masks),
                            $result_type,
                        );
                    }
                } else {
                    $new_potential_ints = $right_potential_ints;
                    $new_potential_ints[] = $left_type_part->value;
                    $new_potential_ints = array_unique($new_potential_ints);
                    $new_verifier = new TIntMaskVerifier($new_potential_ints);
                    $result_type = Type::combineUnionTypes(
                        new Union([$new_verifier]),
                        $result_type,
                    );
                }

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;

                return null;
            } else {
                $potential_ints = $right_type_part->potential_ints;
                $new_verifier = new TIntMaskVerifier($potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );

                return null;
            }
        }

        return null;
    }

    private static function analyzeBitwiseXorOperation(
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type = null,
    ): ?Union {
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TIntMaskVerifier) {
            $left_potential_ints = $left_type_part->potential_ints;
            $right_potential_ints = $right_type_part->potential_ints;
            $potential_ints = array_merge($left_potential_ints, $right_potential_ints);
            $potential_ints = array_unique($potential_ints);
            $result_type = Type::combineUnionTypes(
                new Union([new TIntMaskVerifier($potential_ints)]),
                $result_type,
            );

            $has_valid_left_operand = true;
            $has_valid_right_operand = true;

            return null;
        }

        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TInt) {
            if ($right_type_part instanceof TLiteralInt) {
                $left_potential_ints = $left_type_part->potential_ints;
                $new_potential_ints = $left_potential_ints;
                $new_potential_ints[] = $right_type_part->value;
                $new_potential_ints = array_unique($new_potential_ints);
                $new_verifier = new TIntMaskVerifier($new_potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;
                return null;
            } else {
                $potential_ints = $left_type_part->potential_ints;
                $new_verifier = new TIntMaskVerifier($potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );
                return null;
            }
        }

        if ($left_type_part instanceof TInt && $right_type_part instanceof TIntMaskVerifier) {
            if ($left_type_part instanceof TLiteralInt) {
                $right_potential_ints = $right_type_part->potential_ints;
                $new_potential_ints = $right_potential_ints;
                $new_potential_ints[] = $left_type_part->value;
                $new_potential_ints = array_unique($new_potential_ints);
                $new_verifier = new TIntMaskVerifier($new_potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;
                return null;
            } else {
                $potential_ints = $right_type_part->potential_ints;
                $new_verifier = new TIntMaskVerifier($potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );
            }
        }

        return null;
    }

    private static function analyzeShiftLeftOperation(
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type = null,
    ): ?Union {
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TInt) {
            if ($right_type_part instanceof TLiteralInt) {
                $left_potential_ints = $left_type_part->potential_ints;
                $new_potential_ints = [];
                foreach ($left_potential_ints as $int) {
                    $new_potential_ints[] = $int << $right_type_part->value;
                }
                $new_potential_ints = array_unique($new_potential_ints);
                $new_verifier = new TIntMaskVerifier($new_potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;
                return null;
            } else {
                $result_type = Type::combineUnionTypes(
                    Type::getInt(),
                    $result_type,
                );

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;
                return null;
            }
        }

        if ($left_type_part instanceof TInt && $right_type_part instanceof TIntMaskVerifier) {
            $result_type = Type::combineUnionTypes(
                Type::getInt(),
                $result_type,
            );

            $has_valid_left_operand = true;
            $has_valid_right_operand = true;
            return null;
        }

        return null;
    }

    private static function analyzeShiftRightOperation(
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type = null,
    ): ?Union {
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TInt) {
            if ($right_type_part instanceof TLiteralInt) {
                $left_potential_ints = $left_type_part->potential_ints;
                $new_potential_ints = [];
                foreach ($left_potential_ints as $int) {
                    $new_potential_ints[] = $int >> $right_type_part->value;
                }
                $new_potential_ints = array_unique($new_potential_ints);
                $new_verifier = new TIntMaskVerifier($new_potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;
                return null;
            } else {
                $result_type = Type::combineUnionTypes(
                    Type::getInt(),
                    $result_type,
                );

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;
                return null;
            }
        }

        if ($left_type_part instanceof TInt && $right_type_part instanceof TIntMaskVerifier) {
            $result_type = Type::combineUnionTypes(
                Type::getInt(),
                $result_type,
            );

            $has_valid_left_operand = true;
            $has_valid_right_operand = true;
            return null;
        }

        return null;
    }
}
