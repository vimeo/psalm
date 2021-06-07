<?php
namespace Psalm;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Plugin\EventHandler;
use Psalm\Plugin\Hook;
use Psalm\Plugin\RegistrationInterface;

use function class_exists;
use function in_array;
use function is_a;
use function is_subclass_of;
use function sprintf;

class PluginRegistrationSocket implements RegistrationInterface
{
    /** @var Config */
    private $config;

    /** @var Codebase */
    private $codebase;

    /**
     * @var array<string, class-string<FileScanner>>
     */
    private $additionalFileTypeScanners = [];

    /**
     * @var array<string, class-string<FileAnalyzer>>
     */
    private $additionalFileTypeAnalyzers = [];

    /**
     * @var list<string>
     */
    private $additionalFileExtensions = [];

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

    /**
     * @param string $fileExtension e.g. `'html'`
     * @param class-string<FileScanner> $className
     */
    public function addFileTypeScanner(string $fileExtension, string $className): void
    {
        if (!class_exists($className) || !is_a($className, FileScanner::class, true)) {
            throw new \LogicException(
                sprintf(
                    'Class %s must be of type %s',
                    $className,
                    FileScanner::class
                ),
                1622727271
            );
        }
        if (!empty($this->config->getFiletypeScanners()[$fileExtension])
            || !empty($this->additionalFileTypeScanners[$fileExtension])
        ) {
            throw new \LogicException(
                sprintf('Cannot redeclare scanner for file-type %s', $fileExtension),
                1622727272
            );
        }
        $this->additionalFileTypeScanners[$fileExtension] = $className;
        $this->addFileExtension($fileExtension);
    }

    /**
     * @return array<string, class-string<FileScanner>>
     */
    public function getAdditionalFileTypeScanners(): array
    {
        return $this->additionalFileTypeScanners;
    }

    /**
     * @param string $fileExtension e.g. `'html'`
     * @param class-string<FileAnalyzer> $className
     */
    public function addFileTypeAnalyzer(string $fileExtension, string $className): void
    {
        if (!class_exists($className) || !is_a($className, FileAnalyzer::class, true)) {
            throw new \LogicException(
                sprintf(
                    'Class %s must be of type %s',
                    $className,
                    FileAnalyzer::class
                ),
                1622727281
            );
        }
        if (!empty($this->config->getFiletypeAnalyzers()[$fileExtension])
            || !empty($this->additionalFileTypeAnalyzers[$fileExtension])
        ) {
            throw new \LogicException(
                sprintf('Cannot redeclare analyzer for file-type %s', $fileExtension),
                1622727282
            );
        }
        $this->additionalFileTypeAnalyzers[$fileExtension] = $className;
        $this->addFileExtension($fileExtension);
    }

    /**
     * @return array<string, class-string<FileAnalyzer>>
     */
    public function getAdditionalFileTypeAnalyzers(): array
    {
        return $this->additionalFileTypeAnalyzers;
    }

    /**
     * @return list<string> e.g. `['html', 'perl']`
     */
    public function getAdditionalFileExtensions(): array
    {
        return $this->additionalFileExtensions;
    }

    /**
     * @param string $fileExtension e.g. `'html'`
     */
    private function addFileExtension(string $fileExtension): void
    {
        if (!in_array($fileExtension, $this->config->getFileExtensions(), true)) {
            $this->additionalFileExtensions[] = $fileExtension;
        }
    }
}
