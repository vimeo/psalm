<?php

namespace Psalm\Internal\ExtensionStubGenerator\Command;

use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\Reflection;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Stubs\Generator\StubsGenerator;
use ReflectionExtension;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

use function array_flip;
use function array_intersect_key;
use function array_map;
use function assert;
use function extension_loaded;
use function is_array;
use function is_scalar;
use function is_string;

use const PHP_VERSION;

/**
 * @internal
 */
class GenerateExtensionStubCommand extends Command
{
    public function __construct()
    {
        parent::__construct("generate-extension-stub");
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generates stubs for an extension using the PHP Reflection API')
            // Don't actually care about this, but it's required due to the way the command is forwarded
            ->addOption("--generate-extension-stub")
            ->addArgument(
                'extensionName',
                InputArgument::REQUIRED,
                'Extension name'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : new NullOutput();

        $extension_name = $input->getArgument('extensionName');
        if (!is_string($extension_name)) {
            throw new UnexpectedValueException('Extension name should be a string');
        }

        if (!extension_loaded($extension_name)) {
            $errorOutput->writeln("Extension not loaded. An extension must be loaded to generate stubs for it.");
            return 1;
        }


        $extension_reflection = new ReflectionExtension($extension_name);
        $extension_version = $extension_reflection->getVersion();

        $providers = new Providers(new FakeFileProvider());
        $project_analyzer = new ProjectAnalyzer(new Config(), $providers);
        $codebase = $project_analyzer->getCodebase();
        $reflection = new Reflection($providers->classlike_storage_provider, $codebase);

        foreach ($extension_reflection->getClasses() as $class_reflection) {
            $reflection->registerClass($class_reflection);
        }

        // Registering classes also registers their parents, so we need to
        // filter out parent classlikes that aren't defined by this extension.
        $classlike_storages = array_intersect_key(
            $providers->classlike_storage_provider->getAll(),
            array_flip(array_map("strtolower", $extension_reflection->getClassNames())),
        );

        foreach ($extension_reflection->getFunctions() as $function_reflection) {
            $function_name = $function_reflection->getName();
            assert($function_name);
            $reflection->registerFunction($function_name);
        }

        /** @var mixed $const_type */
        foreach ($extension_reflection->getConstants() as $const_name => $const_type) {
            if (!is_array($const_type) && !is_scalar($const_type) && $const_type !== null) {
                throw new RuntimeException("Unexpected type for constant \"$const_name\"");
            }
            $reflection->registerConstant($const_name, $const_type);
        }

        $namespaced_nodes = [];
        StubsGenerator::addConstantStubs($namespaced_nodes, $reflection->getConstants());
        StubsGenerator::addFunctionStubs($namespaced_nodes, $reflection->getFunctions());
        StubsGenerator::addClassLikeStubs($namespaced_nodes, $classlike_storages, $codebase);

        $output->writeln(StubsGenerator::printNamespacedNodes(
            $namespaced_nodes,
            "// Stub generated with PHP " . PHP_VERSION . ", $extension_name $extension_version",
        ));

        return 0;
    }
}
