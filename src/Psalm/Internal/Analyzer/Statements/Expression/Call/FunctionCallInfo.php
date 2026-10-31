<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;

/**
 * @internal
 */
final class FunctionCallInfo
{
    public ?string $function_id = null;

    public ?bool $function_exists = null;

    public bool $is_stubbed = false;

    public bool $in_call_map = false;

    /**
     * @var array<string, Union>
     */
    public array $defined_constants = [];

    /**
     * @var array<string, bool>
     */
    public array $global_variables = [];

    /**
     * @var ?array<int, FunctionLikeParameter>
     */
    public ?array $function_params = null;

    public ?FunctionLikeStorage $function_storage = null;

    public ?PhpParser\Node\Name $new_function_name = null;

    /**
     * When the call target is a callable value (closure / first-class callable) whose
     * underlying function id is known, this holds that id so taint sources can be
     * re-dispatched on invocation. If the target is a union of several distinct
     * callables (e.g. `rand() ? a(...) : b(...)`), every known id is collected here so
     * the taint behavior of all of them is re-dispatched, since any of them may run.
     *
     * Maps each id to the parameters of the callable value it comes from, which are not those of the underlying
     * function for a partial application, or null if they are not known.
     *
     * @var array<non-empty-lowercase-string, array<int, FunctionLikeParameter>|null>
     */
    public array $callable_ids = [];

    public bool $allow_named_args = true;

    public array $byref_uses = [];

    /**
     * Collects the known underlying id of a callable value. When the call target is a union of distinct
     * callables, any of them may run, so the taint behavior of all of them is re-dispatched on invocation (an
     * over-approximation, which is the sound direction for taint). Callables with no known id contribute nothing to
     * re-dispatch and are simply skipped.
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function addCallableId(TClosure|TCallable $callable): void
    {
        if ($callable->callable_id !== null) {
            $this->callable_ids[$callable->callable_id] = $callable->params;
        }
    }

    /**
     * @mutation-free
     * @psalm-mutation-free
     */
    public function hasByReferenceParameters(): bool
    {
        if (null === $this->function_params) {
            return false;
        }

        foreach ($this->function_params as $value) {
            if ($value->by_ref) {
                return true;
            }
        }

        return false;
    }
}
