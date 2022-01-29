<?php

namespace Psalm\Tests;

use Psalm\Internal\ExtensionStubGenerator\Command\GenerateExtensionStubCommand;
use Psalm\Internal\RuntimeCaches;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class ExtensionStubGeneratorTest extends TestCase
{
    /** @var Application */
    private $app;

    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->app = new Application("extension-stub-generator", "0.1");
        $this->app->add(new GenerateExtensionStubCommand());
        $this->app->setDefaultCommand("generate-extension-stub", true);
    }

    public function testErrorWhenExtensionMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Not enough arguments");

        $command = new CommandTester($this->app->find("generate-extension-stub"));
        $command->execute([]);
    }

    public function testErrorWhenExtensionNotEnabled(): void
    {
        $command = new CommandTester($this->app->find("generate-extension-stub"));
        $command->execute(["extensionName" => "extension_thats_not_enabled"], ["capture_stderr_separately" => true]);

        $output = $command->getErrorOutput();
        $this->assertStringContainsString("Extension not loaded. An extension must be loaded to generate stubs for it.", $output);
    }

    public function testGenerateXmlStub(): void
    {
        $command = new CommandTester($this->app->find("generate-extension-stub"));
        $command->execute(["extensionName" => "xml"], ["capture_stderr_separately" => true]);

        $output = $command->getDisplay();

        // Make sure at least one class, const, and function exists in the output
        $this->assertStringContainsString("class XMLParser", $output);
        $this->assertStringContainsString("const XML_ERROR_ASYNC_ENTITY", $output);
        $this->assertStringContainsString("function xml_error_string(", $output);

        // Make sure there were no warnings or errors
        $this->assertEquals("", $command->getErrorOutput());
    }
}
