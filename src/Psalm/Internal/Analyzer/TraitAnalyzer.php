<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Attribute;
use Override;
use PhpParser\Node\Stmt\Trait_;
use Psalm\Aliases;
use Psalm\Context;
use Psalm\Type;

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
        string $fq_class_name,
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
    public function getNamespace(): ?string
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
     * @return array<lowercase-string, string>
     * @psalm-pure
     */
    #[Override]
    public function getAliasedClassesFlipped(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
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

        $fq_trait_name = Type::getFQCLNFromString($stmt->name->name, $statements_analyzer->getAliases());

        if (!$codebase->classlike_storage_provider->has($fq_trait_name)) {
            return;
        }

        $storage = $codebase->classlike_storage_provider->get($fq_trait_name);

        ClassLikeAnalyzer::registerDocblockSuppressions($storage, $statements_analyzer->getFilePath(), $codebase);

        AttributesAnalyzer::analyze(
            $statements_analyzer,
            $context,
            $storage,
            $stmt->attrGroups,
            Attribute::TARGET_CLASS,
            $storage->suppressed_issues + $statements_analyzer->getSuppressedIssues(),
        );

        self::reportDocblockIssues(
            $codebase,
            $storage,
            $storage->suppressed_issues + $statements_analyzer->getSuppressedIssues(),
        );
    }
}
