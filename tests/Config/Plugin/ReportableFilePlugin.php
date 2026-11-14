<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin;

use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\PluginRegistrationSocket;
use SimpleXMLElement;

use function getcwd;

use const DIRECTORY_SEPARATOR;

/**
 * Has the files of compiled/, outside the project files, analyzed and makes them reportable
 */
final class ReportableFilePlugin implements PluginEntryPointInterface
{
    #[Override]
    public function __invoke(PluginRegistrationSocket $registration, ?SimpleXMLElement $config = null): void
    {
        $compiled_dir = (string) getcwd() . DIRECTORY_SEPARATOR . 'compiled' . DIRECTORY_SEPARATOR;
        $compiled_files = [$compiled_dir . 'view.php', $compiled_dir . 'partial.php'];

        foreach ($compiled_files as $compiled_file) {
            $registration->codebase->addFilesToAnalyze([$compiled_file => $compiled_file]);
        }
        $registration->codebase->addReportableFiles($compiled_files);
    }
}
