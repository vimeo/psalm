<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\ExecutionEnvironment;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\ExecutionEnvironment\Env;
use Psalm\Tests\TestCase;

final class EnvTest extends TestCase
{
    public function testGetReturnsTheFirstNonEmptyString(): void
    {
        $env = new Env(['EMPTY' => '', 'LIST' => ['a'], 'NUMBER' => 5, 'SECOND' => 'two', 'THIRD' => 'three']);

        self::assertSame('two', $env->get('MISSING', 'EMPTY', 'LIST', 'NUMBER', 'SECOND', 'THIRD'));
        self::assertNull($env->get('MISSING', 'EMPTY', 'LIST', 'NUMBER'));
        self::assertNull($env->get());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, bool}>
     * @psalm-pure
     */
    public static function provideFlag(): iterable
    {
        yield 'true' => [['FLAG' => 'true'], true];
        yield 'True' => [['FLAG' => 'True'], true];
        yield 'one' => [['FLAG' => '1'], true];
        yield 'any text' => [['FLAG' => 'yes'], true];
        yield 'false' => [['FLAG' => 'false'], false];
        yield 'False' => [['FLAG' => 'False'], false];
        yield 'zero' => [['FLAG' => '0'], false];
        yield 'empty' => [['FLAG' => ''], false];
        yield 'missing' => [[], false];
        yield 'not a string' => [['FLAG' => true], false];
    }

    /**
     * @param array<string, mixed> $values
     */
    #[DataProvider('provideFlag')]
    public function testIsTruthy(array $values, bool $expected): void
    {
        self::assertSame($expected, (new Env($values))->isTruthy('FLAG'));
    }

    /**
     * @return iterable<string, array{string|null, string|null, string|null}>
     * @psalm-pure
     */
    public static function provideRepository(): iterable
    {
        yield 'a slug' => ['psalm/example', 'psalm', 'example'];
        yield 'dots and dashes' => ['psalm-dev/psalm.dev', 'psalm-dev', 'psalm.dev'];
        yield 'no slash' => ['psalm', null, null];
        yield 'no name' => ['psalm/', null, null];
        yield 'no owner' => ['/example', null, null];
        yield 'too many parts' => ['g/psalm/example', null, null];
        yield 'empty' => ['', null, null];
        yield 'missing' => [null, null, null];
    }

    #[DataProvider('provideRepository')]
    public function testRepositoryIsAnOwnerAndANameOrNothing(?string $slug, ?string $owner, ?string $name): void
    {
        $repository = (new Env($slug === null ? [] : ['SLUG' => $slug]))->repository('SLUG');

        self::assertSame($owner, $repository?->owner);
        self::assertSame($name, $repository?->name);
    }

    public function testPickKeepsTheNonEmptyStringsOnly(): void
    {
        $env = new Env(['A' => 'a', 'B' => '', 'C' => ['c'], 'D' => 'd']);

        self::assertSame(['A' => 'a', 'D' => 'd'], $env->pick('A', 'B', 'C', 'D', 'MISSING'));
    }
}
