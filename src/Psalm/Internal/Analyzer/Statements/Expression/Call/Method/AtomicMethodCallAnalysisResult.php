<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use Psalm\Internal\MethodIdentifier;
use Psalm\Type\Union;

/**
 * @internal
 */
final class AtomicMethodCallAnalysisResult
{
    public ?Union $return_type = null;

    public bool $returns_by_ref = false;

    public bool $has_mock = false;

    public bool $has_valid_method_call_type = false;

    public bool $has_mixed_method_call = false;

    /**
     * @var array<string>
     */
    public array $invalid_method_call_types = [];

    /**
     * @var array<string, bool>
     */
    public array $existent_method_ids = [];

    /**
     * @var array<string>
     */
    public array $non_existent_class_method_ids = [];

    /**
     * @var array<string>
     */
    public array $non_existent_interface_method_ids = [];

    /**
     * @var array<string>
     */
    public array $non_existent_magic_method_ids = [];

    public bool $check_visibility = true;

    public bool $too_many_arguments = true;

    /**
     * @var list<MethodIdentifier>
     */
    public array $too_many_arguments_method_ids = [];

    public bool $too_few_arguments = false;

    /**
     * @var list<MethodIdentifier>
     */
    public array $too_few_arguments_method_ids = [];

    public bool $can_memoize = false;
}
