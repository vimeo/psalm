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
     * @return array<string>    a list of return types
     */
    public static function getReturnTypes(array $stmts, $collapse_types = false)
    {
        $return_types = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Return_) {
                $return_types[] = $stmt->returnType;

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

        var_dump($return_types);

        // expand any nested return types
        $return_types = explode('|', implode('|', $return_types));

        if ($return_types === ['']) {
            $return_types = [];
        }

        $array_return_types = array_filter($return_types, function($return_type) {
            return preg_match('/^array(\<|$)/', $return_type);
        });

        $return_types = array_flip($return_types);

        if (count($return_types) > 1 && isset($return_types['void'])) {
            unset($return_types['void']);
            $return_types['null'] = 1;
        }

        if (count($return_types) > 1 && isset($return_types['false']) && isset($return_types['bool'])) {
            unset($return_types['false']);
        }

        if ($collapse_types && isset($return_types['mixed'])) {
            return ['mixed'];
        }

        if ($collapse_types && isset($return_types['array<empty>'])) {
            unset($return_types['array<empty>']);

            // if that special entity was the only array entry
            if (count($array_return_types) === 1) {
                $return_types['array'] = 1;
            }
        }

        $return_type_keys = array_keys($return_types);

        if ($collapse_types && $return_type_keys === ['false']) {
            $return_type_keys = ['bool'];
        }

        if ($collapse_types) {
            $return_type_keys = TypeChecker::reduceTypes($return_type_keys);
        }

        return $return_type_keys;
    }
}
