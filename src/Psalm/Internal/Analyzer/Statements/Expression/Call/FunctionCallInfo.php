<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\Type;

/**
 * @internal
 */
class FunctionCallInfo
{
    /**
     * @var ?string
     */
    public $function_id = null;

    /**
     * @var ?bool
     */
    public $function_exists = null;

    /**
     * @var bool
     */
    public $is_stubbed = false;

    /**
     * @var bool
     */
    public $in_call_map = false;

    /**
     * @var array<string, Type\Union>
     */
    public $defined_constants = [];

    /**
     * @var array<string, bool>
     */
    public $global_variables = [];

    /**
     * @var ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public $function_params = null;

    /**
     * @var ?\Psalm\Storage\FunctionLikeStorage
     */
    public $function_storage = null;

    /**
     * @var ?PhpParser\Node\Name
     */
    public $new_function_name = null;

    /**
     * @var bool
     */
    public $allow_named_args = true;

    /**
     * @var array
     */
    public $byref_uses = [];

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
