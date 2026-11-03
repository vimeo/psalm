<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Psalm\Context;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type\Union;

use function str_starts_with;

/**
 * The `$this->` property state at every `return` of the constructor being checked for property initialisation
 *
 * @internal
 */
final class ConstructorExits
{
    /** @var list<array<string, Union>> */
    public array $states = [];

    public function __construct(public readonly FunctionLikeStorage $constructor)
    {
    }

    public static function record(StatementsAnalyzer $statements_analyzer, Context $context): void
    {
        $source = $statements_analyzer->getSource();

        if ($context->constructor_exits === null
            || !$source instanceof FunctionLikeAnalyzer
            || $source->getFunctionLikeStorage($statements_analyzer) !== $context->constructor_exits->constructor
        ) {
            return;
        }

        $state = [];

        foreach ($context->vars_in_scope as $var_id => $type) {
            if (str_starts_with($var_id, '$this->')) {
                $state[$var_id] = $type;
            }
        }

        $context->constructor_exits->states[] = $state;
    }
}
