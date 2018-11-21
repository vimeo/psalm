<?php
namespace Psalm;

use Psalm\Plugin\Hook;
use Psalm\Plugin\RegistrationInterface;

class PluginRegistrationSocket implements RegistrationInterface
{
    /** @var Config */
    private $config;

    /**
     * @internal
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /** @return void */
    public function addStubFile(string $file_name)
    {
        $this->config->addStubFile($file_name);
    }

    /** @return void */
    public function registerHooksFromClass(string $handler)
    {
        if (!class_exists($handler, false)) {
            throw new \InvalidArgumentException('Plugins must be loaded before registration');
        }

        if (is_subclass_of($handler, Hook\AfterMethodCallAnalysisInterface::class)) {
            $this->config->after_method_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterFunctionCallAnalysisInterface::class)) {
            $this->config->after_function_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterExpressionAnalysisInterface::class)) {
            $this->config->after_expression_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterStatementAnalysisInterface::class)) {
            $this->config->after_statement_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterClassLikeExistenceCheckInterface::class)) {
            $this->config->after_classlike_exists_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterClassLikeVisitInterface::class)) {
            $this->config->after_visit_classlikes[$handler] = $handler;
        }
    }
}
