<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
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
     * Used as a set (id => true) to deduplicate repeated ids.
     *
     * @var array<non-empty-lowercase-string, true>
     */
    public array $callable_ids = [];

    /**
     * @param non-empty-lowercase-string $callable_id
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function addCallableId(string $callable_id): void
    {
        $this->callable_ids[$callable_id] = true;
    }

    /**
     * The templates a call of a first-class callable infers, as a call of the function or method it was made from
     *
     * @var array<string, non-empty-array<string, Union>>
     */
    public array $callable_template_types = [];

    /**
     * The return type of the function or method a first-class callable was made from, with the templates in
     * $callable_template_types
     */
    public ?Union $callable_return_type = null;

    public array $byref_uses = [];

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
