<?php
namespace Psalm\Type\Atomic;

class TMixedDeferredConstant extends TMixed
{
    /**
     * @return string
     */
    public function getId()
    {
        return 'mixed-deferred-constant';
    }
}
