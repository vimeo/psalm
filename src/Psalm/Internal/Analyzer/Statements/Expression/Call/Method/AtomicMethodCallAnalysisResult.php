<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use Psalm\Internal\MethodIdentifier;
use Psalm\Storage\ClassLikeStorage;
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

    /**
     * Whether a memoizable result may be changed by a later call that mutates its receiver, i.e.
     * whether it comes from a mutation-free method of a class that is not immutable
     */
    public bool $memoized_result_has_mutations = false;

    /**
     * What was known of the properties the call writes before it, by var id
     *
     * @var array<string, ?Union>
     */
    public array $property_types_before_call = [];

    /**
     * What is known of the properties the call writes after it, for the types of the receiver analysed so far
     *
     * @var array<string, Union>
     */
    public array $property_types_after_call = [];

    /**
     * For how many types of the receiver the call writes each property
     *
     * @var array<string, int>
     */
    public array $property_writer_counts = [];

    /**
     * The types of the receiver the purity of the call was analysed for, with their template types
     *
     * @var list<array{ClassLikeStorage, array<string, array<string, Union>>}>
     */
    public array $analysed_receiver_classes = [];

    /**
     * Sets aside the methods earlier calls found and missed, so that those of the next call can be told apart
     *
     * @return array{array<string, bool>, bool, array<string>, array<string>, array<string>}
     * @psalm-external-mutation-free
     */
    public function setAsideFoundMethods(): array
    {
        $aside = [
            $this->existent_method_ids,
            $this->has_mixed_method_call,
            $this->non_existent_class_method_ids,
            $this->non_existent_interface_method_ids,
            $this->non_existent_magic_method_ids,
        ];

        $this->existent_method_ids = [];
        $this->has_mixed_method_call = false;
        $this->non_existent_class_method_ids = [];
        $this->non_existent_interface_method_ids = [];
        $this->non_existent_magic_method_ids = [];

        return $aside;
    }

    /**
     * Adds back what setAsideFoundMethods() set aside, without the methods the last call missed when
     * $drop_missing_methods is true
     *
     * @param array{array<string, bool>, bool, array<string>, array<string>, array<string>} $aside
     * @psalm-external-mutation-free
     */
    public function addBackFoundMethods(array $aside, bool $drop_missing_methods): void
    {
        [
            $existent_method_ids,
            $has_mixed_method_call,
            $non_existent_class_method_ids,
            $non_existent_interface_method_ids,
            $non_existent_magic_method_ids,
        ] = $aside;

        $this->existent_method_ids += $existent_method_ids;
        $this->has_mixed_method_call = $this->has_mixed_method_call || $has_mixed_method_call;

        if ($drop_missing_methods) {
            $this->non_existent_class_method_ids = $non_existent_class_method_ids;
            $this->non_existent_interface_method_ids = $non_existent_interface_method_ids;
            $this->non_existent_magic_method_ids = $non_existent_magic_method_ids;
        } else {
            $this->non_existent_class_method_ids = [
                ...$non_existent_class_method_ids,
                ...$this->non_existent_class_method_ids,
            ];
            $this->non_existent_interface_method_ids = [
                ...$non_existent_interface_method_ids,
                ...$this->non_existent_interface_method_ids,
            ];
            $this->non_existent_magic_method_ids = [
                ...$non_existent_magic_method_ids,
                ...$this->non_existent_magic_method_ids,
            ];
        }
    }
}
