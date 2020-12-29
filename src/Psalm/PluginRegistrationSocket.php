<?php
namespace Psalm;

use Psalm\Plugin\Hook;
use Psalm\Plugin\EventHandler;
use Psalm\Plugin\RegistrationInterface;
use function class_exists;
use function is_subclass_of;

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

    public function addStubFile(string $file_name): void
    {
        $this->config->addStubFile($file_name);
    }

    public function registerHooksFromClass(string $handler): void
    {
        if (!class_exists($handler, false)) {
            throw new \InvalidArgumentException('Plugins must be loaded before registration');
        }

        $this->config->eventDispatcher->registerClass($handler);

        if (is_subclass_of($handler, Hook\PropertyExistenceProviderInterface::class) ||
            is_subclass_of($handler, EventHandler\PropertyExistenceProviderInterface::class)
        ) {
            $this->codebase->properties->property_existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\PropertyVisibilityProviderInterface::class) ||
            is_subclass_of($handler, EventHandler\PropertyVisibilityProviderInterface::class)
        ) {
            $this->codebase->properties->property_visibility_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\PropertyTypeProviderInterface::class) ||
            is_subclass_of($handler, EventHandler\PropertyTypeProviderInterface::class)
        ) {
            $this->codebase->properties->property_type_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\MethodExistenceProviderInterface::class) ||
            is_subclass_of($handler, EventHandler\MethodExistenceProviderInterface::class)
        ) {
            $this->codebase->methods->existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\MethodVisibilityProviderInterface::class) ||
            is_subclass_of($handler, EventHandler\MethodVisibilityProviderInterface::class)
        ) {
            $this->codebase->methods->visibility_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\MethodReturnTypeProviderInterface::class) ||
            is_subclass_of($handler, EventHandler\MethodReturnTypeProviderInterface::class)
        ) {
            $this->codebase->methods->return_type_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\MethodParamsProviderInterface::class) ||
            is_subclass_of($handler, EventHandler\MethodParamsProviderInterface::class)
        ) {
            $this->codebase->methods->params_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\FunctionExistenceProviderInterface::class) ||
            is_subclass_of($handler, EventHandler\FunctionExistenceProviderInterface::class)
        ) {
            $this->codebase->functions->existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\FunctionParamsProviderInterface::class) ||
            is_subclass_of($handler, EventHandler\FunctionParamsProviderInterface::class)
        ) {
            $this->codebase->functions->params_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, Hook\FunctionReturnTypeProviderInterface::class) ||
            is_subclass_of($handler, EventHandler\FunctionReturnTypeProviderInterface::class)
        ) {
            $this->codebase->functions->return_type_provider->registerClass($handler);
        }
    }
}
