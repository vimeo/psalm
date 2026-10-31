<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\AddRemoveTaints;

use Override;
use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type\TaintKind;

use function strtolower;

use const JSON_HEX_AMP;
use const JSON_HEX_TAG;
use const JSON_PRETTY_PRINT;

/**
 * The taints json_encode() removes with the flags it is called with: the html one for the HEX flags, the header one
 * unless it pretty prints
 *
 * @internal
 */
final class JsonEncodeTainter implements RemoveTaintsInterface
{
    /**
     * JSON_HEX_TAG and JSON_HEX_AMP escape every <, > and & as <, > and &: the JSON syntax has none
     * of them, so they could only come from a string. Without JSON_HEX_AMP, an attribute decoding its entities
     * would give back what the other flags escape (&lt; or &quot; as < or ").
     *
     * The quotes are kept: a JSON string is always delimited by ", which ends a double-quoted attribute and makes
     * what the string holds part of the markup, whatever the flags.
     */
    private const HTML_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP;

    #[Override]
    public static function removeTaints(AddRemoveTaintsEvent $event): int
    {
        $item = $event->getExpr();
        $statements_analyzer = $event->getStatementsSource();

        if (!$statements_analyzer instanceof StatementsAnalyzer
            || !$item instanceof PhpParser\Node\Expr\FuncCall
            || $item->isFirstClassCallable()
            || !$item->name instanceof PhpParser\Node\Name
            || !self::isJsonEncode($statements_analyzer, $item->name)
        ) {
            return 0;
        }

        $flags = null;
        $has_flags = false;

        foreach ($item->getArgs() as $offset => $arg) {
            if ($arg->unpack) {
                return 0;
            }

            if ($arg->name === null ? $offset === 1 : $arg->name->name === 'flags') {
                $has_flags = true;
                $flags = $statements_analyzer->node_data->getType($arg->value);
            }
        }

        $flag_value = $flags !== null && $flags->isSingleIntLiteral() ? $flags->getSingleIntLiteral()->value : null;
        $removed_taints = 0;

        // every control character is escaped, so the output holds no CR, LF or NUL (the layout of
        // JSON_PRETTY_PRINT has LF)
        if (!$has_flags || ($flag_value !== null && ($flag_value & JSON_PRETTY_PRINT) === 0)) {
            $removed_taints |= TaintKind::INPUT_HEADER;
        }

        if ($flag_value !== null && ($flag_value & self::HTML_FLAGS) === self::HTML_FLAGS) {
            $removed_taints |= TaintKind::INPUT_HTML;
        }

        return $removed_taints;
    }

    /**
     * Whether the name calls the builtin json_encode(), not a function of the namespace named the same
     */
    private static function isJsonEncode(StatementsAnalyzer $statements_analyzer, PhpParser\Node\Name $name): bool
    {
        $functions = $statements_analyzer->getCodebase()->functions;

        $function_id = strtolower($name instanceof PhpParser\Node\Name\FullyQualified
            ? $name->toString()
            : $functions->getFullyQualifiedFunctionNameFromString($name->toString(), $statements_analyzer));

        if ($function_id === 'json_encode') {
            return true;
        }

        // an unqualified name falls back to the global function when the namespace has none
        return !$name instanceof PhpParser\Node\Name\FullyQualified
            && strtolower($name->toString()) === 'json_encode'
            && !$functions->functionExists($statements_analyzer, $function_id);
    }
}
