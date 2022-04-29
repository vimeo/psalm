<?php

namespace Psalm\Type\Atomic;

use PhpParser\Node\Expr\CallLike;
use Psalm\Node\VirtualNode;
use Psalm\Storage\FunctionLikeParameter;

/**
 * Represents a closure where we know the return type and params
 */
final class TClosure extends TNamedObject
{
    use CallableTrait;

    /** @var array<string, bool> */
    public $byref_uses = [];

    /**
     * Can be used to better determine types by running another function.
     * This can be used for example by first class functions and Closure::fromCallable().
     * But it might as well be used if you provide your own higher order function wrappers.
     *
     * @var callable(list<FunctionLikeParameter>): CallLike & VirtualNode
     */
    public $forwarding_to = null;

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @param callable(list<FunctionLikeParameter>): CallLike & VirtualNode $forwardTo
     */
    public static function forwardingTo($forwardTo, TClosure $closure): self
    {
        $new = new self($closure->value, $closure->params, $closure->return_type, $closure->is_pure);
        $new->forwarding_to = $forwardTo;
        $new->byref_uses = $closure->byref_uses;
        return $new;
    }

    /**
     * It only makes sense to forward the closure call to an expression if the closure contains a template type.
     * If no return type is known, it probably also means that types need to be inferred.
     * Otherwise, all types are already known on inferring the closure expression types.
     */
    public function makesSenseToForwardCall(): bool
    {
        if (!$this->forwarding_to) {
            return false;
        }

        if (null === $this->params || !$this->return_type || $this->return_type->hasTemplate()) {
            return true;
        }

        foreach ($this->params as $param) {
            if ($param->type->hasTemplate()) {
                return true;
            }
        }

        return false;
    }
}
