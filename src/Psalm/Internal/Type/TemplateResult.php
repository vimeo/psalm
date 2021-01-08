<?php

namespace Psalm\Internal\Type;

use Psalm\Type\Union;
use function array_map;

class TemplateResult
{
    /**
     * @var array<string, array<string, Union>>
     */
    public $template_types;

    /**
     * @var array<string, array<string, TemplateBound>>
     */
    public $upper_bounds;

    /**
     * @var array<string, array<string, TemplateBound>>
     */
    public $lower_bounds = [];

    /**
     * If set to true then we shouldn't update the template bounds
     *
     * @var bool
     */
    public $readonly = false;

    /**
     * @var list<Union>
     */
    public $lower_bounds_unintersectable_types = [];

    /**
     * @param  array<string, array<string, Union>> $template_types
     * @param  array<string, array<string, Union>> $upper_bounds
     */
    public function __construct(array $template_types, array $upper_bounds)
    {
        $this->template_types = $template_types;

        $this->upper_bounds = array_map(
            function ($type_map) {
                return array_map(
                    function ($type) {
                        return new TemplateBound($type);
                    },
                    $type_map
                );
            },
            $upper_bounds
        );
    }
}
