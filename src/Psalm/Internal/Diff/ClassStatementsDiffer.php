<?php

namespace Psalm\Internal\Diff;

use PhpParser;

/**
 * @internal
 */
class ClassStatementsDiffer extends AstDiffer
{
    /**
     * Calculate diff (edit script) from $a to $b.
     *
     * @param string $name
     * @param string $a_code
     * @param string $b_code
     * @param array<int, PhpParser\Node\Stmt> $a
     * @param array<int, PhpParser\Node\Stmt> $b
     *
     * @return array{
     *      0: array<int, string>,
     *      1: array<int, string>,
     *      2: array<int, string>,
     *      3: array<int, array{0: int, 1: int, 2: int, 3: int}>
     * }
     */
    public static function diff($name, array $a, array $b, $a_code, $b_code)
    {
        $diff_map = [];

        list($trace, $x, $y, $bc) = self::calculateTrace(
            /**
             * @param string $a_code
             * @param string $b_code
             * @param bool $body_change
             *
             * @return bool
             */
            function (
                PhpParser\Node\Stmt $a,
                PhpParser\Node\Stmt $b,
                $a_code,
                $b_code,
                &$body_change = false
            ) use (&$diff_map) {
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
                    $start_diff = $b_start - $a_start;
                    $line_diff = $b->getLine() - $a->getLine();

                    $diff_map[] = [$a_start, $a_end, $start_diff, $line_diff];

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

                    if (!$signature_change) {
                        $a_signature = substr($a_code, $a_start, $a_stmts_start - $a_start);
                        $b_signature = substr($b_code, $b_start, $b_stmts_start - $b_start);

                        if ($a_signature !== $b_signature) {
                            $a_signature = trim($a_signature);
                            $b_signature = trim($b_signature);

                            if (strpos($a_signature, $b_signature) === false
                                && strpos($b_signature, $a_signature) === false
                            ) {
                                $signature_change = true;
                            }
                        }
                    }
                } elseif ($a instanceof PhpParser\Node\Stmt\Property && $b instanceof PhpParser\Node\Stmt\Property) {
                    if (count($a->props) !== 1 || count($b->props) !== 1) {
                        return false;
                    }

                    if ((string) $a->props[0]->name !== (string) $b->props[0]->name || $a->flags !== $b->flags) {
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
        $add_or_delete = [];

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
                } elseif ($diff_elem->old instanceof PhpParser\Node\Stmt\TraitUse) {
                    foreach ($diff_elem->old->traits as $trait) {
                        $keep[] = strtolower((string) $trait->getAttribute('resolvedName')) . '::*';
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
            } elseif ($diff_elem->type === DiffElem::TYPE_REMOVE || $diff_elem->type === DiffElem::TYPE_ADD) {
                /** @psalm-suppress MixedAssignment */
                $affected_elem = $diff_elem->type === DiffElem::TYPE_REMOVE ? $diff_elem->old : $diff_elem->new;
                if ($affected_elem instanceof PhpParser\Node\Stmt\ClassMethod) {
                    $add_or_delete[] = strtolower($name) . '::' . strtolower((string) $affected_elem->name);
                } elseif ($affected_elem instanceof PhpParser\Node\Stmt\Property) {
                    foreach ($affected_elem->props as $prop) {
                        $add_or_delete[] = strtolower($name) . '::$' . $prop->name;
                    }
                } elseif ($affected_elem instanceof PhpParser\Node\Stmt\ClassConst) {
                    foreach ($affected_elem->consts as $const) {
                        $add_or_delete[] = strtolower($name) . '::' . $const->name;
                    }
                } elseif ($affected_elem instanceof PhpParser\Node\Stmt\TraitUse) {
                    foreach ($affected_elem->traits as $trait) {
                        $add_or_delete[] = strtolower((string) $trait->getAttribute('resolvedName')) . '::*';
                    }
                }
            }
        };

        return [$keep, $keep_signature, $add_or_delete, $diff_map];
    }
}
