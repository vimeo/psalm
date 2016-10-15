<?php

namespace Psalm;

use PhpParser;

/**
 * A class for analysing a given method call's effects in relation to
 * $this/self and also looking at return types
 */
class EffectsAnalyser
{
    /**
     * Gets the return types from a list of statements
     *
     * @param  array<int,PhpParser\Node\Stmt>  $stmts
     * @return array<int,AtomicType>    a list of return types
     */
    public static function getReturnTypes(array $stmts, $collapse_types = false)
    {
        $return_types = [];

        $last_stmt = null;

        foreach ($stmts as $stmt) {
            if (!$stmt instanceof PhpParser\Node\Stmt\Nop) {
                $last_stmt = $stmt;
            }

            if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                if (isset($stmt->inferredType)) {
                    $return_types = array_merge(array_values($stmt->inferredType->types), $return_types);
                }
                else {
                    $return_types[] = new Type\Atomic('mixed');
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\If_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

                foreach ($stmt->elseifs as $elseif) {
                    $return_types = array_merge($return_types, self::getReturnTypes($elseif->stmts));
                }

                if ($stmt->else) {
                    $return_types = array_merge($return_types, self::getReturnTypes($stmt->else->stmts));
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\TryCatch) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

                foreach ($stmt->catches as $catch) {
                    $return_types = array_merge($return_types, self::getReturnTypes($catch->stmts));
                }

                if ($stmt->finallyStmts) {
                    $return_types = array_merge($return_types, self::getReturnTypes($stmt->finallyStmts));
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\For_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Foreach_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

            } elseif ($stmt instanceof PhpParser\Node\Stmt\While_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Do_) {
                $return_types = array_merge($return_types, self::getReturnTypes($stmt->stmts));

            } elseif ($stmt instanceof PhpParser\Node\Stmt\Switch_) {
                foreach ($stmt->cases as $case) {
                    $return_types = array_merge($return_types, self::getReturnTypes($case->stmts));
                }
            }
        }

        // if we're at the top level and we're not ending in a return, make sure to add possible null
        if ($collapse_types && !$last_stmt instanceof PhpParser\Node\Stmt\Return_ && !Checker\ScopeChecker::doesAlwaysReturnOrThrow($stmts)) {
            $return_types[] = new Type\Atomic('null');
        }

        return $return_types;
    }
}
