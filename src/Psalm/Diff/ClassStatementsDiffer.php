<?php

namespace Psalm\Diff;

use PhpParser;

/**
 * @internal
 */
class ClassStatementsDiffer extends Differ
{
    /**
     * Calculate diff (edit script) from $a to $b.
     *
     * @param PhpParser\Node\Stmt[] $a
     * @param PhpParser\Node\Stmt[] $b New array
     *
     * @return array{
     *      0: array<int, string>,
     *.     1: array<int, string>,
     *      2: array<int, string>,
     *      3: array<int, array{0: int, 1: int, 2: int, 3: int}>
     * }
     */
    public static function diff(string $name, array $a, array $b, string $a_code, string $b_code)
    {
        $diff_map = [];

        list($trace, $x, $y, $bc) = self::calculateTrace(
            function (
                PhpParser\Node\Stmt $a,
                PhpParser\Node\Stmt $b,
                string $a_code,
                string $b_code,
                bool &$body_change = false
            ) use (&$diff_map) : bool {
                if (get_class($a) !== get_class($b)) {
                    return false;
                }

                $a_start = (int)$a->getAttribute('startFilePos');
                $a_end = (int)$a->getAttribute('endFilePos');

                $b_start = (int)$b->getAttribute('startFilePos');
                $b_end = (int)$b->getAttribute('endFilePos');

                $a_comments_end = $a_start;
                $b_comments_end = $b_start;

                $a_comments = $a->getComments();
                $b_comments = $b->getComments();

                $signature_change = false;
                $body_change = false;

                if ($a_comments) {
                    if (!$b_comments) {
                        $signature_change = true;
                    }

                    $a_start = $a_comments[0]->getFilePos();
                }

                if ($b_comments) {
                    if (!$a_comments) {
                        $signature_change = true;
                    }

                    $b_start = $b_comments[0]->getFilePos();
                }

                $a_size = $a_end - $a_start;
                $b_size = $b_end - $b_start;

                if (substr($a_code, $a_start, $a_size) === substr($b_code, $b_start, $b_size)) {
                    $diff_map[] = [$a_start, $a_end, $b_start - $a_start, $b->getLine() - $a->getLine()];

                    return true;
                }

                if (!$signature_change
                    && substr($a_code, $a_start, $a_comments_end - $a_start)
                    !== substr($b_code, $b_start, $b_comments_end - $b_start)
                ) {
                    $signature_change = true;
                }

                if ($a instanceof PhpParser\Node\Stmt\ClassMethod && $b instanceof PhpParser\Node\Stmt\ClassMethod) {
                    if ((string) $a->name !== (string) $b->name) {
                        return false;
                    }

                    $a_stmts_start = $a->stmts ? (int) $a->stmts[0]->getAttribute('startFilePos') : $a_end;
                    $b_stmts_start = $b->stmts ? (int) $b->stmts[0]->getAttribute('startFilePos') : $b_end;

                    $body_change = substr($a_code, $a_stmts_start, $a_end - $a_stmts_start)
                        !== substr($b_code, $b_stmts_start, $b_end - $b_stmts_start);

                    $signature_change = $signature_change
                        || substr($a_code, $a_start, $a_stmts_start - $a_start)
                            !== substr($b_code, $b_start, $b_stmts_start - $b_start);
                } elseif ($a instanceof PhpParser\Node\Stmt\Property && $b instanceof PhpParser\Node\Stmt\Property) {
                    if (count($a->props) !== 1 || count($b->props) !== 1) {
                        return false;
                    }

                    if ((string) $a->props[0]->name !== (string) $b->props[0]->name) {
                        return false;
                    }

                    $body_change = substr($a_code, $a_comments_end, $a_end - $a_comments_end)
                        !== substr($b_code, $b_comments_end, $b_end - $b_comments_end);
                } else {
                    $signature_change = true;
                }

                if (!$signature_change && !$body_change) {
                    $diff_map[] = [$a_start, $a_end, $b_start - $a_start, $b->getLine() - $a->getLine()];
                }

                return !$signature_change;
            },
            $a,
            $b,
            $a_code,
            $b_code
        );

        $diff = self::extractDiff($trace, $x, $y, $a, $b, $bc);

        $keep = [];
        $keep_signature = [];
        $delete = [];

        foreach ($diff as $diff_elem) {
            if ($diff_elem->type === DiffElem::TYPE_KEEP) {
                if ($diff_elem->old instanceof PhpParser\Node\Stmt\ClassMethod) {
                    $keep[] = strtolower($name) . '::' . strtolower((string) $diff_elem->old->name);
                } elseif ($diff_elem->old instanceof PhpParser\Node\Stmt\Property) {
                    foreach ($diff_elem->old->props as $prop) {
                        $keep[] = strtolower($name) . '::$' . $prop->name;
                    }
                } elseif ($diff_elem->old instanceof PhpParser\Node\Stmt\ClassConst) {
                    foreach ($diff_elem->old->consts as $const) {
                        $keep[] = strtolower($name) . '::' . $const->name;
                    }
                }
            } elseif ($diff_elem->type === DiffElem::TYPE_KEEP_SIGNATURE) {
                if ($diff_elem->old instanceof PhpParser\Node\Stmt\ClassMethod) {
                    $keep_signature[] = strtolower($name) . '::' . strtolower((string) $diff_elem->old->name);
                } elseif ($diff_elem->old instanceof PhpParser\Node\Stmt\Property) {
                    foreach ($diff_elem->old->props as $prop) {
                        $keep_signature[] = strtolower($name) . '::$' . $prop->name;
                    }
                }
            } elseif ($diff_elem->type === DiffElem::TYPE_REMOVE) {
                if ($diff_elem->old instanceof PhpParser\Node\Stmt\ClassMethod) {
                    $delete[] = strtolower($name) . '::' . strtolower((string) $diff_elem->old->name);
                } elseif ($diff_elem->old instanceof PhpParser\Node\Stmt\Property) {
                    foreach ($diff_elem->old->props as $prop) {
                        $delete[] = strtolower($name) . '::$' . $prop->name;
                    }
                } elseif ($diff_elem->old instanceof PhpParser\Node\Stmt\ClassConst) {
                    foreach ($diff_elem->old->consts as $const) {
                        $delete[] = strtolower($name) . '::' . $const->name;
                    }
                }
            }
        };

        return [$keep, $keep_signature, $delete, $diff_map];
    }
}
