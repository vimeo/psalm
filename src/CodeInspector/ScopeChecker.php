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
    public static function doesLeaveBlock(array $stmts, $check_continue = true)
    {
        for ($i = count($stmts) - 1; $i >= 0; $i--) {
            $stmt = $stmts[$i];

            if ($stmt instanceof PhpParser\Node\Stmt\Return_ ||
                $stmt instanceof PhpParser\Node\Stmt\Throw_ ||
                ($check_continue && ($stmt instanceof PhpParser\Node\Stmt\Continue_ || $stmt instanceof PhpParser\Node\Stmt\Break_))) {

                return true;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\If_) {
                if ($stmt->else && self::doesLeaveBlock($stmt->stmts, $check_continue) && self::doesLeaveBlock($stmt->else->stmts, $check_continue)) {
                    if (empty($stmt->elseifs)) {
                        return true;
                    }

                    foreach ($stmt->elseifs as $elseif) {
                        if (!self::doesLeaveBlock($elseif->stmts, $check_continue)) {
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
}
