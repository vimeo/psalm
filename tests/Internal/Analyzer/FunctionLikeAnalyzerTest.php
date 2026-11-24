<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Analyzer;

use Override;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Tests\TestCase;

use function get_class;
use function sort;

final class FunctionLikeAnalyzerTest extends TestCase
{
    public function testThePluginsSeeTheNodeOfTheFunctionLikeAnalysed(): void
    {
        $hook = new class implements AfterExpressionAnalysisInterface {
            /** @var list<string> the function-likes around the method calls */
            public static array $nodes = [];

            #[Override]
            public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
            {
                $source = $event->getStatementsSource()->getSource();

                if ($event->getExpr() instanceof MethodCall && $source instanceof FunctionLikeAnalyzer) {
                    $node = $source->getFunctionLikeNode();

                    self::$nodes[] = match (true) {
                        $node instanceof ClassMethod, $node instanceof Function_ => $node->name->name,
                        default => $node::class,
                    };
                }

                return null;
            }
        };

        $hook::$nodes = [];
        $this->project_analyzer->getCodebase()->config->eventDispatcher->after_expression_checks[] = get_class($hook);

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                final class Relations {
                    public function owner(): void {
                        $this->load();
                    }

                    public function load(): void {}
                }

                function run(Relations $relations): void {
                    $relations->load();
                }',
        );

        $this->analyzeFile($file_path, new Context());

        $nodes = $hook::$nodes;
        sort($nodes);

        $this->assertSame(['owner', 'run'], $nodes);
    }
}
