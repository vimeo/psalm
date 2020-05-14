<?php
namespace Psalm\Internal\Type\TypeAlias;

class LinkableTypeAlias implements \Psalm\Internal\Type\TypeAlias
{
    public $declaring_fq_classlike_name;

    public $alias_name;

    public function __construct(string $declaring_fq_classlike_name, string $alias_name)
    {
        $this->declaring_fq_classlike_name = $declaring_fq_classlike_name;
        $this->alias_name = $alias_name;
    }
}
