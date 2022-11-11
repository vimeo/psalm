<?php

namespace Psalm\Internal\Diff;

use PhpParser;

use function end;
use function get_class;
use function substr;

/**
 * @internal
 */
class FileStatementsDiffer extends AstDiffer
{
    /**
     * Calculate diff (edit script) from $a to $b.
     * @param list<PhpParser\Node\Stmt> $a
     * @param list<PhpParser\Node\Stmt> $b
     *
     * @return array{
     *      0: list<string>,
     *      1: list<string>,
     *      2: list<string>,
     *      3: list<array{int, int, int, int}>,
     *      4: list<array{int, int}>
     * }
     */
    public static function diff(array $a, array $b, string $a_code, string $b_code): array
    {
        [$trace, $x, $y, $bc] = self::calculateTrace(
            static function (
                PhpParser\Node\Stmt $a,
                PhpParser\Node\Stmt $b,
                string $a_code,
                string $b_code
            ): bool {
                if (get_class($a) !== get_class($b)) {
                    return false;
                }

                if (($a instanceof PhpParser\Node\Stmt\Namespace_ && $b instanceof PhpParser\Node\Stmt\Namespace_)
                    || ($a instanceof PhpParser\Node\Stmt\Class_ && $b instanceof PhpParser\Node\Stmt\Class_)
                    || ($a instanceof PhpParser\Node\Stmt\Interface_ && $b instanceof PhpParser\Node\Stmt\Interface_)
                    || ($a instanceof PhpParser\Node\Stmt\Trait_ && $b instanceof PhpParser\Node\Stmt\Trait_)
                ) {
                    return (string)$a->name === (string)$b->name;
                }

                if (($a instanceof PhpParser\Node\Stmt\Use_
                        && $b instanceof PhpParser\Node\Stmt\Use_)
                    || ($a instanceof PhpParser\Node\Stmt\GroupUse
                        && $b instanceof PhpParser\Node\Stmt\GroupUse)
                ) {
                    $a_start = (int)$a->getAttribute('startFilePos');
                    $a_end = (int)$a->getAttribute('endFilePos');

                    $b_start = (int)$b->getAttribute('startFilePos');
                    $b_end = (int)$b->getAttribute('endFilePos');

                    $a_size = $a_end - $a_start;
                    $b_size = $b_end - $b_start;

                    if (substr($a_code, $a_start, $a_size) === substr($b_code, $b_start, $b_size)) {
                        return true;
                    }
                }

                return false;
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
        $diff_map = [];
        $deletion_ranges = [];

        foreach ($diff as $diff_elem) {
            if ($diff_elem->type === DiffElem::TYPE_KEEP) {
                if ($diff_elem->old instanceof PhpParser\Node\Stmt\Namespace_
                        && $diff_elem->new instanceof PhpParser\Node\Stmt\Namespace_
                ) {
                    $namespace_keep = NamespaceStatementsDiffer::diff(
                        (string) $diff_elem->old->name,
                        $diff_elem->old->stmts,
                        $diff_elem->new->stmts,
                        $a_code,
                        $b_code
                    );

                    $keep = [...$keep, ...$namespace_keep[0]];
                    $keep_signature = [...$keep_signature, ...$namespace_keep[1]];
                    $add_or_delete = [...$add_or_delete, ...$namespace_keep[2]];
                    $diff_map = [...$diff_map, ...$namespace_keep[3]];
                    $deletion_ranges = [...$deletion_ranges, ...$namespace_keep[4]];
                } elseif (($diff_elem->old instanceof PhpParser\Node\Stmt\Class_
                        && $diff_elem->new instanceof PhpParser\Node\Stmt\Class_)
                    || ($diff_elem->old instanceof PhpParser\Node\Stmt\Interface_
                        && $diff_elem->new instanceof PhpParser\Node\Stmt\Interface_)
                    || ($diff_elem->old instanceof PhpParser\Node\Stmt\Trait_
                        && $diff_elem->new instanceof PhpParser\Node\Stmt\Trait_)
                ) {
                    $class_keep = ClassStatementsDiffer::diff(
                        (string) $diff_elem->old->name,
                        $diff_elem->old->stmts,
                        $diff_elem->new->stmts,
                        $a_code,
                        $b_code
                    );

                    $keep = [...$keep, ...$class_keep[0]];
                    $keep_signature = [...$keep_signature, ...$class_keep[1]];
                    $add_or_delete = [...$add_or_delete, ...$class_keep[2]];
                    $diff_map = [...$diff_map, ...$class_keep[3]];
                    $deletion_ranges = [...$deletion_ranges, ...$class_keep[4]];
                }
            } elseif ($diff_elem->type === DiffElem::TYPE_REMOVE) {
                if ($diff_elem->old instanceof PhpParser\Node\Stmt\Use_
                    || $diff_elem->old instanceof PhpParser\Node\Stmt\GroupUse
                ) {
                    foreach ($diff_elem->old->uses as $use) {
                        if ($use->alias) {
                            $add_or_delete[] = 'use:' . (string) $use->alias;
                        } else {
                            $name_parts = $use->name->parts;

                            $add_or_delete[] = 'use:' . end($name_parts);
                        }
                    }
                } elseif ($diff_elem->old instanceof PhpParser\Node
                    && !$diff_elem->old instanceof PhpParser\Node\Stmt\Namespace_
                ) {
                    if ($doc = $diff_elem->old->getDocComment()) {
                        $start = $doc->getStartFilePos();
                    } else {
                        $start = (int)$diff_elem->old->getAttribute('startFilePos');
                    }

                    $deletion_ranges[] = [
                        $start,
                        (int)$diff_elem->old->getAttribute('endFilePos')
                    ];
                }
            } elseif ($diff_elem->type === DiffElem::TYPE_ADD) {
                if ($diff_elem->new instanceof PhpParser\Node\Stmt\Use_
                    || $diff_elem->new instanceof PhpParser\Node\Stmt\GroupUse
                ) {
                    foreach ($diff_elem->new->uses as $use) {
                        if ($use->alias) {
                            $add_or_delete[] = 'use:' . (string) $use->alias;
                        } else {
                            $name_parts = $use->name->parts;

                            $add_or_delete[] = 'use:' . end($name_parts);
                        }
                    }
                }
            }
        }

        return [$keep, $keep_signature, $add_or_delete, $diff_map, $deletion_ranges];
    }
}
