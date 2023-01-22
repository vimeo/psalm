<?php declare(strict_types=1);

namespace Psalm\Internal\Cli\Commands;

use Symfony\Component\Console\Style\SymfonyStyle;

final class PsalmStyle extends SymfonyStyle
{
    public function namedListing(string $header, array $elements): void
    {
        if ($elements === []) {
            $this->writeln(sprintf('<fg=green>%s</> []', $header),);
            $this->newLine();
            return;
        }

        $this->writeln(sprintf('<fg=green>%s</>', $header),);
        $elements = array_map(static function (string $element) {
            return sprintf(' - %s', $element);
        }, $elements);

        $this->writeln($elements);
        $this->newLine();
    }
}
