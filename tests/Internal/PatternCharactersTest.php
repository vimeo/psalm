<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Type\PatternCharacters;
use Psalm\Type\TaintKind;

final class PatternCharactersTest extends TestCase
{
    /**
     * @return iterable<string, array{string, int, bool}>
     * @psalm-pure
     */
    public static function providerPatterns(): iterable
    {
        yield 'a class of letters' => ['/^[a-z]+$/', TaintKind::INPUT_HTML, true];
        yield 'a negated class' => ['/^[^<>]*$/', TaintKind::INPUT_HTML, true];
        yield 'a negated class holding a negated escape' => ['/^[^\D<]+$/', TaintKind::INPUT_HTML, false];
        yield 'a quantifier' => ['/^a{2,3}$/', TaintKind::INPUT_HTML, true];
        yield 'a brace that is a literal' => ['/^a{<}$/', TaintKind::INPUT_HTML, false];
        yield 'vertical whitespace' => ['/^[a\v]+$/D', TaintKind::INPUT_HEADER, false];
        yield 'an octal escape' => ['/^\012$/D', TaintKind::INPUT_HEADER, false];
        yield 'a dot, which can make the parent directory' => ['/^[a-z.]+$/', TaintKind::INPUT_FILE, false];
        yield 'no directory separator nor dot' => ['/^[a-z_-]+$/', TaintKind::INPUT_FILE, true];
        yield 'a trailing newline, which ends a shell command' => ['/^[a-z]+$/', TaintKind::INPUT_SHELL, false];
        yield 'no trailing newline' => ['/^[a-z]+$/D', TaintKind::INPUT_SHELL, true];
        yield 'a space, which can make an unquoted XPath predicate' => ['/^[a-z0-9 ]+$/', TaintKind::INPUT_XPATH, false];
        yield 'a trailing newline, which nothing in an XPath query follows' => ['/^\d+$/', TaintKind::INPUT_XPATH, true];
        yield 'not anchored' => ['/[a-z]+/', TaintKind::INPUT_HTML, false];
        yield 'anchored in one alternative only' => ['/^a|b$/', TaintKind::INPUT_HTML, false];
        yield 'anchored at each line' => ['/^[a-z]+$/m', TaintKind::INPUT_HTML, false];
        yield 'a lookahead' => ['/^(?=a)[a-z]+$/', TaintKind::INPUT_HTML, false];
        yield 'a case-insensitive alternative' => ['/^(?:asc|desc)$/i', TaintKind::INPUT_SQL, true];
    }

    #[DataProvider('providerPatterns')]
    public function testTheTaintsAValueMatchingAPatternCantCarry(string $pattern, int $taint, bool $removed): void
    {
        $this->assertSame($removed, (PatternCharacters::getTaintsRemoved($pattern) & $taint) !== 0);
    }
}
