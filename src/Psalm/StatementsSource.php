<?php
namespace Psalm;

interface StatementsSource extends FileSource
{
    /**
     * @return null|string
     */
    public function getNamespace(): ?string;

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped(): array;

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(): array;

    /**
     * @return string|null
     */
    public function getFQCLN(): ?string;

    /**
     * @return string|null
     */
    public function getClassName(): ?string;

    /**
     * @return string|null
     */
    public function getParentFQCLN(): ?string;

    /**
     * @return array<string, array<string, array{Type\Union}>>|null
     */
    public function getTemplateTypeMap(): ?array;

    /**
     * @param string $file_path
     * @param string $file_name
     *
     * @return void
     */
    public function setRootFilePath($file_path, $file_name);

    /**
     * @param string $file_path
     *
     * @return bool
     */
    public function hasParentFilePath($file_path): bool;

    /**
     * @param string $file_path
     *
     * @return bool
     */
    public function hasAlreadyRequiredFilePath($file_path): bool;

    /**
     * @return int
     */
    public function getRequireNesting(): int;

    /**
     * @return bool
     */
    public function isStatic(): bool;

    /**
     * @return StatementsSource|null
     */
    public function getSource();

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
