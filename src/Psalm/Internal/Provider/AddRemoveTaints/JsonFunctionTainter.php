<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\AddRemoveTaints;

use Override;
use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\AddTaintsInterface;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type\TaintKind;

use function strtolower;

use const JSON_HEX_AMP;
use const JSON_HEX_TAG;
use const JSON_PRETTY_PRINT;

/**
 * The taints json_encode() removes with the flags it is called with: the html one for the HEX flags, the header one
 * unless it pretty prints. And those json_decode() gives back.
 *
 * @internal
 */
final class JsonFunctionTainter implements AddTaintsInterface, RemoveTaintsInterface
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

    /**
     * A JSON string can hold any character as an escape sequence, which the escapes of the text before do not see:
     * what json_decode() gives has the taints they removed back, as `json_decode(json_encode($_GET['a'],
     * JSON_HEX_TAG | JSON_HEX_AMP))` has its <, > and &.
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
            || !self::isBuiltin($statements_analyzer, $item->name, 'json_decode')
        ) {
            return 0;
        }

        return TaintKind::INPUT_HTML | TaintKind::INPUT_HAS_QUOTES | TaintKind::INPUT_HEADER;
    }

    #[Override]
    public static function removeTaints(AddRemoveTaintsEvent $event): int
    {
        $item = $event->getExpr();
        $statements_analyzer = $event->getStatementsSource();

        if (!$statements_analyzer instanceof StatementsAnalyzer
            || !$item instanceof PhpParser\Node\Expr\FuncCall
            || $item->isFirstClassCallable()
            || !$item->name instanceof PhpParser\Node\Name
            || !self::isBuiltin($statements_analyzer, $item->name, 'json_encode')
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
     * Whether the name calls the builtin function, not a function of the namespace named the same
     *
     * @param lowercase-string $function_id
     */
    private static function isBuiltin(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Name $name,
        string $function_id,
    ): bool {
        $functions = $statements_analyzer->getCodebase()->functions;

        $called_id = strtolower($name instanceof PhpParser\Node\Name\FullyQualified
            ? $name->toString()
            : $functions->getFullyQualifiedFunctionNameFromString($name->toString(), $statements_analyzer));

        if ($called_id === $function_id) {
            return true;
        }

        // an unqualified name falls back to the global function when the namespace has none
        return !$name instanceof PhpParser\Node\Name\FullyQualified
            && strtolower($name->toString()) === $function_id
            && !$functions->functionExists($statements_analyzer, $called_id);
    }
}
