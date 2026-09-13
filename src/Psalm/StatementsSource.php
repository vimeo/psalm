<?php

declare(strict_types=1);

namespace Psalm;

use PhpParser\Node;
use Psalm\Issue\CodeIssue;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\Mutations;
use Psalm\Type\Union;

/**
 * @api
 */
interface StatementsSource extends FileSource
{
    public function getNamespace(): ?string;

    /**
     * @return array<lowercase-string, string>
     */
    public function getAliasedClassesFlipped(): array;

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(): array;

    public function getFQCLN(): ?string;

    public function getClassName(): ?string;

    public function getParentFQCLN(): ?string;

    /**
     * @return array<string, array<string, Union>>|null
     */
    public function getTemplateTypeMap(): ?array;

    /**
     * @psalm-external-mutation-free
     */
    public function setRootFilePath(string $file_path, string $file_name): void;

    public function hasParentFilePath(string $file_path): bool;

    public function hasAlreadyRequiredFilePath(string $file_path): bool;

    public function getRequireNesting(): int;

    public function isStatic(): bool;

    public function getSource(): StatementsSource;

    public function getCodebase(): Codebase;

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    public function getSuppressedIssues(): array;

    /**
     * @param list<string> $new_issues
     */
    public function addSuppressedIssues(array $new_issues): void;

    /**
     * @param list<string> $new_issues
     */
    public function removeSuppressedIssues(array $new_issues): void;

    public function getNodeTypeProvider(): NodeTypeProvider;

    /**
     * Records that the current function-like performs a mutation of the given
     * level, for purity inference.
     *
     * When $storage is the storage of an unannotated callee of the project, the
     * callee's level is only known once it has been analysed itself, so the
     * dependency is recorded and resolved after analysis instead (see
     * \Psalm\Internal\Codebase\MutationLevelResolver).
     *
     * @param Mutations::LEVEL_* $mutation_level
     * @param bool $callee_internal_mutations_ok whether mutations of the callee's own instance
     *        (e.g. of a freshly constructed object) are fine for the caller
     * @param ?string $callee_id the graph node of the callee, when it can't be derived from its storage (closures)
     */
    public function signalMutationOnlyInferred(
        int $mutation_level,
        ?FunctionLikeStorage $storage = null,
        bool $callee_internal_mutations_ok = false,
        ?string $callee_id = null,
    ): void;

    /**
     * @param Mutations::LEVEL_* $mutation_level
     * @param non-empty-string $msg
     * @param class-string<CodeIssue> $class
     * @param ?Mutations::LEVEL_* $inferred_mutation_level
     */
    public function signalMutation(
        int $mutation_level,
        Context $context,
        string $msg,
        string $class,
        Node $node,
        ?int $inferred_mutation_level = null,
        bool $overrideMsg = false,
        ?FunctionLikeStorage $storage = null,
        bool $callee_internal_mutations_ok = false,
        ?string $callee_id = null,
    ): void;
}
