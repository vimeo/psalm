<?php

namespace Psalm\Internal\Type;

use Psalm\Type\Union;

class TemplateResult
{
    /**
     * @var array<string, array<string, array{0: Union}>>
     */
    public $template_types;

    /**
     * @var array<string, array<string, array{0: Union, 1?: int}>>
     */
    public $generic_params;

    /**
     * @param  array<string, array<string, array{0: Union}>> $template_types
     * @param  array<string, array<string, array{0: Union, 1?: int}>> $generic_params
     */
    public function __construct(array $template_types, array $generic_params)
    {
        $this->template_types = $template_types;
        $this->generic_params = $generic_params;
    }
}
