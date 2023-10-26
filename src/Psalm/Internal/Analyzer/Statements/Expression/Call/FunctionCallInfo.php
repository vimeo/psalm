<?php

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
     * @var array<string, bool>
     */
    public array $global_variables = [];

    /**
     * @var ?array<int, FunctionLikeParameter>
     */
    public ?array $function_params = null;

    public ?FunctionLikeStorage $function_storage = null;

    public ?PhpParser\Node\Name $new_function_name = null;

    public bool $allow_named_args = true;

    public array $byref_uses = [];

    /**
     * @mutation-free
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
