<?php

namespace Psalm\Internal\Analyzer;

use PhpParser\Node\Stmt\Trait_;
use Psalm\Aliases;
use Psalm\Context;

use function assert;

/**
 * @internal
 */
class TraitAnalyzer extends ClassLikeAnalyzer
{
    private Aliases $aliases;

    public function __construct(
        Trait_ $class,
        SourceAnalyzer $source,
        string $fq_class_name,
        Aliases $aliases
    ) {
        $this->source = $source;
        $this->file_analyzer = $source->getFileAnalyzer();
        $this->aliases = $source->getAliases();
        $this->class = $class;
        $this->fq_class_name = $fq_class_name;
        $codebase = $source->getCodebase();
        $this->storage = $codebase->classlike_storage_provider->get($fq_class_name);
        $this->aliases = $aliases;
    }

    /** @psalm-mutation-free */
    public function getNamespace(): ?string
    {
        return $this->aliases->namespace;
    }

    /** @psalm-mutation-free */
    public function getAliases(): Aliases
    {
        return $this->aliases;
    }

    /**
     * @psalm-mutation-free
     * @return array<lowercase-string, string>
     */
    public function getAliasedClassesFlipped(): array
    {
        return [];
    }

    /**
     * @psalm-mutation-free
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(): array
    {
        return [];
    }

    public static function analyze(StatementsAnalyzer $statements_analyzer, Trait_ $stmt, Context $context): void
    {
        assert($stmt->name !== null);
        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->classlike_storage_provider->has($stmt->name->name)) {
            return;
        }

        $storage = $codebase->classlike_storage_provider->get($stmt->name->name);

        AttributesAnalyzer::analyze(
            $statements_analyzer,
            $context,
            $storage,
            $stmt->attrGroups,
            AttributesAnalyzer::TARGET_CLASS,
            $storage->suppressed_issues + $statements_analyzer->getSuppressedIssues(),
        );
    }
}
