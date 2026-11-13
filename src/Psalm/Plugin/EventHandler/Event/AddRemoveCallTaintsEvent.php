<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Type\Union;

/**
 * A value going into or coming out of a call, which may take taints or lose some on its way: an argument going into
 * the parameter of the function-like called, or what the call returns. Unlike AddRemoveTaintsEvent, it has no
 * expression: it is dispatched for the calls of PHP code and for those plugins wire with Codebase::addTaintedCall()
 * alike.
 *
 * @psalm-immutable
 * @api
 */
final class AddRemoveCallTaintsEvent
{
    /**
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Codebase $codebase,
        private readonly string $function_id,
        private readonly ?int $argument_offset,
        private readonly ?Union $argument_type,
        private readonly CodeLocation $code_location,
    ) {
    }

    /**
     * The function-like called, as `function_name` or `Class::method`
     */
    public function getFunctionId(): string
    {
        return $this->function_id;
    }

    /**
     * The position of the argument going into the call, null for what the call returns
     */
    public function getArgumentOffset(): ?int
    {
        return $this->argument_offset;
    }

    /**
     * The type of the argument, if it is known (null for what the call returns)
     */
    public function getArgumentType(): ?Union
    {
        return $this->argument_type;
    }

    /**
     * The location of the argument, or of the call
     */
    public function getCodeLocation(): CodeLocation
    {
        return $this->code_location;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }
}
