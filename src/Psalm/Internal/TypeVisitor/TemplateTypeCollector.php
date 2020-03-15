<?php
namespace Psalm\Internal\TypeVisitor;

use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\TypeNode;
use Psalm\Type\NodeVisitor;

class TemplateTypeCollector extends NodeVisitor
{
    /**
     * @var list<TTemplateParam>
     */
    private $template_types = [];

    protected function enterNode(TypeNode $type) : ?int
    {
        if ($type instanceof TTemplateParam) {
            $this->template_types[] = $type;
        }

        return null;
    }

    /**
     * @return list<TTemplateParam>
     */
    public function getTemplateTypes() : array
    {
        return $this->template_types;
    }
}
