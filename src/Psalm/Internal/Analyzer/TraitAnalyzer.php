<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Attribute;
use Override;
use PhpParser\Node\Stmt\Trait_;
use Psalm\Aliases;
use Psalm\Context;
use Psalm\Interner;
use Psalm\IssueBuffer;

use function assert;

/**
 * @internal
 */
final class TraitAnalyzer extends ClassLikeAnalyzer
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        Trait_ $class,
        SourceAnalyzer $source,
        int $fq_class_name,
        private readonly Aliases $aliases,
    ) {
        $this->source = $source;
        $this->file_analyzer = $source->getFileAnalyzer();
        $this->class = $class;
        $this->fq_class_name = $fq_class_name;
        $codebase = $source->getCodebase();
        $this->storage = $codebase->classlike_storage_provider->get($fq_class_name);
    }

    /** @psalm-mutation-free */
    #[Override]
    public function getNamespace(): ?int
    {
        return $this->aliases->namespace;
    }

    /** @psalm-mutation-free */
    #[Override]
    public function getAliases(): Aliases
    {
        return $this->aliases;
    }

    /**
     * @return array<int, int>
     * @psalm-pure
     */
    #[Override]
    public function getAliasedClassesFlipped(): array
    {
        return [];
    }

    /**
     * @return array<int, int>
     * @psalm-pure
     */
    #[Override]
    public function getAliasedClassesFlippedReplaceable(): array
    {
        return [];
    }

    public static function analyze(StatementsAnalyzer $statements_analyzer, Trait_ $stmt, Context $context): void
    {
        assert($stmt->name !== null);
        $codebase = $statements_analyzer->getCodebase();

        $name = Interner::intern($stmt->name->name);
        if (!$codebase->classlike_storage_provider->has($name)) {
            return;
        }

        $storage = $codebase->classlike_storage_provider->get($name);

        ClassLikeAnalyzer::registerDocblockSuppressions($storage, $statements_analyzer->getFilePath(), $codebase);

        AttributesAnalyzer::analyze(
            $statements_analyzer,
            $context,
            $storage,
            $stmt->attrGroups,
            Attribute::TARGET_CLASS,
            $storage->suppressed_issues + $statements_analyzer->getSuppressedIssues(),
        );

        foreach ($storage->docblock_issues as $docblock_issue) {
            IssueBuffer::maybeAdd($docblock_issue);
        }
    }
}
