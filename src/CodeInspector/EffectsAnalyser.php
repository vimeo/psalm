<?php

namespace CodeInspector;

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
     * @param  array<PhpParser\Node\Stmt>  $stmts
     * @return array<AtomicType>    a list of return types
     */
    public static function getReturnTypes(array $stmts, $collapse_types = false)
    {
        $return_types = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                $return_types = array_merge($stmt->returnType->types, $return_types);

                break;

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

        return $return_types;
    }
}
