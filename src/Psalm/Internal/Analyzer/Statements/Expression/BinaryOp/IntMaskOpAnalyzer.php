<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntMaskVerifier;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Union;

/**
 * Analyzer for operations involving TIntMaskVerifier types.
 */
final class IntMaskOpAnalyzer
{
    /**
     * Analyze operations between TIntMaskVerifier types.
     */
    public static function analyzeTIntMaskVerifierOperands(
        ?StatementsSource $statements_source,
        PhpParser\Node $parent,
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type = null,
        int $max_int_mask_combinations = 10,
    ): ?Union {
        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr) {
            return self::analyzeBitwiseOrWithMasks(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
                $max_int_mask_combinations
            );
        }
        
        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd) {
            return self::analyzeBitwiseAndWithMasks(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
                $max_int_mask_combinations
            );
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor) {
            return self::analyzeBitwiseXorWithMasks(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }
        
        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft) {
            return self::analyzeShiftLeftWithMasks(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight) {
            return self::analyzeShiftRightWithMasks(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }

        return null;
    }

    /**
     * Handle BitwiseOr operations between TIntMaskVerifier types.
     */
    private static function analyzeBitwiseOrWithMasks(
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type,
        int $max_int_mask_combinations
    ): ?Union {
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TIntMaskVerifier) {
            return self::handleMaskVerifierToMaskVerifier(
                $left_type_part->potential_ints,
                $right_type_part->potential_ints,
                array_merge(...),
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }
        
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TInt) {
            return self::handleMaskVerifierWithInt(
                $left_type_part,
                $right_type_part,
                fn($left_int, $right_value) => $left_int | $right_value,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
                $max_int_mask_combinations
            );
        }
        
        if ($left_type_part instanceof TInt && $right_type_part instanceof TIntMaskVerifier) {
            return self::handleIntWithMaskVerifier(
                $left_type_part,
                $right_type_part,
                fn($left_value, $right_int) => $left_value | $right_int,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
                $max_int_mask_combinations
            );
        }

        return null;
    }

    /**
     * Handle BitwiseAnd operations between TIntMaskVerifier types.
     */
    private static function analyzeBitwiseAndWithMasks(
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type,
        int $max_int_mask_combinations
    ): ?Union {
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TIntMaskVerifier) {
            // For BitwiseAnd, only the bits that are common to both operands should be in the result
            return self::handleMaskVerifierToMaskVerifier(
                $left_type_part->potential_ints,
                $right_type_part->potential_ints,
                array_intersect(...),
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }

        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TInt) {
            return self::handleMaskVerifierWithInt(
                $left_type_part,
                $right_type_part,
                fn($left_int, $right_value) => $left_int & $right_value,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
                $max_int_mask_combinations
            );
        }
        
        if ($left_type_part instanceof TInt && $right_type_part instanceof TIntMaskVerifier) {
            return self::handleIntWithMaskVerifier(
                $left_type_part,
                $right_type_part,
                fn($left_value, $right_int) => $left_value & $right_int,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type,
                $max_int_mask_combinations
            );
        }

        return null;
    }

    /**
     * Handle BitwiseXor operations between TIntMaskVerifier types.
     */
    private static function analyzeBitwiseXorWithMasks(
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type
    ): ?Union {
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TIntMaskVerifier) {
            return self::handleMaskVerifierToMaskVerifier(
                $left_type_part->potential_ints,
                $right_type_part->potential_ints,
                array_merge(...),
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }

        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TInt) {
            return self::handleMaskVerifierWithIntForXor(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }
        
        if ($left_type_part instanceof TInt && $right_type_part instanceof TIntMaskVerifier) {
            return self::handleIntWithMaskVerifierForXor(
                $left_type_part,
                $right_type_part,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }

        return null;
    }

    /**
     * Handle left shift operations with TIntMaskVerifier types.
     */
    private static function analyzeShiftLeftWithMasks(
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type
    ): ?Union {
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TInt) {
            return self::handleShiftOperation(
                $left_type_part,
                $right_type_part,
                fn($int, $shift) => $int << $shift,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }
        
        if ($left_type_part instanceof TInt && $right_type_part instanceof TIntMaskVerifier) {
            return self::setGenericIntResult(
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }

        return null;
    }

    /**
     * Handle right shift operations with TIntMaskVerifier types.
     */
    private static function analyzeShiftRightWithMasks(
        Atomic $left_type_part,
        Atomic $right_type_part,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type
    ): ?Union {
        if ($left_type_part instanceof TIntMaskVerifier && $right_type_part instanceof TInt) {
            return self::handleShiftOperation(
                $left_type_part,
                $right_type_part,
                fn($int, $shift) => $int >> $shift,
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }
        
        if ($left_type_part instanceof TInt && $right_type_part instanceof TIntMaskVerifier) {
            return self::setGenericIntResult(
                $has_valid_left_operand,
                $has_valid_right_operand,
                $result_type
            );
        }

        return null;
    }

    /**
     * Handle operations between two TIntMaskVerifier types.
     */
    private static function handleMaskVerifierToMaskVerifier(
        array $left_potential_ints,
        array $right_potential_ints,
        callable $array_operation,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type
    ): ?Union {
        $potential_ints = $array_operation($left_potential_ints, $right_potential_ints);
        $potential_ints = array_unique(array_values($potential_ints));

        $result_type = Type::combineUnionTypes(
            new Union([new TIntMaskVerifier($potential_ints)]),
            $result_type,
        );
        
        $has_valid_left_operand = true;
        $has_valid_right_operand = true;
        
        return null;
    }

    /**
     * Handle operations between TIntMaskVerifier and TInt.
     */
    private static function handleMaskVerifierWithInt(
        TIntMaskVerifier $mask_verifier,
        TInt $int_type,
        callable $operation,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type,
        int $max_int_mask_combinations
    ): ?Union {
        if ($int_type instanceof TLiteralInt) {
            $mask_potential_ints = $mask_verifier->potential_ints;
            $total_combinations = count($mask_potential_ints);
            
            if ($total_combinations <= $max_int_mask_combinations) {
                $possible_ints = $mask_verifier->getPossibleInts();
                $calculated_masks = [];
                foreach ($possible_ints as $mask_int) {
                    $result_int = $operation($mask_int, $int_type->value);
                    $calculated_masks[] = new TLiteralInt($result_int);
                }

                if ($calculated_masks) {
                    $result_type = Type::combineUnionTypes(
                        new Union($calculated_masks),
                        $result_type,
                    );
                }
            } else {
                $new_potential_ints = $mask_potential_ints;
                $new_potential_ints[] = $int_type->value;
                $new_potential_ints = array_unique($new_potential_ints);

                $new_verifier = new TIntMaskVerifier($new_potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );
            }
        } else {
            // Non-literal int - create verifier with existing potential ints
            $potential_ints = $mask_verifier->potential_ints;
            $new_verifier = new TIntMaskVerifier($potential_ints);
            $result_type = Type::combineUnionTypes(
                new Union([$new_verifier]),
                $result_type,
            );
        }
        
        $has_valid_left_operand = true;
        $has_valid_right_operand = true;
        
        return null;
    }

    /**
     * Handle operations between TInt and TIntMaskVerifier.
     */
    private static function handleIntWithMaskVerifier(
        TInt $int_type,
        TIntMaskVerifier $mask_verifier,
        callable $operation,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type,
        int $max_int_mask_combinations
    ): ?Union {
        if ($int_type instanceof TLiteralInt) {
            $mask_potential_ints = $mask_verifier->potential_ints;
            $total_combinations = count($mask_potential_ints);
            
            if ($total_combinations <= $max_int_mask_combinations) {
                $possible_ints = $mask_verifier->getPossibleInts();
                $calculated_masks = [];
                foreach ($possible_ints as $mask_int) {
                    $result_int = $operation($int_type->value, $mask_int);
                    $calculated_masks[] = new TLiteralInt($result_int);
                }

                if ($calculated_masks) {
                    $result_type = Type::combineUnionTypes(
                        new Union($calculated_masks),
                        $result_type,
                    );
                }
            } else {
                $new_potential_ints = $mask_potential_ints;
                $new_potential_ints[] = $int_type->value;
                $new_potential_ints = array_unique($new_potential_ints);

                $new_verifier = new TIntMaskVerifier($new_potential_ints);
                $result_type = Type::combineUnionTypes(
                    new Union([$new_verifier]),
                    $result_type,
                );
            }
        } else {
            // Non-literal int - create verifier with existing potential ints
            $potential_ints = $mask_verifier->potential_ints;
            $new_verifier = new TIntMaskVerifier($potential_ints);
            $result_type = Type::combineUnionTypes(
                new Union([$new_verifier]),
                $result_type,
            );
        }
        
        $has_valid_left_operand = true;
        $has_valid_right_operand = true;
        
        return null;
    }

    /**
     * Handle XOR operation between TIntMaskVerifier and TInt.
     */
    private static function handleMaskVerifierWithIntForXor(
        TIntMaskVerifier $mask_verifier,
        TInt $int_type,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type
    ): ?Union {
        $mask_potential_ints = $mask_verifier->potential_ints;
        
        if ($int_type instanceof TLiteralInt) {
            $new_potential_ints = $mask_potential_ints;
            $new_potential_ints[] = $int_type->value;
            $new_potential_ints = array_unique($new_potential_ints);
        } else {
            $new_potential_ints = $mask_potential_ints;
        }
        
        $new_verifier = new TIntMaskVerifier($new_potential_ints);
        $result_type = Type::combineUnionTypes(
            new Union([$new_verifier]),
            $result_type,
        );

        $has_valid_left_operand = true;
        $has_valid_right_operand = true;
        
        return null;
    }

    /**
     * Handle XOR operation between TInt and TIntMaskVerifier.
     */
    private static function handleIntWithMaskVerifierForXor(
        TInt $int_type,
        TIntMaskVerifier $mask_verifier,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type
    ): ?Union {
        $mask_potential_ints = $mask_verifier->potential_ints;
        
        if ($int_type instanceof TLiteralInt) {
            $new_potential_ints = $mask_potential_ints;
            $new_potential_ints[] = $int_type->value;
            $new_potential_ints = array_unique($new_potential_ints);
        } else {
            $new_potential_ints = $mask_potential_ints;
        }
        
        $new_verifier = new TIntMaskVerifier($new_potential_ints);
        $result_type = Type::combineUnionTypes(
            new Union([$new_verifier]),
            $result_type,
        );

        $has_valid_left_operand = true;
        $has_valid_right_operand = true;
        
        return null;
    }

    /**
     * Handle shift operations with TIntMaskVerifier.
     */
    private static function handleShiftOperation(
        TIntMaskVerifier $mask_verifier,
        TInt $int_type,
        callable $shift_operation,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type
    ): ?Union {
        if ($int_type instanceof TLiteralInt) {
            $mask_potential_ints = $mask_verifier->potential_ints;
            $new_potential_ints = [];
            foreach ($mask_potential_ints as $int) {
                $new_potential_ints[] = $shift_operation($int, $int_type->value);
            }
            $new_potential_ints = array_unique($new_potential_ints);
            
            $new_verifier = new TIntMaskVerifier($new_potential_ints);
            $result_type = Type::combineUnionTypes(
                new Union([$new_verifier]),
                $result_type,
            );
        } else {
            // Non-literal right operand - return generic int type
            $result_type = Type::combineUnionTypes(
                Type::getInt(),
                $result_type,
            );
        }
        
        $has_valid_left_operand = true;
        $has_valid_right_operand = true;
        
        return null;
    }

    /**
     * Set a generic int result and mark operands as valid.
     */
    private static function setGenericIntResult(
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        ?Union &$result_type
    ): ?Union {
        $result_type = Type::combineUnionTypes(
            Type::getInt(),
            $result_type,
        );
        
        $has_valid_left_operand = true;
        $has_valid_right_operand = true;
        
        return null;
    }
}
