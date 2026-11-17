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
use Psalm\Type\Union;

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

        if ($function_id === 'html_entity_decode'
            || $function_id === 'htmlspecialchars_decode'
        ) {
            $flags_arg = self::getFlagsArg($item);

            return self::getFlagTaints(
                $statements_analyzer->getCodebase()->analysis_php_version_id,
                $flags_arg !== null,
                $flags_arg !== null ? $statements_analyzer->node_data->getType($flags_arg) : null,
            );
        }

        return 0;
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
            $flags_arg = self::getFlagsArg($item);

            return self::getFlagTaints(
                $statements_analyzer->getCodebase()->analysis_php_version_id,
                $flags_arg !== null,
                $flags_arg !== null ? $statements_analyzer->node_data->getType($flags_arg) : null,
            );
        }

        return 0;
    }

    /**
     * What a call of htmlspecialchars() or htmlentities() removes, or of html_entity_decode() or
     * htmlspecialchars_decode() gives back, with the type of its flags: the quotes too when they have ENT_QUOTES, or
     * without flags from PHP 8.1 on
     *
     * @psalm-pure
     */
    private static function getFlagTaints(int $analysis_php_version_id, bool $has_flags, ?Union $flags): int
    {
        if (!$has_flags) {
            return $analysis_php_version_id >= 8_01_00
                ? TaintKind::INPUT_HTML | TaintKind::INPUT_HAS_QUOTES
                : TaintKind::INPUT_HTML;
        }

        return $flags !== null
            && $flags->isSingleIntLiteral()
            && ($flags->getSingleIntLiteral()->value & ENT_QUOTES) === ENT_QUOTES
            ? TaintKind::INPUT_HTML | TaintKind::INPUT_HAS_QUOTES
            : TaintKind::INPUT_HTML;
    }

    /**
     * What a call that PHP code doesn't make (see Codebase::addTaintedCall()) of one of the functions adds to what
     * it returns and removes from it, with the number and the types of its arguments
     *
     * @param array<int, Union> $argument_types
     * @return array{int, int}
     * @psalm-pure
     */
    public static function getWiredCallTaints(
        int $analysis_php_version_id,
        string $function_id,
        int $argument_count,
        array $argument_types,
    ): array {
        $taints = self::getFlagTaints($analysis_php_version_id, $argument_count > 1, $argument_types[1] ?? null);

        return match ($function_id) {
            'html_entity_decode', 'htmlspecialchars_decode' => [$taints, 0],
            'htmlentities', 'htmlspecialchars' => [0, $taints],
            default => [0, 0],
        };
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
