<?php
namespace Psalm;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Scanner\FileScanner;
use Psalm\PluginApi;

class LegacyPlugin implements PluginApi\PluginEntryPointInterface
{
    /** @var string */
    private $path;

    /** @var ProjectChecker */
    private $project_checker;

    /** @var Config */
    private $config;

    public function __construct(string $path, Config $config, ProjectChecker $project_checker)
    {
        $this->path = $path;
        $this->config = $config;
        $this->project_checker = $project_checker;
    }

    public function __invoke(PluginApi\RegistrationInterface $api): void
    {
        $codebase = $this->project_checker->codebase;
        $fq_class_name = $this->getPluginClassForPath($this->path, Plugin::class);

        /** @psalm-suppress UnresolvableInclude */
        require_once($this->path);

        if ($codebase->methods->methodExists($fq_class_name . '::afterMethodCallCheck')) {
            $this->config->after_method_checks[$fq_class_name] = $fq_class_name;
        }

        if ($codebase->methods->methodExists($fq_class_name . '::afterFunctionCallCheck')) {
            $this->config->after_function_checks[$fq_class_name] = $fq_class_name;
        }

        if ($codebase->methods->methodExists($fq_class_name . '::afterExpressionCheck')) {
            $this->config->after_expression_checks[$fq_class_name] = $fq_class_name;
        }

        if ($codebase->methods->methodExists($fq_class_name . '::afterStatementCheck')) {
            $this->config->after_statement_checks[$fq_class_name] = $fq_class_name;
        }

        if ($codebase->methods->methodExists($fq_class_name . '::afterClassLikeExistsCheck')) {
            $this->config->after_classlike_exists_checks[$fq_class_name] = $fq_class_name;
        }

        if ($codebase->methods->methodExists($fq_class_name . '::afterVisitClassLike')) {
            $this->config->after_visit_classlikes[$fq_class_name] = $fq_class_name;
        }
    }

    /**
     * @param  string $path
     * @param  string $must_extend
     *
     * @return string
     */
    private function getPluginClassForPath(string $path, string $must_extend)
    {
        $codebase = $this->project_checker->codebase;

        $file_storage = $codebase->createFileStorageForPath($path);
        $file_to_scan = new FileScanner($path, $this->config->shortenFileName($path), true);
        $file_to_scan->scan(
            $codebase,
            $file_storage
        );

        $declared_classes = ClassLikeChecker::getClassesForFile($this->project_checker, $path);

        if (count($declared_classes) !== 1) {
            throw new \InvalidArgumentException(
                'Plugins must have exactly one class in the file - ' . $path . ' has ' .
                    count($declared_classes)
            );
        }

        $fq_class_name = reset($declared_classes);

        if (!$codebase->classExtends(
            $fq_class_name,
            $must_extend
        )
        ) {
            throw new \InvalidArgumentException(
                'This plugin must extend ' . $must_extend . ' - ' . $path . ' does not'
            );
        }

        return $fq_class_name;
    }
}
