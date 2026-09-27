<?php

declare(strict_types=1);

namespace Psalm\Plugin;

use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

/**
 * @psalm-immutable
 * @api
 */
final class DynamicTemplateProvider
{
    /**
     * @param int $defining_class interned defining entity (e.g. `fn-foo`)
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly int $defining_class,
    ) {
    }

    /**
     * If {@see DynamicFunctionStorage} requires template params this method can create it.
     *
     * @param int $param_name interned template param name
     * @psalm-mutation-free
     */
    public function createTemplate(int $param_name, ?Union $as = null): TTemplateParam
    {
        return new TTemplateParam($param_name, $as ?? Type::getMixed(), $this->defining_class);
    }
}
