<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Psalm\Context;
use Psalm\Internal\Scope\FinallyScope;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type\Union;

use function array_values;
use function count;
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

    /** @var array<int, FinallyScope> the `finally` that still runs after the `return` of a state, by its index */
    private array $finally_scopes = [];

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

        if ($context->finally_scope !== null) {
            $context->constructor_exits->finally_scopes[count($context->constructor_exits->states)]
                = $context->finally_scope;
        }

        $context->constructor_exits->states[] = $state;
    }

    /**
     * What a `finally` block assigns to `$this->` is assigned wherever the constructor returned through it
     *
     * @param array<string, Union> $finally_vars_in_scope the variables at the end of the `finally` block
     * @param array<string, int> $finally_assigned_var_ids
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function applyFinally(
        FinallyScope $finally_scope,
        array $finally_vars_in_scope,
        array $finally_assigned_var_ids,
    ): void {
        $states = $this->states;

        foreach ($this->finally_scopes as $index => $scope) {
            if ($scope !== $finally_scope) {
                continue;
            }

            foreach ($finally_assigned_var_ids as $var_id => $_) {
                if (str_starts_with($var_id, '$this->') && isset($finally_vars_in_scope[$var_id])) {
                    $states[$index][$var_id] = $finally_vars_in_scope[$var_id];
                }
            }
        }

        $this->states = array_values($states);
    }
}
