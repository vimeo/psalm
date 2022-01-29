<?php

namespace Psalm\Internal\Cli;

use Composer\InstalledVersions;
use Psalm\Internal\CliUtils;
use Psalm\Internal\ExtensionStubGenerator\Command\GenerateExtensionStubCommand;
use Symfony\Component\Console\Application;

use function getcwd;

use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
final class GenerateExtensionStub
{
    public static function run(): void
    {
        $current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;
        $vendor_dir = CliUtils::getVendorDir($current_dir);
        CliUtils::requireAutoloaders($current_dir, false, $vendor_dir);

        $app = new Application("generate-extension-stub", InstalledVersions::getVersion("vimeo/psalm") ?? "unknown");

        $app->add(new GenerateExtensionStubCommand());

        $app->setDefaultCommand("generate-extension-stub", true);
        $app->run();
    }
}
