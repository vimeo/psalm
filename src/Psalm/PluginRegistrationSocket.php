<?php
namespace Psalm;

use Psalm\Plugin\Hook;
use Psalm\Plugin\RegistrationInterface;

class PluginRegistrationSocket implements RegistrationInterface
{
    /** @var Config */
    private $config;

    /** @var Codebase */
    private $codebase;

    /**
     * @internal
     */
    public function __construct(Config $config, Codebase $codebase)
    {
        $this->config = $config;
        $this->codebase = $codebase;
    }

    /** @return void */
    public function addStubFile(string $file_name)
    {
        $this->config->addStubFile($file_name);
    }

    /**
     * @return void
     * @psalm-suppress TypeCoercion
     */
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

        if (is_subclass_of($handler, Hook\AfterClassLikeAnalysisInterface::class)) {
            $this->config->after_classlike_checks[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterClassLikeVisitInterface::class)) {
            $this->config->after_visit_classlikes[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\AfterCodebasePopulatedInterface::class)) {
            $this->config->after_codebase_populated[$handler] = $handler;
        }

        if (is_subclass_of($handler, Hook\PropertyExistenceProviderInterface::class)) {
            $this->codebase->properties->property_existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\PropertyVisibilityProviderInterface::class)) {
            $this->codebase->properties->property_visibility_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\PropertyTypeProviderInterface::class)) {
            $this->codebase->properties->property_type_provider->registerClass($handler);
        }
    }
}
