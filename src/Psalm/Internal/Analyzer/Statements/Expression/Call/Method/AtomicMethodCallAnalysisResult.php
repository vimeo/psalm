<?php

declare(strict_types=1);

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
     * class name id => method name id => method id
     *
     * @var array<int, array<int, MethodIdentifier>>
     */
    public array $existent_method_ids = [];

    /**
     * @psalm-external-mutation-free
     */
    public function addExistentMethodId(MethodIdentifier $method_id): void
    {
        $this->existent_method_ids[$method_id->fq_class_name][$method_id->method_name] = $method_id;
    }

    /**
     * @param array<int, array<int, MethodIdentifier>> $a
     * @param array<int, array<int, MethodIdentifier>> $b
     * @return array<int, array<int, MethodIdentifier>>
     * @psalm-pure
     */
    public static function mergeMethodIds(array $a, array $b): array
    {
        foreach ($b as $class => $method_ids) {
            foreach ($method_ids as $method_name => $method_id) {
                $a[$class][$method_name] = $method_id;
            }
        }
        return $a;
    }

    /**
     * @var list<array{MethodIdentifier, string}> method id, cased method id (for messages)
     */
    public array $non_existent_class_method_ids = [];

    /**
     * @var list<array{MethodIdentifier, string}> method id, cased method id (for messages)
     */
    public array $non_existent_interface_method_ids = [];

    /**
     * @var list<MethodIdentifier>
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
