<?php
namespace Psalm\Example\Plugin;

use PhpParser;
use Psalm\FileManipulation;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use function strpos;
use function strtolower;
use function implode;
use function array_map;

class TestAssertionTransformer implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(
        AfterExpressionAnalysisEvent $event
    ): ?bool {
        $statements_source = $event->getStatementsSource();
        $file_replacements = $event->getFileReplacements();
        $expr = $event->getExpr();

        if ($expr instanceof PhpParser\Node\Expr\Array_) {
            $code_block = null;
            $assertion_array = null;

            foreach ($expr->items as $i => $array_item) {
                if (!$array_item) {
                    continue;
                }

                if ($i === 1
                    && (!$array_item->key instanceof PhpParser\Node\Scalar\String_
                        || $array_item->key->value === "assertions")
                    && $array_item->value instanceof PhpParser\Node\Expr\Array_
                ) {
                    $assertion_array = $array_item;
                }

                if ($i === 0
                    && $array_item->key === null
                    && $array_item->value instanceof PhpParser\Node\Scalar\String_
                    && substr($array_item->value->value, 0, 5) === '<?php'
                ) {
                    $code_block = $array_item->value;
                }
            }

            if ($code_block) {
                $file_replacements[] = new FileManipulation(
                    $code_block->getAttribute('startFilePos'),
                    $code_block->getAttribute('startFilePos'),
                    '\'code\' => '
                );

                $event->setFileReplacements($file_replacements);
            }
        }

        return null;
    }
}
