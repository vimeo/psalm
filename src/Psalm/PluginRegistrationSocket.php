<?php
namespace Psalm;

use Psalm\PluginApi\Hook;
use Psalm\PluginApi\RegistrationInterface;

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

        if (is_subclass_of($handler, Plugin::class)) {
            $this->registerPluginDescendant($handler);
            return;
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

    /** @return void */
    private function registerPluginDescendant(string $handler)
    {
        // check that handler class (or one of its ancestors, but not Plugin) actually redefines specific hooks,
        // so that we don't register empty handlers provided by Plugin

        $handlerClass = new \ReflectionClass($handler);

        if ($handlerClass->getMethod('afterMethodCallAnalysis')->getDeclaringClass()->getName()
            !== Plugin::class
        ) {
            $this->config->after_method_checks[$handler] = $handler;
        }

        if ($handlerClass->getMethod('afterFunctionCallAnalysis')->getDeclaringClass()->getName()
            !== Plugin::class
        ) {
            $this->config->after_function_checks[$handler] = $handler;
        }

        if ($handlerClass->getMethod('afterExpressionAnalysis')->getDeclaringClass()->getName()
            !== Plugin::class
        ) {
            $this->config->after_expression_checks[$handler] = $handler;
        }

        if ($handlerClass->getMethod('afterStatementAnalysis')->getDeclaringClass()->getName()
            !== Plugin::class
        ) {
            $this->config->after_statement_checks[$handler] = $handler;
        }

        if ($handlerClass->getMethod('afterClassLikeExistenceCheck')->getDeclaringClass()->getName()
            !== Plugin::class
        ) {
            $this->config->after_classlike_exists_checks[$handler] = $handler;
        }

        if ($handlerClass->getMethod('afterClassLikeVisit')->getDeclaringClass()->getName()
            !== Plugin::class
        ) {
            $this->config->after_visit_classlikes[$handler] = $handler;
        }
    }
}
