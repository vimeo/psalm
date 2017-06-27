<?php
namespace Psalm\Checker;

use PhpParser;

class ScopeChecker
{
    /**
     * Do all code paths in this list of statements exit the block (return/throw)
     *
     * @param  array<PhpParser\Node\Stmt|PhpParser\Node\Expr>  $stmts
     * @param  bool                                            $check_continue - also looks for a continue
     * @param  bool                                            $check_break
     *
     * @return bool
     */
    public static function doesLeaveBlock(array $stmts, $check_continue = true, $check_break = true)
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; --$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Return_ ||
                $stmt instanceof PhpParser\Node\Stmt\Throw_ ||
                $stmt instanceof PhpParser\Node\Expr\Exit_ ||
                ($check_continue && $stmt instanceof PhpParser\Node\Stmt\Continue_) ||
                ($check_break && $stmt instanceof PhpParser\Node\Stmt\Break_)
            ) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if ($stmt->else &&
                    self::doesLeaveBlock($stmt->stmts, $check_continue, $check_break) &&
                    self::doesLeaveBlock($stmt->else->stmts, $check_continue, $check_break)
                ) {
                    if (empty($stmt->elseifs)) {
                        return true;
                    }

                    foreach ($stmt->elseifs as $elseif) {
                        if (!self::doesLeaveBlock($elseif->stmts, $check_continue, $check_break)) {
                            return false;
                        }
                    }

                    return true;
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                $has_left = false;

                $has_default_leaver = false;

                // iterate backwards in a case statement
                for ($j = count($stmt->cases) - 1; $j >= 0; --$j) {
                    $case = $stmt->cases[$j];

                    $case_does_leave = self::doesEverBreakOrContinue($case->stmts, true);

                    if ($case_does_leave) {
                        $has_left = true;
                    }

                    if (!$case_does_leave && !$has_left) {
                        return false;
                    }

                    if (!$case->cond && $case_does_leave) {
                        $has_default_leaver = true;
                    }
                }

                return $has_default_leaver;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                continue;
            }

            return false;
        }

        return false;
    }

    /**
     * @param   array<PhpParser\Node>   $stmts
     *
     * @return  bool
     */
    public static function doesEverBreak(array $stmts)
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; --$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Break_) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if (self::doesEverBreak($stmt->stmts)) {
                    return true;
                }

                if ($stmt->else && self::doesEverBreak($stmt->else->stmts)) {
                    return true;
                }

                foreach ($stmt->elseifs as $elseif) {
                    if (self::doesEverBreak($elseif->stmts)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param   array<PhpParser\Node>   $stmts
     * @param   bool                    $ignore_break
     *
     * @return  bool
     */
    public static function doesEverBreakOrContinue(array $stmts, $ignore_break = false)
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; --$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Continue_ ||
                (!$ignore_break && $stmt instanceof PhpParser\Node\Stmt\Break_)
            ) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if (self::doesEverBreakOrContinue($stmt->stmts, $ignore_break)) {
                    return true;
                }

                if ($stmt->else && self::doesEverBreakOrContinue($stmt->else->stmts, $ignore_break)) {
                    return true;
                }

                foreach ($stmt->elseifs as $elseif) {
                    if (self::doesEverBreakOrContinue($elseif->stmts, $ignore_break)) {
                        return true;
                    }
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                // iterate backwards
                // in switch statements we only care here about continue
                for ($j = count($stmt->cases) - 1; $j >= 0; --$j) {
                    $case = $stmt->cases[$j];

                    if (self::doesEverBreakOrContinue($case->stmts, true)) {
                        return true;
                    }
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                continue;
            }
        }

        return false;
    }

    /**
     * @param   array<PhpParser\Node>   $stmts
     * @param   bool                    $ignore_break
     *
     * @return  bool
     */
    public static function doesAlwaysBreakOrContinue(array $stmts, $ignore_break = false)
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; --$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Continue_ ||
                (!$ignore_break && $stmt instanceof PhpParser\Node\Stmt\Break_)
            ) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if (!self::doesAlwaysBreakOrContinue($stmt->stmts, $ignore_break)) {
                    return false;
                }

                if (!$stmt->else || !self::doesAlwaysBreakOrContinue($stmt->else->stmts, $ignore_break)) {
                    return false;
                }

                foreach ($stmt->elseifs as $elseif) {
                    if (!self::doesAlwaysBreakOrContinue($elseif->stmts, $ignore_break)) {
                        return false;
                    }
                }

                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                // iterate backwards
                // in switch statements we only care here about continue
                for ($j = count($stmt->cases) - 1; $j >= 0; --$j) {
                    $case = $stmt->cases[$j];

                    if (!self::doesAlwaysBreakOrContinue($case->stmts, true)) {
                        return false;
                    }
                }

                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                continue;
            }
        }

        return false;
    }

    /**
     * @param   array<PhpParser\Node> $stmts
     *
     * @return  bool
     */
    public static function doesAlwaysReturnOrThrow(array $stmts)
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; --$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Return_ ||
                $stmt instanceof PhpParser\Node\Stmt\Throw_ ||
                $stmt instanceof PhpParser\Node\Expr\Exit_
            ) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if ($stmt->else &&
                    self::doesAlwaysReturnOrThrow($stmt->stmts) &&
                    self::doesAlwaysReturnOrThrow($stmt->else->stmts)
                ) {
                    if (empty($stmt->elseifs)) {
                        return true;
                    }

                    foreach ($stmt->elseifs as $elseif) {
                        if (!self::doesAlwaysReturnOrThrow($elseif->stmts)) {
                            return false;
                        }
                    }

                    return true;
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                $has_returned = false;
                $has_default_terminator = false;

                // iterate backwards in a case statement
                for ($j = count($stmt->cases) - 1; $j >= 0; --$j) {
                    $case = $stmt->cases[$j];

                    if (self::doesEverBreakOrContinue($case->stmts)) {
                        return false;
                    }

                    $case_does_return = self::doesAlwaysReturnOrThrow($case->stmts);

                    if ($case_does_return) {
                        $has_returned = true;
                    }

                    if (!$case_does_return && !$has_returned) {
                        return false;
                    }

                    if (!$case->cond && $case_does_return) {
                        $has_default_terminator = true;
                    }
                }

                return $has_default_terminator;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\While_) {
                if (self::doesAlwaysReturnOrThrow($stmt->stmts)) {
                    return true;
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                if (self::doesAlwaysReturnOrThrow($stmt->stmts)) {
                    foreach ($stmt->catches as $catch) {
                        if (!self::doesAlwaysReturnOrThrow($catch->stmts)) {
                            return false;
                        }
                    }

                    return true;
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                continue;
            }

            return false;
        }

        return false;
    }

    /**
     * @param   array<PhpParser\Node> $stmts
     *
     * @return  bool
     */
    public static function onlyThrows(array $stmts)
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; --$i) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Throw_) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                continue;
            }

            return false;
        }

        return false;
    }
}
