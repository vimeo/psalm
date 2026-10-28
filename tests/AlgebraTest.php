<?php

declare(strict_types=1);

namespace Psalm\Tests;

use AssertionError;
use PhpParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\IsIdentical;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Storage\Assertion\IsNotIdentical;
use Psalm\Storage\Assertion\IsType;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TString;

use function array_map;
use function ini_get;
use function spl_object_id;

final class AlgebraTest extends TestCase
{
    public function testNegateFormula(): void
    {
        $formula = [
            new Clause(['$a' => ['!falsy' => new Truthy()]], 1, 1),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(1, $negated_formula);
        $this->assertSame('!$a', (string)$negated_formula[0]);

        $formula = [
            new Clause(['$a' => ['!falsy' => new Truthy()], '$b' => ['!falsy' => new Truthy()]], 1, 1),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(2, $negated_formula);
        $this->assertSame('!$a', (string)$negated_formula[0]);
        $this->assertSame('!$b', (string)$negated_formula[1]);

        $formula = [
            new Clause(['$a' => ['!falsy' => new Truthy()]], 1, 1),
            new Clause(['$b' => ['!falsy' => new Truthy()]], 1, 2),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(1, $negated_formula);
        $this->assertSame('(!$a) || (!$b)', (string)$negated_formula[0]);

        $a1 = new IsType(new TInt());
        $a2 = new IsType(new TString());

        $formula = [
            new Clause(
                [
                    '$a' => [(string)$a1 => $a1, (string)$a2 => $a2],
                    '$b' => ['!falsy' => new Truthy()],
                ],
                1,
                1,
            ),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(3, $negated_formula);
        $this->assertSame('$a is not int', (string)$negated_formula[0]);
        $this->assertSame('$a is not string', (string)$negated_formula[1]);
        $this->assertSame('!$b', (string)$negated_formula[2]);
    }

    public function testNegateFormulaWithUnreconcilableTerm(): void
    {
        $a1 = new IsType(new TInt());
        $formula = [
            new Clause(['$a' => [(string)$a1 => $a1]], 1, 1),
            new Clause(['$b' => [(string)$a1 => $a1]], 1, 2, false, false),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(1, $negated_formula);
        $this->assertSame('$a is not int', (string)$negated_formula[0]);
    }

    public function testCombinatorialExpansion(): void
    {
        $dnf = '<?php ($b0 === true && $b4 === true && $b8 === true)
                  || ($b0 === true && $b1 === true && $b2 === true)
                  || ($b0 === true && $b3 === true && $b6 === true)
                  || ($b1 === true && $b4 === true && $b7 === true)
                  || ($b2 === true && $b5 === true && $b8 === true)
                  || ($b2 === true && $b4 === true && $b6 === true)
                  || ($b3 === true && $b4 === true && $b5 === true)
                  || ($b6 === true && $b7 === true && $b8 === true);';

        $has_errors = false;

        $dnf_stmt = StatementsProvider::parseStatements($dnf, 7_04_00, $has_errors)[0];

        $this->assertInstanceOf(PhpParser\Node\Stmt\Expression::class, $dnf_stmt);

        $file_analyzer = new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $file_analyzer->context = new Context();
        $statements_analyzer = new StatementsAnalyzer($file_analyzer, new NodeDataProvider(), false);

        $dnf_clauses = FormulaGenerator::getFormula(
            spl_object_id($dnf_stmt->expr),
            spl_object_id($dnf_stmt->expr),
            $dnf_stmt->expr,
            null,
            $statements_analyzer,
        );

        $this->assertCount(6_561, $dnf_clauses);

        $simplified_dnf_clauses = Algebra::simplifyCNF($dnf_clauses);

        $this->assertCount(23, $simplified_dnf_clauses);
    }

    public function testContainsClause(): void
    {
        $this->assertTrue(
            (new Clause(
                [
                    '$a' => ['!falsy' => new Truthy()],
                    '$b' => ['!falsy' => new Truthy()],
                ],
                1,
                1,
            ))->contains(
                new Clause(
                    [
                        '$a' => ['!falsy' => new Truthy()],
                    ],
                    1,
                    1,
                ),
            ),
        );

        $this->assertFalse(
            (new Clause(
                [
                    '$a' => ['!falsy' => new Truthy()],
                ],
                1,
                1,
            ))->contains(
                new Clause(
                    [
                        '$a' => ['!falsy' => new Truthy()],
                        '$b' => ['!falsy' => new Truthy()],
                    ],
                    1,
                    1,
                ),
            ),
        );
    }

    public function testSimplifySimpleCNF(): void
    {
        $formula = [
            new Clause(['$a' => ['!falsy' => new Truthy()]], 1, 1),
            new Clause(['$a' => ['falsy' => new Falsy()], '$b' => ['falsy' => new Falsy()]], 1, 2),
        ];

        $simplified_formula = Algebra::simplifyCNF($formula);

        $this->assertCount(2, $simplified_formula);
        $this->assertSame('$a', (string)$simplified_formula[0]);
        $this->assertSame('!$b', (string)$simplified_formula[1]);
    }

    public function testSimplifyCNFWithOneUselessTerm(): void
    {
        /** @psalm-suppress ArgumentTypeCoercion due to Psalm bug */
        $formula = [
            new Clause(['$a' => ['!falsy' => new Truthy()], '$b' => ['!falsy' => new Truthy()]], 1, 1),
            new Clause(['$a' => ['falsy' => new Falsy()], '$b' => ['!falsy' => new Truthy()]], 1, 2),
        ];

        $simplified_formula = Algebra::simplifyCNF($formula);

        $this->assertCount(1, $simplified_formula);
        $this->assertSame('$b', (string)$simplified_formula[0]);
    }

    public function testSimplifyCNFWithNonUselessTerm(): void
    {
        $formula = [
            new Clause(['$a' => ['!falsy' => new Truthy()], '$b' => ['!falsy' => new Truthy()]], 1, 1),
            new Clause(['$a' => ['falsy' => new Falsy()], '$b' => ['falsy' => new Falsy()]], 1, 2),
        ];

        $simplified_formula = Algebra::simplifyCNF($formula);

        $this->assertCount(2, $simplified_formula);
        $this->assertSame('($a) || ($b)', (string)$simplified_formula[0]);
        $this->assertSame('(!$a) || (!$b)', (string)$simplified_formula[1]);
    }

    public function testSimplifyCNFWithUselessTermAndOneInMiddle(): void
    {
        /** @psalm-suppress ArgumentTypeCoercion due to Psalm bug */
        $formula = [
            new Clause(['$a' => ['!falsy' => new Truthy()], '$b' => ['!falsy' => new Truthy()]], 1, 1),
            new Clause(['$b' => ['!falsy' => new Truthy()]], 1, 2),
            new Clause(['$a' => ['falsy' => new Falsy()], '$b' => ['!falsy' => new Truthy()]], 1, 3),
        ];

        $simplified_formula = Algebra::simplifyCNF($formula);

        $this->assertCount(1, $simplified_formula);
        $this->assertSame('$b', (string)$simplified_formula[0]);
    }

    public function testGroupImpossibilities(): void
    {
        $a1 = new IsIdentical(new TArray([Type::getArrayKey(), Type::getMixed()]));

        $clause1 = (new Clause(
            [
                '$a' => [(string)$a1 => $a1],
            ],
            1,
            2,
            false,
            true,
            true,
            [],
        ))->calculateNegation();

        $a2 = new IsIsset();

        $clause2 = (new Clause(
            [
                '$b' => [(string)$a2 => $a2],
            ],
            1,
            2,
            false,
            true,
            true,
            [],
        ))->calculateNegation();

        $result_clauses = Algebra::groupImpossibilities([$clause1, $clause2]);

        $this->assertCount(0, $result_clauses);
    }

    /**
     * @param array<string, non-empty-list<Assertion>> $assertions_by_var
     * @psalm-capabilities read-props
     */
    private static function clause(array $assertions_by_var, int $object_id = 1): Clause
    {
        return new Clause(
            array_map(
                static function (array $assertions): array {
                    $keyed = [];
                    foreach ($assertions as $assertion) {
                        $keyed[(string) $assertion] = $assertion;
                    }

                    return $keyed;
                },
                $assertions_by_var,
            ),
            1,
            $object_id,
        );
    }

    /**
     * @param list<Clause> $clauses
     */
    private function assertClausesAreKeyedByAssertionString(array $clauses): void
    {
        foreach ($clauses as $clause) {
            foreach ($clause->possibilities as $assertions) {
                foreach ($assertions as $key => $assertion) {
                    $this->assertSame((string) $assertion, $key);
                }
            }
        }
    }

    /**
     * @param list<Clause> $clauses
     * @return list<string>
     * @psalm-capabilities read-props
     */
    private static function clauseStrings(array $clauses): array
    {
        return array_map(static fn(Clause $clause): string => (string) $clause, $clauses);
    }

    public function testClauseRequiresPossibilitiesKeyedByAssertionString(): void
    {
        if (ini_get('zend.assertions') !== '1') {
            $this->markTestSkipped('Needs zend.assertions=1');
        }

        $this->expectException(AssertionError::class);

        new Clause(['$a' => ['truthy' => new Truthy()]], 1, 1);
    }

    /**
     * @param array<string, non-empty-list<Assertion>> $this_assertions
     * @param array<string, non-empty-list<Assertion>> $other_assertions
     */
    #[DataProvider('providerContains')]
    public function testContains(array $this_assertions, array $other_assertions, bool $expected): void
    {
        $this->assertSame(
            $expected,
            self::clause($this_assertions)->contains(self::clause($other_assertions)),
        );
    }

    /**
     * @return iterable<string, array{array<string, non-empty-list<Assertion>>, array<string, non-empty-list<Assertion>>, bool}>
     */
    public static function providerContains(): iterable
    {
        yield 'same truthy' => [
            ['$a' => [new Truthy()]],
            ['$a' => [new Truthy()]],
            true,
        ];

        yield 'truthy does not contain falsy' => [
            ['$a' => [new Truthy()]],
            ['$a' => [new Falsy()]],
            false,
        ];

        yield 'falsy does not contain truthy' => [
            ['$a' => [new Falsy()]],
            ['$a' => [new Truthy()]],
            false,
        ];

        yield 'truthy or falsy contains truthy' => [
            ['$a' => [new Truthy(), new Falsy()]],
            ['$a' => [new Truthy()]],
            true,
        ];

        yield 'truthy does not contain truthy or falsy' => [
            ['$a' => [new Truthy()]],
            ['$a' => [new Truthy(), new Falsy()]],
            false,
        ];

        yield 'equal but distinct assertion objects' => [
            ['$a' => [new IsNotIdentical(new TLiteralInt(1))]],
            ['$a' => [new IsNotIdentical(new TLiteralInt(1))]],
            true,
        ];

        yield 'same assertion kind, other type' => [
            ['$a' => [new IsNotIdentical(new TLiteralInt(1))]],
            ['$a' => [new IsNotIdentical(new TLiteralInt(2))]],
            false,
        ];

        yield 'same type, negated assertion kind' => [
            ['$a' => [new IsNotIdentical(new TLiteralInt(1))]],
            ['$a' => [new IsIdentical(new TLiteralInt(1))]],
            false,
        ];

        yield 'superset of mixed assertions' => [
            ['$a' => [new IsType(new TInt()), new IsNotIdentical(new TLiteralInt(1)), new IsIsset()]],
            ['$a' => [new IsIsset(), new IsType(new TInt())]],
            true,
        ];

        yield 'one assertion missing' => [
            ['$a' => [new IsType(new TInt()), new IsIsset()]],
            ['$a' => [new IsType(new TInt()), new IsNotIdentical(new TLiteralInt(1))]],
            false,
        ];

        yield 'same assertion, other variable' => [
            ['$a' => [new Truthy()]],
            ['$b' => [new Truthy()]],
            false,
        ];

        yield 'all variables must be contained' => [
            ['$a' => [new Truthy()], '$b' => [new IsType(new TInt())]],
            ['$a' => [new Truthy()], '$b' => [new IsType(new TString())]],
            false,
        ];
    }

    /**
     * @param list<array<string, non-empty-list<Assertion>>> $formula
     * @param list<string> $expected
     */
    #[DataProvider('providerNegateFormula')]
    public function testNegateFormulaKeepsKeysCanonical(array $formula, array $expected): void
    {
        $negated_formula = Algebra::negateFormula(
            array_map(static fn(array $assertions): Clause => self::clause($assertions), $formula),
        );

        $this->assertClausesAreKeyedByAssertionString($negated_formula);
        $this->assertSame($expected, self::clauseStrings($negated_formula));
    }

    /**
     * @return iterable<string, array{list<array<string, non-empty-list<Assertion>>>, list<string>}>
     */
    public static function providerNegateFormula(): iterable
    {
        yield 'truthy' => [
            [['$a' => [new Truthy()]]],
            ['!$a'],
        ];

        yield 'falsy' => [
            [['$a' => [new Falsy()]]],
            ['$a'],
        ];

        yield 'identical' => [
            [['$a' => [new IsIdentical(new TLiteralInt(1))]]],
            ['$a is not int(1)'],
        ];

        yield 'not identical' => [
            [['$a' => [new IsNotIdentical(new TLiteralInt(1))]]],
            ['$a is int(1)'],
        ];

        yield 'orred assertions of several kinds' => [
            [['$a' => [new IsType(new TInt()), new IsNotIdentical(new TLiteralInt(1))]]],
            ['$a is not int', '$a is int(1)'],
        ];
    }

    /**
     * @param list<array<string, non-empty-list<Assertion>>> $formula
     * @param list<string> $expected
     */
    #[DataProvider('providerSimplifyCNF')]
    public function testSimplifyCNF(array $formula, array $expected): void
    {
        $clauses = [];

        foreach ($formula as $i => $assertions) {
            $clauses[] = self::clause($assertions, $i + 1);
        }

        $simplified_formula = Algebra::simplifyCNF($clauses);

        $this->assertClausesAreKeyedByAssertionString($simplified_formula);
        $this->assertSame($expected, self::clauseStrings($simplified_formula));
    }

    /**
     * @return iterable<string, array{list<array<string, non-empty-list<Assertion>>>, list<string>}>
     */
    public static function providerSimplifyCNF(): iterable
    {
        yield 'truthy removes its falsy disjunct' => [
            [
                ['$a' => [new Truthy()]],
                ['$a' => [new Falsy(), new IsType(new TInt())], '$b' => [new IsIsset()]],
            ],
            ['$a', '($a is int) || ($b is isset)'],
        ];

        yield 'falsy removes its truthy disjunct' => [
            [
                ['$a' => [new Falsy()]],
                ['$a' => [new Truthy(), new IsType(new TInt())], '$b' => [new IsIsset()]],
            ],
            ['!$a', '($a is int) || ($b is isset)'],
        ];

        yield 'type removes its negation' => [
            [
                ['$a' => [new IsType(new TInt())]],
                ['$a' => [(new IsType(new TInt()))->getNegation()], '$b' => [new IsIsset()]],
            ],
            ['$a is int', '$b is isset'],
        ];

        yield 'identical removes its negation' => [
            [
                ['$a' => [new IsIdentical(new TLiteralInt(1))]],
                ['$a' => [new IsNotIdentical(new TLiteralInt(1)), new IsType(new TString())]],
            ],
            ['$a is int(1)', '$a is string'],
        ];

        yield 'not identical removes its negation' => [
            [
                ['$a' => [new IsNotIdentical(new TLiteralInt(1))]],
                ['$a' => [new IsIdentical(new TLiteralInt(1)), new IsType(new TString())]],
            ],
            ['$a is not int(1)', '$a is string'],
        ];

        yield 'not identical to another value is not a negation' => [
            [
                ['$a' => [new IsNotIdentical(new TLiteralInt(1))]],
                ['$a' => [new IsIdentical(new TLiteralInt(2))]],
            ],
            ['$a is not int(1)', '$a is int(2)'],
        ];

        yield 'contradiction leaves nothing' => [
            [
                ['$a' => [new Truthy()]],
                ['$a' => [new Falsy()]],
            ],
            [],
        ];

        yield 'clause containing another one is redundant' => [
            [
                ['$a' => [new IsNotIdentical(new TLiteralInt(1))]],
                ['$a' => [new IsNotIdentical(new TLiteralInt(1)), new Truthy()], '$b' => [new IsIsset()]],
            ],
            ['$a is not int(1)'],
        ];
    }

    public function testFormulaOfUnknownExpressionIsKeyedByAssertionString(): void
    {
        $has_errors = false;

        $stmt = StatementsProvider::parseStatements(
            '<?php unknown_function($a) && (unknown_function($b) || $c !== 1);',
            7_04_00,
            $has_errors,
        )[0];

        $this->assertInstanceOf(PhpParser\Node\Stmt\Expression::class, $stmt);

        $file_analyzer = new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $file_analyzer->context = new Context();
        $statements_analyzer = new StatementsAnalyzer($file_analyzer, new NodeDataProvider(), false);

        $clauses = FormulaGenerator::getFormula(
            spl_object_id($stmt->expr),
            spl_object_id($stmt->expr),
            $stmt->expr,
            null,
            $statements_analyzer,
        );

        $this->assertNotSame([], $clauses);
        $this->assertClausesAreKeyedByAssertionString($clauses);

        $negated_clauses = Algebra::negateFormula($clauses);

        $this->assertClausesAreKeyedByAssertionString($negated_clauses);
        $this->assertClausesAreKeyedByAssertionString(Algebra::simplifyCNF([...$clauses, ...$negated_clauses]));
    }
}
