<?php
namespace Psalm;

interface StatementsSource extends FileSource
{
    public function getNamespace(): ?string;

    /**
     * @return array<string, string>
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
     * @return array<string, array<string, array{Type\Union}>>|null
     */
    public function getTemplateTypeMap(): ?array;

    /**
     * @return void
     */
    public function setRootFilePath(string $file_path, string $file_name);

    public function hasParentFilePath(string $file_path): bool;

    public function hasAlreadyRequiredFilePath(string $file_path): bool;

    public function getRequireNesting(): int;

    public function isStatic(): bool;

    public function getSource(): StatementsSource;

    public function getCodebase() : Codebase;

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    public function getSuppressedIssues(): array;

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function addSuppressedIssues(array $new_issues);

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function removeSuppressedIssues(array $new_issues);

    public function getNodeTypeProvider() : NodeTypeProvider;
}
