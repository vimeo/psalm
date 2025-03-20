<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\IsIdentical;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Storage\Assertion\IsType;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;

use function spl_object_id;

final class AlgebraTest extends TestCase
{
    public function testNegateFormula(): void
    {
        $formula = [
            new Clause(['$a' => ['truthy' => new Truthy()]], 1, 1),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(1, $negated_formula);
        $this->assertSame('!$a', (string)$negated_formula[0]);

        $formula = [
            new Clause(['$a' => ['truthy' => new Truthy()], '$b' => ['truthy' => new Truthy()]], 1, 1),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(2, $negated_formula);
        $this->assertSame('!$a', (string)$negated_formula[0]);
        $this->assertSame('!$b', (string)$negated_formula[1]);

        $formula = [
            new Clause(['$a' => ['truthy' => new Truthy()]], 1, 1),
            new Clause(['$b' => ['truthy' => new Truthy()]], 1, 2),
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
                    '$b' => ['truthy' => new Truthy()],
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
                    '$a' => ['truthy' => new Truthy()],
                    '$b' => ['truthy' => new Truthy()],
                ],
                1,
                1,
            ))->contains(
                new Clause(
                    [
                        '$a' => ['truthy' => new Truthy()],
                    ],
                    1,
                    1,
                ),
            ),
        );

        $this->assertFalse(
            (new Clause(
                [
                    '$a' => ['truthy' => new Truthy()],
                ],
                1,
                1,
            ))->contains(
                new Clause(
                    [
                        '$a' => ['truthy' => new Truthy()],
                        '$b' => ['truthy' => new Truthy()],
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
            new Clause(['$a' => ['truthy' => new Truthy()]], 1, 1),
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
            new Clause(['$a' => ['truthy' => new Truthy()], '$b' => ['truthy' => new Truthy()]], 1, 1),
            new Clause(['$a' => ['falsy' => new Falsy()], '$b' => ['truthy' => new Truthy()]], 1, 2),
        ];

        $simplified_formula = Algebra::simplifyCNF($formula);

        $this->assertCount(1, $simplified_formula);
        $this->assertSame('$b', (string)$simplified_formula[0]);
    }

    public function testSimplifyCNFWithNonUselessTerm(): void
    {
        $formula = [
            new Clause(['$a' => ['truthy' => new Truthy()], '$b' => ['truthy' => new Truthy()]], 1, 1),
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
            new Clause(['$a' => ['truthy' => new Truthy()], '$b' => ['truthy' => new Truthy()]], 1, 1),
            new Clause(['$b' => ['truthy' => new Truthy()]], 1, 2),
            new Clause(['$a' => ['falsy' => new Falsy()], '$b' => ['truthy' => new Truthy()]], 1, 3),
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
}
