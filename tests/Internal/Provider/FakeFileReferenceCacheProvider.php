<?php
namespace Psalm\Tests\Internal\Provider;

/**
 * Used to determine which files reference other files, necessary for using the --diff
 * option from the command line.
 */
class FakeFileReferenceCacheProvider extends \Psalm\Internal\Provider\FileReferenceCacheProvider
{
    /** @var ?array */
    private $cached_file_references;

    /** @var ?array */
    private $cached_file_class_references;

    /** @var ?array */
    private $cached_method_member_references;

    /** @var ?array */
    private $cached_file_member_references;

    /** @var ?array */
    private $cached_method_missing_member_references;

    /** @var ?array */
    private $cached_file_missing_member_references;

    /** @var ?array */
    private $cached_unknown_member_references;

    /** @var ?array */
    private $cached_method_param_uses;

    /** @var ?array */
    private $cached_issues;

    /** @var array<string, array<string, int>> */
    private $cached_correct_methods = [];

    /**
     * @var array<
     *      string,
     *      array{
     *          0: array<int, array{0: int, 1: string}>,
     *          1: array<int, array{0: int, 1: string}>,
     *          2: array<int, array{0: int, 1: string, 2: int}>
     *      }
     *  >
     */
    private $cached_file_maps = [];

    public function __construct()
    {
        $this->config_changed = false;
    }

    /**
     * @return ?array
     */
    public function getCachedFileReferences()
    {
        return $this->cached_file_references;
    }

    /**
     * @return ?array
     */
    public function getCachedFileClassReferences()
    {
        return $this->cached_file_class_references;
    }

    /**
     * @return ?array
     */
    public function getCachedFileMemberReferences()
    {
        return $this->cached_file_member_references;
    }

    /**
     * @return ?array
     */
    public function getCachedMethodMemberReferences()
    {
        return $this->cached_method_member_references;
    }

    /**
     * @return ?array
     */
    public function getCachedFileMissingMemberReferences()
    {
        return $this->cached_file_missing_member_references;
    }

    /**
     * @return ?array
     */
    public function getCachedMixedMemberNameReferences()
    {
        return $this->cached_unknown_member_references;
    }

    /**
     * @return ?array
     */
    public function getCachedMethodMissingMemberReferences()
    {
        return $this->cached_method_missing_member_references;
    }

    /**
     * @return ?array
     */
    public function getCachedMethodParamUses()
    {
        return $this->cached_method_missing_member_references;
    }

    /**
     * @return ?array
     */
    public function getCachedIssues()
    {
        return $this->cached_issues;
    }

    /**
     * @return void
     */
    public function setCachedFileReferences(array $file_references)
    {
        $this->cached_file_references = $file_references;
    }

    /**
     * @return void
     */
    public function setCachedFileClassReferences(array $file_references)
    {
        $this->cached_file_class_references = $file_references;
    }

    /**
     * @return void
     */
    public function setCachedMethodMemberReferences(array $method_references)
    {
        $this->cached_method_member_references = $method_references;
    }

    /**
     * @return void
     */
    public function setCachedMethodMissingMemberReferences(array $method_references)
    {
        $this->cached_method_missing_member_references = $method_references;
    }

    /**
     * @return void
     */
    public function setCachedFileMemberReferences(array $method_references)
    {
        $this->cached_file_member_references = $method_references;
    }

    /**
     * @return void
     */
    public function setCachedFileMissingMemberReferences(array $method_references)
    {
        $this->cached_file_missing_member_references = $method_references;
    }

    /**
     * @return void
     */
    public function setCachedMixedMemberNameReferences(array $references)
    {
        $this->cached_unknown_member_references = $references;
    }

    /**
     * @return void
     */
    public function setCachedMethodParamUses(array $uses)
    {
        $this->cached_method_param_uses = $uses;
    }

    /**
     * @return void
     */
    public function setCachedIssues(array $issues)
    {
        $this->cached_issues = $issues;
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getAnalyzedMethodCache()
    {
        return $this->cached_correct_methods;
    }

    /**
     * @param array<string, array<string, int>> $correct_methods
     *
     * @return void
     */
    public function setAnalyzedMethodCache(array $correct_methods)
    {
        $this->cached_correct_methods = $correct_methods;
    }

    /**
     * @return array<
     *      string,
     *      array{
     *          0: array<int, array{0: int, 1: string}>,
     *          1: array<int, array{0: int, 1: string}>,
     *          2: array<int, array{0: int, 1: string, 2: int}>
     *      }
     *  >
     */
    public function getFileMapCache()
    {
        return $this->cached_file_maps;
    }

    /**
     * @param array<
     *      string,
     *      array{
     *          0: array<int, array{0: int, 1: string}>,
     *          1: array<int, array{0: int, 1: string}>,
     *          2: array<int, array{0: int, 1: string, 2: int}>
     *      }
     *  > $file_maps
     *
     * @return void
     */
    public function setFileMapCache(array $file_maps)
    {
        $this->cached_file_maps = $file_maps;
    }
}
