<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Aliases;
use Psalm\StatementsSource;

class TraitAnalyzer extends ClassLikeAnalyzer
{
    /**
     * @var Aliases
     */
    private $aliases;

    /**
     * @param string $fq_class_name
     */
    public function __construct(
        PhpParser\Node\Stmt\Trait_ $class,
        SourceAnalyzer $source,
        $fq_class_name,
        Aliases $aliases
    ) {
        $this->source = $source;
        $this->file_checker = $source->getFileAnalyzer();
        $this->aliases = $source->getAliases();
        $this->class = $class;
        $this->fq_class_name = $fq_class_name;
        $this->storage = $this->file_checker->project_checker->classlike_storage_provider->get($fq_class_name);
        $this->aliases = $aliases;
    }

    /**
     * @return null|string
     */
    public function getNamespace()
    {
        return $this->aliases->namespace;
    }

    /**
     * @return Aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        return [];
    }
}
