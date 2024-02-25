<?php

namespace Psalm\Internal\Provider\AddRemoveTaints;

use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\AddTaintsInterface;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type\TaintKind;

use function count;
use function strtolower;

use const ENT_QUOTES;

/**
 * @internal
 */
final class HtmlFunctionTainter implements AddTaintsInterface, RemoveTaintsInterface
{
    /**
     * Called to see what taints should be added
     *
     * @return list<string>
     */
    public static function addTaints(AddRemoveTaintsEvent $event): array
    {
        $item = $event->getExpr();
        $statements_analyzer = $event->getStatementsSource();

        if (!$statements_analyzer instanceof StatementsAnalyzer
            || !$item instanceof PhpParser\Node\Expr\FuncCall
            || $item->isFirstClassCallable()
            || !$item->name instanceof PhpParser\Node\Name
            || count($item->name->getParts()) !== 1
            || count($item->getArgs()) === 0
        ) {
            return [];
        }

        $function_id = strtolower($item->name->getFirst());

        if ($function_id === 'html_entity_decode'
            || $function_id === 'htmlspecialchars_decode'
        ) {
            $second_arg = $item->getArgs()[1]->value ?? null;

            if ($second_arg === null) {
                if ($statements_analyzer->getCodebase()->analysis_php_version_id >= 8_01_00) {
                    return [TaintKind::INPUT_HTML, TaintKind::INPUT_HAS_QUOTES];
                }
                return [TaintKind::INPUT_HTML];
            }

            $second_arg_value = $statements_analyzer->node_data->getType($second_arg);

            if (!$second_arg_value || !$second_arg_value->isSingleIntLiteral()) {
                return [TaintKind::INPUT_HTML];
            }

            $second_arg_value = $second_arg_value->getSingleIntLiteral()->value;

            if (($second_arg_value & ENT_QUOTES) === ENT_QUOTES) {
                return [TaintKind::INPUT_HTML, TaintKind::INPUT_HAS_QUOTES];
            }

            return [TaintKind::INPUT_HTML];
        }

        return [];
    }

    /**
     * Called to see what taints should be removed
     *
     * @return list<string>
     */
    public static function removeTaints(AddRemoveTaintsEvent $event): array
    {
        $item = $event->getExpr();
        $statements_analyzer = $event->getStatementsSource();

        if (!$statements_analyzer instanceof StatementsAnalyzer
            || !$item instanceof PhpParser\Node\Expr\FuncCall
            || $item->isFirstClassCallable()
            || !$item->name instanceof PhpParser\Node\Name
            || count($item->name->getParts()) !== 1
            || count($item->getArgs()) === 0
        ) {
            return [];
        }

        $function_id = strtolower($item->name->getFirst());

        if ($function_id === 'htmlentities'
            || $function_id === 'htmlspecialchars'
        ) {
            $second_arg = $item->getArgs()[1]->value ?? null;

            if ($second_arg === null) {
                if ($statements_analyzer->getCodebase()->analysis_php_version_id >= 8_01_00) {
                    return [TaintKind::INPUT_HTML, TaintKind::INPUT_HAS_QUOTES];
                }
                return [TaintKind::INPUT_HTML];
            }

            $second_arg_value = $statements_analyzer->node_data->getType($second_arg);

            if (!$second_arg_value || !$second_arg_value->isSingleIntLiteral()) {
                return [TaintKind::INPUT_HTML];
            }

            $second_arg_value = $second_arg_value->getSingleIntLiteral()->value;

            if (($second_arg_value & ENT_QUOTES) === ENT_QUOTES) {
                return [TaintKind::INPUT_HTML, TaintKind::INPUT_HAS_QUOTES];
            }

            return [TaintKind::INPUT_HTML];
        }

        return [];
    }
}
