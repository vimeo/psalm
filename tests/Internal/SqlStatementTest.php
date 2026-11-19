<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Codebase\SqlStatement;

use function array_map;
use function implode;
use function str_replace;

/**
 * @psalm-import-type Value from SqlStatement
 */
final class SqlStatementTest extends TestCase
{
    /**
     * The query, where `%n%` is the hole n, and what it writes (`W table.column <- value`) and reads
     * (`R [keys] <- value`, `all` for all the columns under their names), a value being made of holes (`h`),
     * placeholders (`p`) and columns (`c`)
     *
     * @return iterable<string, array{string, string}>
     * @psalm-pure
     */
    public static function providerQueries(): iterable
    {
        yield 'a column' => [
            'SELECT name FROM users LIMIT 1',
            'R [name,0] <- h[] p[] c[users.name]',
        ];
        yield 'aliases of tables and columns' => [
            'SELECT u.name AS n, o.total FROM users u JOIN orders AS o ON o.user_id = u.id WHERE u.id = ?',
            'R [n,0] <- h[] p[] c[users.name]; R [total,1] <- h[] p[] c[orders.total]',
        ];
        yield 'all columns, each under its name' => [
            'SELECT * FROM users WHERE id = :id',
            'R [all] <- h[] p[] c[users.*]',
        ];
        yield 'all columns of a table' => [
            'SELECT u.*, o.total FROM users u JOIN orders o ON o.user_id = u.id',
            'R [all] <- h[] p[] c[users.*]; R [total] <- h[] p[] c[orders.total]',
        ];
        yield 'all columns of the parts of a UNION, under the names of the first part\'s' => [
            'SELECT * FROM t1 UNION SELECT * FROM t2',
            'R [any] <- h[] p[] c[t1.*,t2.*]',
        ];
        yield 'names as written' => [
            'SELECT userName, u.Bio AS "Text" FROM users u',
            'R [userName,0] <- h[] p[] c[users.username]; R [Text,1] <- h[] p[] c[users.bio]',
        ];
        yield 'the column of a write as written' => [
            'UPDATE users SET userName = ?',
            'W users.userName <- h[] p[0] c[]',
        ];
        yield 'an expression, whose name is its text' => [
            'SELECT COUNT(*), MAX(age) oldest FROM `users`',
            'R [0,any] <- h[] p[] c[]; R [oldest,1] <- h[] p[] c[users.age]',
        ];
        yield 'the type of a cast' => [
            'SELECT x::text FROM t',
            'R [0,any] <- h[] p[] c[t.x]',
        ];
        yield 'positional placeholders' => [
            'INSERT INTO users (name, email) VALUES (?, ?)',
            'W users.name <- h[] p[0] c[]; W users.email <- h[] p[1] c[]',
        ];
        yield 'named placeholders and a hole in a string' => [
            'INSERT INTO users (name, email) VALUES (:name, :email), (\'x\', \'%0%\')',
            'W users.name <- h[] p[name] c[]; W users.email <- h[] p[email] c[]; '
                . 'W users.name <- h[] p[] c[]; W users.email <- h[0] p[] c[]',
        ];
        yield 'INSERT SET' => [
            'INSERT INTO users SET name = ?, bio = CONCAT(?, bio)',
            'W users.name <- h[] p[0] c[]; W users.bio <- h[] p[1] c[users.bio]',
        ];
        yield 'INSERT SELECT, which copies columns' => [
            'INSERT INTO archive (name) SELECT name FROM users WHERE id = ?',
            'W archive.name <- h[] p[] c[users.name]',
        ];
        yield 'ON CONFLICT and RETURNING' => [
            'INSERT INTO t (a) VALUES ($1) ON CONFLICT (a) DO UPDATE SET b = excluded.a, c = $2 RETURNING id, a',
            'W t.a <- h[] p[0] c[]; W t.b <- h[] p[] c[t.a]; W t.c <- h[] p[1] c[]; '
                . 'R [id,0] <- h[] p[] c[t.id]; R [a,1] <- h[] p[] c[t.a]',
        ];
        yield 'ON DUPLICATE KEY UPDATE' => [
            'INSERT INTO t (a, b) VALUES (?, ?) ON DUPLICATE KEY UPDATE b = VALUES(b)',
            'W t.a <- h[] p[0] c[]; W t.b <- h[] p[1] c[]; W t.b <- h[] p[] c[t.b]',
        ];
        yield 'no columns named' => [
            'INSERT INTO users VALUES (?, ?)',
            'W users.* <- h[] p[0] c[]; W users.* <- h[] p[1] c[]',
        ];
        yield 'UPDATE' => [
            'UPDATE users SET name = ?, updated = NOW() WHERE id = ?',
            'W users.name <- h[] p[0] c[]; W users.updated <- h[] p[] c[]',
        ];
        yield 'placeholders of a join before SET' => [
            'UPDATE users u JOIN x ON x.k = ? SET u.name = ? WHERE u.id = ?',
            'W users.name <- h[] p[1] c[]',
        ];
        yield 'a table the query doesn\'t name' => [
            'UPDATE %0% SET name = ?',
            'W ?.name <- h[] p[0] c[]',
        ];
        yield 'a table whose name ends with a hole' => [
            'SELECT name FROM users_%0%',
            'R [name,0] <- h[] p[] c[?.name]',
        ];
        yield 'UNION' => [
            'SELECT a FROM t1 UNION SELECT b FROM t2',
            'R [a,0] <- h[] p[] c[t1.a,t2.b]',
        ];
        yield 'a subquery, whose columns are any' => [
            'SELECT (SELECT name FROM t2) AS n FROM t1',
            'R [n,0] <- h[] p[] c[?.*]',
        ];
        yield 'DELETE, which stores nothing' => [
            'DELETE FROM users WHERE id = ?',
            '',
        ];
        yield 'two statements' => [
            'INSERT INTO a (x) VALUES (?); SELECT y FROM b',
            'W a.x <- h[] p[0] c[]; R [y,0] <- h[] p[] c[b.y]',
        ];
        yield 'a WITH clause, which isn\'t modelled' => [
            'WITH x AS (SELECT 1) SELECT * FROM x',
            'null',
        ];
        yield 'an unterminated string' => [
            'SELECT \'a FROM t',
            'null',
        ];
    }

    #[DataProvider('providerQueries')]
    public function testWhatAQueryWritesAndReads(string $sql, string $expected): void
    {
        $parsed = SqlStatement::parse(str_replace('%', SqlStatement::HOLE_MARKER, $sql));

        if ($parsed === null) {
            $this->assertSame($expected, 'null');

            return;
        }

        $parts = [];
        foreach ($parsed['writes'] as $write) {
            $parts[] = 'W ' . ($write['table'] ?? '?') . '.' . ($write['column'] ?? '*') . ' <- '
                . self::formatValue($write['value']);
        }

        foreach ($parsed['reads'] as $read) {
            $keys = $read['keys'];
            if ($read['any_key']) {
                $keys[] = 'any';
            }

            if ($read['all_columns']) {
                $keys[] = 'all';
            }

            $parts[] = 'R [' . implode(',', $keys) . '] <- ' . self::formatValue($read['value']);
        }

        $this->assertSame($expected, implode('; ', $parts));
    }

    /**
     * @param Value $value
     * @psalm-pure
     */
    private static function formatValue(array $value): string
    {
        return 'h[' . implode(',', $value['holes']) . ']'
            . ' p[' . implode(',', $value['placeholders']) . ']'
            . ' c[' . implode(',', array_map(
                static fn(array $column): string => ($column[0] ?? '?') . '.' . ($column[1] ?? '*'),
                $value['columns'],
            )) . ']';
    }
}
