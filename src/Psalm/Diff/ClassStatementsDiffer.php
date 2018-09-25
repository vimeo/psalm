<?php

namespace Psalm\Diff;

use PhpParser;

/**
 * @internal
 */
class ClassStatementsDiffer extends Differ
{
    /** @var PhpParser\PrettyPrinter\Standard|null */
    private static $pretty_printer;

    /**
     * Calculate diff (edit script) from $a to $b.
     *
     * @param PhpParser\Node\Stmt[] $a
     * @param PhpParser\Node\Stmt[] $b New array
     *
     * @return array{0:array<int, string>, 1:array<int, string>}
     */
    public static function diff(string $name, array $a, array $b, string $a_code, string $b_code)
    {
        list($trace, $x, $y, $bc) = self::calculateTrace(
            function (
                PhpParser\Node\Stmt $a,
                PhpParser\Node\Stmt $b,
                string $a_code,
                string $b_code,
                bool &$body_change = false
            ) : bool {
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
                    return true;
                }

                if (!$signature_change
                    && substr($a_code, $a_start, $a_comments_end - $a_start)
                    !== substr($b_code, $b_start, $b_comments_end - $b_start)
                ) {
                    $signature_change = true;
                }

                if (!self::$pretty_printer) {
                    self::$pretty_printer = new PhpParser\PrettyPrinter\Standard;
                }

                if ($a instanceof PhpParser\Node\Stmt\ClassMethod && $b instanceof PhpParser\Node\Stmt\ClassMethod) {
                    if ((string) $a->name !== (string) $b->name) {
                        return false;
                    }

                    $a_stmts = $a->stmts;
                    $a->stmts = [];
                    $b_stmts = $b->stmts;
                    $b->stmts = [];

                    $body_change = $a_stmts !== $b_stmts
                        && self::$pretty_printer->prettyPrint($a_stmts ?: [])
                            !== self::$pretty_printer->prettyPrint($b_stmts ?: []);
                    $signature_change = $signature_change
                        || self::$pretty_printer->prettyPrint([$a]) !== self::$pretty_printer->prettyPrint([$b]);

                    $a->stmts = $a_stmts;
                    $b->stmts = $b_stmts;
                } else {
                    $signature_change = $signature_change
                        || self::$pretty_printer->prettyPrint([$a]) !== self::$pretty_printer->prettyPrint([$b]);
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
            }
        };

        return [$keep, $keep_signature];
    }
}
