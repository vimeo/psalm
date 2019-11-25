<?php
namespace Psalm\Tests;

use function is_array;
use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;

class AlgebraTest extends TestCase
{
    /**
     * @return void
     */
    public function testNegateFormula()
    {
        $formula = [
            new Clause(['$a' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(1, $negated_formula);
        $this->assertSame(['$a' => ['falsy']], $negated_formula[0]->possibilities);

        $formula = [
            new Clause(['$a' => ['!falsy'], '$b' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(2, $negated_formula);
        $this->assertSame(['$a' => ['falsy']], $negated_formula[0]->possibilities);
        $this->assertSame(['$b' => ['falsy']], $negated_formula[1]->possibilities);

        $formula = [
            new Clause(['$a' => ['!falsy']]),
            new Clause(['$b' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(1, $negated_formula);
        $this->assertSame(['$a' => ['falsy'], '$b' => ['falsy']], $negated_formula[0]->possibilities);

        $formula = [
            new Clause(['$a' => ['int', 'string'], '$b' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(3, $negated_formula);
        $this->assertSame(['$a' => ['!int']], $negated_formula[0]->possibilities);
        $this->assertSame(['$a' => ['!string']], $negated_formula[1]->possibilities);
        $this->assertSame(['$b' => ['falsy']], $negated_formula[2]->possibilities);
    }

    /**
     * @return void
     */
    public function testCombinatorialExpansion()
    {
        $dnf = '<?php ($b0 === true && $b4 === true && $b8 === true)
                  || ($b0 === true && $b1 === true && $b2 === true)
                  || ($b0 === true && $b3 === true && $b6 === true)
                  || ($b1 === true && $b4 === true && $b7 === true)
                  || ($b2 === true && $b5 === true && $b8 === true)
                  || ($b2 === true && $b4 === true && $b6 === true)
                  || ($b3 === true && $b4 === true && $b5 === true)
                  || ($b6 === true && $b7 === true && $b8 === true);';

        $dnf_stmt = StatementsProvider::parseStatements($dnf)[0];

        $this->assertInstanceOf(PhpParser\Node\Stmt\Expression::class, $dnf_stmt);

        $file_analyzer = new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $file_analyzer->context = new Context();
        $statements_analyzer = new StatementsAnalyzer($file_analyzer, new \Psalm\Internal\Provider\NodeDataProvider());

        $dnf_clauses = Algebra::getFormula(
            $dnf_stmt->expr,
            null,
            $statements_analyzer
        );

        $this->assertCount(6561, $dnf_clauses);

        $simplified_dnf_clauses = Algebra::simplifyCNF($dnf_clauses);

        $this->assertCount(23, $simplified_dnf_clauses);
    }

    /**
     * @return void
     */
    public function testContainsClause()
    {
        $this->assertTrue(
            (new Clause(
                [
                    '$a' => ['!falsy'],
                    '$b' => ['!falsy'],
                ]
            ))->contains(
                new Clause(
                    [
                        '$a' => ['!falsy'],
                    ]
                )
            )
        );

        $this->assertFalse(
            (new Clause(
                [
                    '$a' => ['!falsy'],
                ]
            ))->contains(
                new Clause(
                    [
                        '$a' => ['!falsy'],
                        '$b' => ['!falsy'],
                    ]
                )
            )
        );
    }

    /**
     * @return void
     */
    public function testSimplifyCNF()
    {
        $formula = [
            new Clause(['$a' => ['!falsy']]),
            new Clause(['$a' => ['falsy'], '$b' => ['falsy']]),
        ];

        $simplified_formula = Algebra::simplifyCNF($formula);

        $this->assertCount(2, $simplified_formula);
        $this->assertSame(['$a' => ['!falsy']], $simplified_formula[0]->possibilities);
        $this->assertSame(['$b' => ['falsy']], $simplified_formula[1]->possibilities);
    }
}
