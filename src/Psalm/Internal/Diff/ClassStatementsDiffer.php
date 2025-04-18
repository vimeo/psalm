<?php

declare(strict_types=1);

namespace Psalm\Internal\Diff;

use PhpParser;
use UnexpectedValueException;

use function count;
use function is_string;
use function str_contains;
use function strtolower;
use function substr;
use function trim;

/**
 * @internal
 */
final class ClassStatementsDiffer extends AstDiffer
{
    /**
     * Calculate diff (edit script) from $a to $b.
     *
     * @param array<int, PhpParser\Node\Stmt> $a
     * @param array<int, PhpParser\Node\Stmt> $b
     * @return array{
     *      0: list<string>,
     *      1: list<string>,
     *      2: list<string>,
     *      3: array<int, array{int, int, int, int}>,
     *      4: list<array{int, int}>
     * }
     */
    public static function diff(string $name, array $a, array $b, string $a_code, string $b_code): array
    {
        $diff_map = [];

        [$trace, $x, $y, $bc] = self::calculateTrace(
            static function (
                PhpParser\Node\Stmt $a,
                PhpParser\Node\Stmt $b,
                string $a_code,
                string $b_code,
                bool &$body_change = false,
            ) use (&$diff_map): bool {
                if ($a::class !== $b::class) {
                    return false;
                }

                $a_start = (int)$a->getAttribute('startFilePos');
                $a_end = (int)$a->getAttribute('endFilePos');

                $b_start = (int)$b->getAttribute('startFilePos');
                $b_end = (int)$b->getAttribute('endFilePos');

                $a_comments_end = $a_start;
                $b_comments_end = $b_start;

                /** @var list<PhpParser\Comment> */
                $a_comments = $a->getComments();
                /** @var list<PhpParser\Comment> */
                $b_comments = $b->getComments();

                $signature_change = false;
                $body_change = false;

                if ($a_comments) {
                    if (!$b_comments) {
                        $signature_change = true;
                    }

                    $a_start = $a_comments[0]->getStartFilePos();
                }

                if ($b_comments) {
                    if (!$a_comments) {
                        $signature_change = true;
                    }

                    $b_start = $b_comments[0]->getStartFilePos();
                }

                $a_size = $a_end - $a_start;
                $b_size = $b_end - $b_start;

                if ($a_size === $b_size
                    && substr($a_code, $a_start, $a_size) === substr($b_code, $b_start, $b_size)
                ) {
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

                    if ($a->stmts) {
                        $first_stmt = $a->stmts[0];
                        $a_stmts_start = (int) $first_stmt->getAttribute('startFilePos');

                        if ($a_stmt_comments = $first_stmt->getComments()) {
                            $a_stmts_start = $a_stmt_comments[0]->getStartFilePos();
                        }
                    } else {
                        $a_stmts_start = $a_end;
                    }

                    if ($b->stmts) {
                        $first_stmt = $b->stmts[0];
                        $b_stmts_start = (int) $first_stmt->getAttribute('startFilePos');

                        if ($b_stmt_comments = $first_stmt->getComments()) {
                            $b_stmts_start = $b_stmt_comments[0]->getStartFilePos();
                        }
                    } else {
                        $b_stmts_start = $b_end;
                    }

                    $a_body_size = $a_end - $a_stmts_start;
                    $b_body_size = $b_end - $b_stmts_start;

                    $body_change = $a_body_size !== $b_body_size
                        || substr($a_code, $a_stmts_start, $a_end - $a_stmts_start)
                            !== substr($b_code, $b_stmts_start, $b_end - $b_stmts_start);

                    if (!$signature_change) {
                        $a_signature = substr($a_code, $a_start, $a_stmts_start - $a_start);
                        $b_signature = substr($b_code, $b_start, $b_stmts_start - $b_start);

                        if ($a_signature !== $b_signature) {
                            $a_signature = trim($a_signature);
                            $b_signature = trim($b_signature);

                            if (!str_contains($a_signature, $b_signature)
                                && !str_contains($b_signature, $a_signature)
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

                    if ($a->type xor $b->type) {
                        return false;
                    }

                    if ($a->type && $b->type) {
                        $a_type_start = (int) $a->type->getAttribute('startFilePos');
                        $a_type_end = (int) $a->type->getAttribute('endFilePos');
                        $b_type_start = (int) $b->type->getAttribute('startFilePos');
                        $b_type_end = (int) $b->type->getAttribute('endFilePos');
                        if (substr($a_code, $a_type_start, $a_type_end - $a_type_start + 1)
                            !== substr($b_code, $b_type_start, $b_type_end - $b_type_start + 1)
                        ) {
                            return false;
                        }
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
            $b_code,
        );

        $diff = self::extractDiff($trace, $x, $y, $a, $b, $bc);

        $keep = [];
        $keep_signature = [];
        $add_or_delete = [];
        $deletion_ranges = [];

        $name_lc = strtolower($name);
        foreach ($diff as $diff_elem) {
            if ($diff_elem->type === DiffElem::TYPE_KEEP) {
                if ($diff_elem->old instanceof PhpParser\Node\Stmt\ClassMethod) {
                    $keep[] = $name_lc . '::' . strtolower((string) $diff_elem->old->name);
                } elseif ($diff_elem->old instanceof PhpParser\Node\Stmt\Property) {
                    foreach ($diff_elem->old->props as $prop) {
                        $keep[] = $name_lc . '::$' . $prop->name;
                    }
                } elseif ($diff_elem->old instanceof PhpParser\Node\Stmt\ClassConst) {
                    foreach ($diff_elem->old->consts as $const) {
                        $keep[] = $name_lc . '::' . $const->name;
                    }
                } elseif ($diff_elem->old instanceof PhpParser\Node\Stmt\TraitUse) {
                    foreach ($diff_elem->old->traits as $trait) {
                        $keep[] = $name_lc . '&' . strtolower((string) $trait->getAttribute('resolvedName'));
                    }
                }
            } elseif ($diff_elem->type === DiffElem::TYPE_KEEP_SIGNATURE) {
                if ($diff_elem->old instanceof PhpParser\Node\Stmt\ClassMethod) {
                    $keep_signature[] = $name_lc . '::' . strtolower((string) $diff_elem->old->name);
                } elseif ($diff_elem->old instanceof PhpParser\Node\Stmt\Property) {
                    foreach ($diff_elem->old->props as $prop) {
                        $keep_signature[] = $name_lc . '::$' . $prop->name;
                    }
                }
            } elseif ($diff_elem->type === DiffElem::TYPE_REMOVE || $diff_elem->type === DiffElem::TYPE_ADD) {
                /** @var PhpParser\Node */
                $affected_elem = $diff_elem->type === DiffElem::TYPE_REMOVE ? $diff_elem->old : $diff_elem->new;
                if ($affected_elem instanceof PhpParser\Node\Stmt\ClassMethod) {
                    $method_name = strtolower((string) $affected_elem->name);
                    $add_or_delete[] = $name_lc . '::' . $method_name;
                    if ($method_name === '__construct') {
                        foreach ($affected_elem->getParams() as $param) {
                            if (!$param->flags || !$param->var instanceof PhpParser\Node\Expr\Variable) {
                                continue;
                            }
                            if ($param->var instanceof PhpParser\Node\Expr\Error || !is_string($param->var->name)) {
                                throw new UnexpectedValueException('Not expecting param name to be non-string');
                            }
                            $add_or_delete[] = $name_lc . '::$' . $param->var->name;
                        }
                    }
                } elseif ($affected_elem instanceof PhpParser\Node\Stmt\Property) {
                    foreach ($affected_elem->props as $prop) {
                        $add_or_delete[] = $name_lc . '::$' . $prop->name;
                    }
                } elseif ($affected_elem instanceof PhpParser\Node\Stmt\ClassConst) {
                    foreach ($affected_elem->consts as $const) {
                        $add_or_delete[] = $name_lc . '::' . $const->name;
                    }
                } elseif ($affected_elem instanceof PhpParser\Node\Stmt\TraitUse) {
                    foreach ($affected_elem->traits as $trait) {
                        $add_or_delete[] = $name_lc . '&' . strtolower((string) $trait->getAttribute('resolvedName'));
                    }
                }

                if ($diff_elem->type === DiffElem::TYPE_REMOVE) {
                    if ($doc = $affected_elem->getDocComment()) {
                        $start = $doc->getStartFilePos();
                    } else {
                        $start = (int)$affected_elem->getAttribute('startFilePos');
                    }

                    $deletion_ranges[] = [
                        $start,
                        (int)$affected_elem->getAttribute('endFilePos'),
                    ];
                }
            }
        }

        /** @var array<int, array{int, int, int, int}> $diff_map */
        return [$keep, $keep_signature, $add_or_delete, $diff_map, $deletion_ranges];
    }
}
