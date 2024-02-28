<?php

namespace Psalm\Internal\LanguageServer\Provider;

use Psalm\Config;
use Psalm\Internal\Provider\FileReferenceCacheProvider as InternalFileReferenceCacheProvider;

/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 *
 * @internal
 */
final class FileReferenceCacheProvider extends InternalFileReferenceCacheProvider
{
    private ?array $cached_file_references = null;

    private ?array $cached_classlike_files = null;

    private ?array $cached_method_class_references = null;

    private ?array $cached_nonmethod_class_references = null;

    private ?array $cached_method_member_references = null;

    private ?array $cached_method_dependencies = null;

    private ?array $cached_method_property_references = null;

    private ?array $cached_method_method_return_references = null;

    private ?array $cached_file_member_references = null;

    private ?array $cached_file_property_references = null;

    private ?array $cached_file_method_return_references = null;

    private ?array $cached_method_missing_member_references = null;

    private ?array $cached_file_missing_member_references = null;

    private ?array $cached_unknown_member_references = null;

    private ?array $cached_method_param_uses = null;

    private ?array $cached_issues = null;

    /** @var array<string, array<string, int>> */
    private array $cached_correct_methods = [];

    /**
     * @var array<
     *      string,
     *      array{
     *          0: array<int, array{0: int, 1: non-empty-string}>,
     *          1: array<int, array{0: int, 1: non-empty-string}>,
     *          2: array<int, array{0: int, 1: non-empty-string, 2: int}>
     *      }
     *  >
     */
    private array $cached_file_maps = [];

    public function __construct(Config $config)
    {
        parent::__construct($config);
    }

    public function getCachedFileReferences(): ?array
    {
        return $this->cached_file_references;
    }

    public function getCachedClassLikeFiles(): ?array
    {
        return $this->cached_classlike_files;
    }

    public function getCachedMethodClassReferences(): ?array
    {
        return $this->cached_method_class_references;
    }

    public function getCachedNonMethodClassReferences(): ?array
    {
        return $this->cached_nonmethod_class_references;
    }

    public function getCachedFileMemberReferences(): ?array
    {
        return $this->cached_file_member_references;
    }

    public function getCachedFilePropertyReferences(): ?array
    {
        return $this->cached_file_property_references;
    }

    public function getCachedFileMethodReturnReferences(): ?array
    {
        return $this->cached_file_method_return_references;
    }

    public function getCachedMethodMemberReferences(): ?array
    {
        return $this->cached_method_member_references;
    }

    public function getCachedMethodDependencies(): ?array
    {
        return $this->cached_method_dependencies;
    }

    public function getCachedMethodPropertyReferences(): ?array
    {
        return $this->cached_method_property_references;
    }

    public function getCachedMethodMethodReturnReferences(): ?array
    {
        return $this->cached_method_method_return_references;
    }

    public function getCachedFileMissingMemberReferences(): ?array
    {
        return $this->cached_file_missing_member_references;
    }

    public function getCachedMixedMemberNameReferences(): ?array
    {
        return $this->cached_unknown_member_references;
    }

    public function getCachedMethodMissingMemberReferences(): ?array
    {
        return $this->cached_method_missing_member_references;
    }

    public function getCachedMethodParamUses(): ?array
    {
        return $this->cached_method_param_uses;
    }

    public function getCachedIssues(): ?array
    {
        return $this->cached_issues;
    }

    public function setCachedFileReferences(array $file_references): void
    {
        $this->cached_file_references = $file_references;
    }

    public function setCachedClassLikeFiles(array $file_references): void
    {
        $this->cached_classlike_files = $file_references;
    }

    public function setCachedMethodClassReferences(array $method_class_references): void
    {
        $this->cached_method_class_references = $method_class_references;
    }

    public function setCachedNonMethodClassReferences(array $file_class_references): void
    {
        $this->cached_nonmethod_class_references = $file_class_references;
    }

    public function setCachedMethodMemberReferences(array $member_references): void
    {
        $this->cached_method_member_references = $member_references;
    }

    public function setCachedMethodDependencies(array $member_references): void
    {
        $this->cached_method_dependencies = $member_references;
    }

    public function setCachedMethodPropertyReferences(array $property_references): void
    {
        $this->cached_method_property_references = $property_references;
    }

    public function setCachedMethodMethodReturnReferences(array $method_return_references): void
    {
        $this->cached_method_method_return_references = $method_return_references;
    }

    public function setCachedMethodMissingMemberReferences(array $member_references): void
    {
        $this->cached_method_missing_member_references = $member_references;
    }

    public function setCachedFileMemberReferences(array $member_references): void
    {
        $this->cached_file_member_references = $member_references;
    }

    public function setCachedFilePropertyReferences(array $property_references): void
    {
        $this->cached_file_property_references = $property_references;
    }

    public function setCachedFileMethodReturnReferences(array $method_return_references): void
    {
        $this->cached_file_method_return_references = $method_return_references;
    }

    public function setCachedFileMissingMemberReferences(array $member_references): void
    {
        $this->cached_file_missing_member_references = $member_references;
    }

    public function setCachedMixedMemberNameReferences(array $references): void
    {
        $this->cached_unknown_member_references = $references;
    }

    public function setCachedMethodParamUses(array $uses): void
    {
        $this->cached_method_param_uses = $uses;
    }

    public function setCachedIssues(array $issues): void
    {
        $this->cached_issues = $issues;
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getAnalyzedMethodCache(): array
    {
        return $this->cached_correct_methods;
    }

    /**
     * @param array<string, array<string, int>> $analyzed_methods
     */
    public function setAnalyzedMethodCache(array $analyzed_methods): void
    {
        $this->cached_correct_methods = $analyzed_methods;
    }

    /**
     * @return array<
     *      string,
     *      array{
     *          0: array<int, array{0: int, 1: non-empty-string}>,
     *          1: array<int, array{0: int, 1: non-empty-string}>,
     *          2: array<int, array{0: int, 1: non-empty-string, 2: int}>
     *      }
     *  >
     */
    public function getFileMapCache(): array
    {
        return $this->cached_file_maps;
    }

    /**
     * @param array<
     *      string,
     *      array{
     *          0: array<int, array{0: int, 1: non-empty-string}>,
     *          1: array<int, array{0: int, 1: non-empty-string}>,
     *          2: array<int, array{0: int, 1: non-empty-string, 2: int}>
     *      }
     *  > $file_maps
     */
    public function setFileMapCache(array $file_maps): void
    {
        $this->cached_file_maps = $file_maps;
    }

    /**
     * @param array<string, array{int, int}> $mixed_counts
     */
    public function setTypeCoverage(array $mixed_counts): void
    {
    }
}
