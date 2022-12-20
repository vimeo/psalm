<?php

declare(strict_types=1);

namespace Psalm\Plugin;

use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

final class DynamicTemplateProvider
{
    private string $defining_class;

    /**
     * @internal
     */
    public function __construct(string $defining_class)
    {
        $this->defining_class = $defining_class;
    }

    /**
     * If {@see DynamicFunctionStorage} requires template params this method can create it.
     */
    public function createTemplate(string $param_name, Union $as = null): TTemplateParam
    {
        return new TTemplateParam($param_name, $as ?? Type::getMixed(), $this->defining_class);
    }
}
