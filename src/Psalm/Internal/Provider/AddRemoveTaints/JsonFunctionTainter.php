<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\AddRemoveTaints;

use Override;
use PhpParser;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type\TaintKind;

use const JSON_HEX_AMP;
use const JSON_HEX_TAG;
use const JSON_PRETTY_PRINT;

/**
 * The taints json_encode() removes with the flags it is called with: the html one for the HEX flags, the header one
 * unless it pretty prints. And those json_decode() gives back.
 *
 * @internal
 */
final class JsonFunctionTainter implements RemoveTaintsInterface
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

    /** What json_decode() gives back (see getGivenBackTaints()) */
    public const GIVEN_BACK_TAINTS = TaintKind::INPUT_HTML | TaintKind::INPUT_HAS_QUOTES | TaintKind::INPUT_HEADER;

    /**
     * A JSON string can hold any character as an escape sequence, which the escapes of the text before do not see:
     * what json_decode() gives has the taints they removed back, as `json_decode(json_encode($_GET['a'],
     * JSON_HEX_TAG | JSON_HEX_AMP))` has its <, > and &.
     */
    public static function getGivenBackTaints(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $item,
    ): int {
        if ($item->isFirstClassCallable()
            || !$item->name instanceof PhpParser\Node\Name
            || !ConstantFlags::isBuiltin($statements_analyzer, $item->name, 'json_decode')
        ) {
            return 0;
        }

        return self::GIVEN_BACK_TAINTS;
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
            || !ConstantFlags::isBuiltin($statements_analyzer, $item->name, 'json_encode')
        ) {
            return 0;
        }

        // without flags, it has none
        [$certain_bits, $possible_bits] = [0, 0];

        foreach ($item->getArgs() as $offset => $arg) {
            if ($arg->unpack) {
                return 0;
            }

            if ($arg->name === null ? $offset === 1 : $arg->name->name === 'flags') {
                [$certain_bits, $possible_bits] = ConstantFlags::getBits($statements_analyzer, $arg->value);
            }
        }

        return self::getRemovedTaints($certain_bits, $possible_bits);
    }

    /**
     * What a call of json_encode() removes with the bits its flags have for sure, and those they may have
     *
     * @psalm-pure
     */
    public static function getRemovedTaints(int $certain_bits, int $possible_bits): int
    {
        $removed_taints = 0;

        // every control character is escaped, so the output holds no CR, LF or NUL (the layout of
        // JSON_PRETTY_PRINT has LF)
        if (($possible_bits & JSON_PRETTY_PRINT) === 0) {
            $removed_taints |= TaintKind::INPUT_HEADER;
        }

        if (($certain_bits & self::HTML_FLAGS) === self::HTML_FLAGS) {
            $removed_taints |= TaintKind::INPUT_HTML;
        }

        return $removed_taints;
    }
}
