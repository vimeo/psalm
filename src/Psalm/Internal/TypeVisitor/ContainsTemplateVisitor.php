<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\TypeNode;
use Psalm\Type\NodeVisitor;

class ContainsTemplateVisitor extends NodeVisitor
{
    /**
     * @var bool
     */
    private $contains_template = false;

    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TTemplateParam || $type instanceof TTemplateParamClass) {
            $this->contains_template = true;
            return NodeVisitor::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_template;
    }
}
