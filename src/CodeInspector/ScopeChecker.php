<?php

namespace CodeInspector;

use PhpParser;

class ScopeChecker
{
    /**
     * Do all code paths in this list of statements exit the block (return/throw)
     *
     * @param  array<PhpParser\Node\Stmt>  $stmts
     * @param  bool $check_continue - also looks for a continue
     * @return bool
     */
    public static function doesLeaveBlock(array $stmts, $check_continue = true, $check_break = true)
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; $i--) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Return_ ||
                $stmt instanceof PhpParser\Node\Stmt\Throw_ ||
                ($check_continue && $stmt instanceof PhpParser\Node\Stmt\Continue_) ||
                ($check_break && $stmt instanceof PhpParser\Node\Stmt\Break_)) {

                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if ($stmt->else &&
                    self::doesLeaveBlock($stmt->stmts, $check_continue, $check_break) &&
                    self::doesLeaveBlock($stmt->else->stmts, $check_continue, $check_break)) {

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

            if ($stmt instanceof PhpParser\Node\Stmt\Switch_ && $stmt->cases[count($stmt->cases) - 1]->cond === null) {
                $all_cases_terminate = true;

                foreach ($stmt->cases as $case) {
                    if (!self::doesLeaveBlock($case->stmts, false)) {
                        return false;
                    }
                }

                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                continue;
            }

            return false;
        }

        return false;
    }

    public static function doesBreakOrContinue(array $stmts)
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; $i--) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Continue_ || $stmt instanceof PhpParser\Node\Stmt\Break_) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if ($stmt->else && self::doesBreakOrContinue($stmt->stmts) && self::doesBreakOrContinue($stmt->else->stmts)) {
                    if (empty($stmt->elseifs)) {
                        return true;
                    }

                    foreach ($stmt->elseifs as $elseif) {
                        if (!self::doesBreakOrContinue($elseif->stmts)) {
                            return false;
                        }
                    }

                    return true;
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Switch_ && $stmt->cases[count($stmt->cases) - 1]->cond === null) {
                $all_cases_terminate = true;

                foreach ($stmt->cases as $case) {
                    if (!self::doesBreakOrContinue($case->stmts)) {
                        return false;
                    }
                }

                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                continue;
            }

            return false;
        }

        return false;
    }

    public static function doesReturnOrThrow(array $stmts)
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; $i--) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Return_ || $stmt instanceof PhpParser\Node\Stmt\Throw_) {
                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if ($stmt->else && self::doesReturnOrThrow($stmt->stmts) && self::doesReturnOrThrow($stmt->else->stmts)) {
                    if (empty($stmt->elseifs)) {
                        return true;
                    }

                    foreach ($stmt->elseifs as $elseif) {
                        if (!self::doesReturnOrThrow($elseif->stmts)) {
                            return false;
                        }
                    }

                    return true;
                }
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Switch_ && $stmt->cases[count($stmt->cases) - 1]->cond === null) {
                $all_cases_terminate = true;

                $has_default = false;
                foreach ($stmt->cases as $case) {
                    if (self::doesBreakOrContinue($case->stmts)) {
                        return false;
                    }

                    if (self::doesReturnOrThrow($case->stmts)) {
                        return true;
                    }

                    if (!$case->cond) {
                        $has_default = true;
                    }
                }

                if ($has_default) {
                    return false;
                }

                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Nop) {
                continue;
            }

            return false;
        }

        return false;
    }

    public static function onlyThrows(array $stmts)
    {
        if (empty($stmts)) {
            return false;
        }

        for ($i = count($stmts) - 1; $i >= 0; $i--) {
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
