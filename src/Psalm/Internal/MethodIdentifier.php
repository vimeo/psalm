<?php

namespace Psalm\Internal;

class MethodIdentifier
{
    public $fq_class_name;
    public $method_name;

    /**
     * @param  string $fq_class_name
     * @param  lowercase-string $method_name
     */
    public function __construct(string $fq_class_name, string $method_name)
    {
        $this->fq_class_name = $fq_class_name;
        $this->method_name = $method_name;
    }

    /** @return string */
    public function __toString()
    {
        return $this->fq_class_name . '::' . $this->method_name;
    }
}
