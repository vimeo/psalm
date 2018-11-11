<?php
namespace Psalm;

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

        if (!is_subclass_of($handler, Plugin::class)) {
            throw new \InvalidArgumentException(
                'This handler must extend ' . Plugin::class . ' - ' . $handler . ' does not'
            );
        }

        // check that handler class (or one of its ancestors, but not Plugin) actually redefines specific hooks,
        // so that we don't register empty handlers provided by Plugin

        $handlerClass = new \ReflectionClass($handler);

        if ($handlerClass->getMethod('afterMethodCallCheck')->getDeclaringClass()->getName() !== Plugin::class) {
            $this->config->after_method_checks[$handler] = $handler;
        }

        if ($handlerClass->getMethod('afterFunctionCallCheck')->getDeclaringClass()->getName() !== Plugin::class) {
            $this->config->after_function_checks[$handler] = $handler;
        }

        if ($handlerClass->getMethod('afterExpressionCheck')->getDeclaringClass()->getName() !== Plugin::class) {
            $this->config->after_expression_checks[$handler] = $handler;
        }

        if ($handlerClass->getMethod('afterStatementCheck')->getDeclaringClass()->getName() !== Plugin::class) {
            $this->config->after_statement_checks[$handler] = $handler;
        }

        if ($handlerClass->getMethod('afterClassLikeExistsCheck')->getDeclaringClass()->getName() !== Plugin::class) {
            $this->config->after_classlike_exists_checks[$handler] = $handler;
        }

        if ($handlerClass->getMethod('afterVisitClassLike')->getDeclaringClass()->getName() !== Plugin::class) {
            $this->config->after_visit_classlikes[$handler] = $handler;
        }
    }
}
