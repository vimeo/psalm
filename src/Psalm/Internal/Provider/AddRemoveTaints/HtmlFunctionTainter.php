<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\AddRemoveTaints;

use Override;
use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\IntMask;
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
     * html_entity_decode() and htmlspecialchars_decode() give back the html taint, and the has_quotes one unless no
     * flags they may have decode a quote: ENT_COMPAT decodes &quot;, ENT_QUOTES &#039; too.
     */
    #[Override]
    public static function addTaints(AddRemoveTaintsEvent $event): int
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
            return 0;
        }

        $function_id = strtolower($item->name->getFirst());

        if ($function_id !== 'html_entity_decode' && $function_id !== 'htmlspecialchars_decode') {
            return 0;
        }

        $flags_arg = self::getFlagsArg($item);

        // without flags, both quotes are decoded from PHP 8.1 on, and " before
        if ($flags_arg === null) {
            return TaintKind::INPUT_HTML | TaintKind::INPUT_HAS_QUOTES;
        }

        $flags = $statements_analyzer->node_data->getType($flags_arg);
        $possible_bits = $flags !== null ? IntMask::getPossibleBits($flags) : null;

        if ($possible_bits !== null && ($possible_bits & ENT_QUOTES) === 0) {
            return TaintKind::INPUT_HTML;
        }

        return TaintKind::INPUT_HTML | TaintKind::INPUT_HAS_QUOTES;
    }

    /**
     * Called to see what taints should be removed
     */
    #[Override]
    public static function removeTaints(AddRemoveTaintsEvent $event): int
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
            return 0;
        }

        $function_id = strtolower($item->name->getFirst());

        if ($function_id === 'htmlentities'
            || $function_id === 'htmlspecialchars'
        ) {
            $second_arg = self::getFlagsArg($item);

            if ($second_arg === null) {
                if ($statements_analyzer->getCodebase()->analysis_php_version_id >= 8_01_00) {
                    return TaintKind::INPUT_HTML|TaintKind::INPUT_HAS_QUOTES;
                }
                return TaintKind::INPUT_HTML;
            }

            $second_arg_value = $statements_analyzer->node_data->getType($second_arg);

            if (!$second_arg_value || !$second_arg_value->isSingleIntLiteral()) {
                return TaintKind::INPUT_HTML;
            }

            $second_arg_value = $second_arg_value->getSingleIntLiteral()->value;

            if (($second_arg_value & ENT_QUOTES) === ENT_QUOTES) {
                return TaintKind::INPUT_HTML|TaintKind::INPUT_HAS_QUOTES;
            }

            return TaintKind::INPUT_HTML;
        }

        return 0;
    }

    /**
     * The `flags` argument, positional or named, of htmlspecialchars() and the like
     *
     * @psalm-mutation-free
     */
    private static function getFlagsArg(PhpParser\Node\Expr\FuncCall $item): ?PhpParser\Node\Expr
    {
        foreach ($item->getArgs() as $offset => $arg) {
            if ($arg->name !== null ? strtolower($arg->name->name) === 'flags' : $offset === 1) {
                return $arg->value;
            }
        }

        return null;
    }
}
