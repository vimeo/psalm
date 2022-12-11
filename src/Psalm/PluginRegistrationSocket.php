<?php

namespace Psalm;

use InvalidArgumentException;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\FunctionExistenceProviderInterface;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Plugin\EventHandler\MethodExistenceProviderInterface;
use Psalm\Plugin\EventHandler\MethodParamsProviderInterface;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Plugin\EventHandler\MethodVisibilityProviderInterface;
use Psalm\Plugin\EventHandler\PropertyExistenceProviderInterface;
use Psalm\Plugin\EventHandler\PropertyTypeProviderInterface;
use Psalm\Plugin\EventHandler\PropertyVisibilityProviderInterface;
use Psalm\Plugin\RegistrationInterface;

use function class_exists;
use function is_subclass_of;

final class PluginRegistrationSocket implements RegistrationInterface
{
    private Config $config;

    private Codebase $codebase;

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
            throw new InvalidArgumentException('Plugins must be loaded before registration');
        }

        $this->config->eventDispatcher->registerClass($handler);

        if (is_subclass_of($handler, PropertyExistenceProviderInterface::class)) {
            $this->codebase->properties->property_existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, PropertyVisibilityProviderInterface::class)) {
            $this->codebase->properties->property_visibility_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, PropertyTypeProviderInterface::class)) {
            $this->codebase->properties->property_type_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, MethodExistenceProviderInterface::class)) {
            $this->codebase->methods->existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, MethodVisibilityProviderInterface::class)) {
            $this->codebase->methods->visibility_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, MethodReturnTypeProviderInterface::class)) {
            $this->codebase->methods->return_type_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, MethodParamsProviderInterface::class)) {
            $this->codebase->methods->params_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, FunctionExistenceProviderInterface::class)) {
            $this->codebase->functions->existence_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, FunctionParamsProviderInterface::class)) {
            $this->codebase->functions->params_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, FunctionReturnTypeProviderInterface::class)) {
            $this->codebase->functions->return_type_provider->registerClass($handler);
        }

        if (is_subclass_of($handler, DynamicFunctionStorageProviderInterface::class)) {
            $this->codebase->functions->dynamic_storage_provider->registerClass($handler);
        }
    }
}
