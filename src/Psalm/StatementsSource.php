<?php

declare(strict_types=1);

namespace Psalm;

use PhpParser\Node;
use Psalm\Issue\CodeIssue;
use Psalm\Storage\Mutations;
use Psalm\Type\Union;

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
     * @param Mutations::LEVEL_* $mutation_level
     */
    public function signalMutationOnlyInferred(
        int $mutation_level,
    ): void;

    /**
     * @param Mutations::LEVEL_* $mutation_level
     * @param non-empty-string $msg
     * @param class-string<CodeIssue> $class
     * @return ?non-empty-string
     */
    public function signalMutation(
        int $mutation_level,
        Context $context,
        string $msg,
        string $class,
        Node $node,
    ): bool;
}
