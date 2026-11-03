<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\NullsafeChainState;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function array_filter;
use function get_class;

final class NullsafeChainStateTest extends TestCase
{
    private const ATTRIBUTE = 'psalm-nullsafe-chain';

    public function testResetForgetsStateLeftByAnEarlierAnalysis(): void
    {
        $node = new Variable('a');

        NullsafeChainState::ShortCircuit->markOn($node);
        NullsafeChainState::reset($node);

        $this->assertSame(NullsafeChainState::None, NullsafeChainState::of($node));
    }

    public function testResetLeavesAnUnmarkedNodeUnmarked(): void
    {
        $node = new Variable('a');

        NullsafeChainState::reset($node);

        $this->assertFalse($node->hasAttribute(self::ATTRIBUTE));
        $this->assertSame(NullsafeChainState::None, NullsafeChainState::of($node));
    }

    public function testNodesNoNullsafeReachedAreNotMarked(): void
    {
        $hook = new class implements AfterExpressionAnalysisInterface {
            /** @var array<int, array<string, bool>> line => node kind => has the attribute */
            public static array $marked = [];

            #[Override]
            public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
            {
                $expr = $event->getExpr();

                $kind = match (true) {
                    $expr instanceof NullsafeMethodCall => 'nullsafe',
                    $expr instanceof MethodCall => 'method',
                    $expr instanceof StaticCall => 'static',
                    $expr instanceof PropertyFetch => 'property',
                    $expr instanceof ArrayDimFetch => 'array',
                    default => null,
                };

                if ($kind !== null) {
                    self::$marked[$expr->getStartLine()][$kind] = $expr->hasAttribute('psalm-nullsafe-chain');
                }

                return null;
            }
        };

        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        $hook::$marked = [];
        $this->project_analyzer->getCodebase()->config->eventDispatcher->after_expression_checks[] = get_class($hook);

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class B {
                    public int $prop = 0;
                    /** @var array{int} */
                    public array $items = [0];
                    public function next(): self { return $this; }
                    public static function make(): self { return new self(); }
                }
                class A {
                    public function b(): B { return new B(); }
                }
                function f(A $a, ?A $maybe): void {
                    $a->b()->next();
                    B::make();
                    $a->b()->prop;
                    $a->b()->items[0];
                    $maybe?->b()->next();
                }',
        );

        $this->analyzeFile($file_path, new Context());

        // no `?->` anywhere near these lines, so nothing is written onto their nodes
        foreach ([13, 14, 15, 16] as $line) {
            $this->assertNotEmpty($hook::$marked[$line]);
            $this->assertSame([], array_filter($hook::$marked[$line]), 'line ' . $line);
        }

        // the chain behind a `?->` still records its state
        $this->assertEquals(['nullsafe' => true, 'method' => true], $hook::$marked[17]);
    }
}
