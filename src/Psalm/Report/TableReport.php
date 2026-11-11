<?php

declare(strict_types=1);

namespace Psalm\Report;

use Override;
use Psalm\Report;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;

use function array_map;
use function implode;
use function str_split;
use function strtr;

/**
 * @api
 */
final class TableReport extends Report
{
    /**
     * @psalm-suppress PossiblyNullReference
     */
    #[Override]
    public function create(): string
    {
        /** @var BufferedOutput|null $buffer */
        $buffer = null;

        /** @var Table|null $table */
        $table = null;

        /** @var string|null $current_file */
        $current_file = null;

        $output = [];
        foreach ($this->issues_data as $issue_data) {
            if ($current_file === null || $current_file !== $issue_data->file_name) {
                // If we're processing a new file, then wrap up the last table and render it out.
                if ($buffer !== null) {
                    $table->render();
                    $output[] = $buffer->fetch();
                }

                $output[] = 'FILE: ' . $issue_data->file_name . "\n";

                $buffer = new BufferedOutput();
                $table = new Table($buffer);
                $table->setHeaders(['SEVERITY', 'LINE', 'ISSUE', 'DESCRIPTION']);
            }

            $severity = $this->getSeverityLabel($issue_data);

            // Since `Table::setColumnMaxWidth` is only available in symfony/console 4.2+ we need do something similar
            // so we have clean tables.
            // Not OutputFormatter::escape(): older symfony/console releases leave adjacent brackets (`<<`) unescaped.
            // A literal `\>` is escaped too, since the formatter unescapes it. Escaping is per chunk, so a cut can't
            // separate a `\` from the `<` it escapes.
            $message = implode(
                "\n",
                array_map(
                    static fn(string $chunk): string => strtr($chunk, ['<' => '\\<', '\\>' => '\\\\>']),
                    str_split($issue_data->message, 70),
                ),
            );

            $table->addRow([
                $severity,
                $issue_data->line_from,
                $issue_data->type,
                $message,
            ]);

            $current_file = $issue_data->file_name;
        }

        if ($buffer !== null) {
            $table->render();
            $output[] = $buffer->fetch();
        }

        return implode("\n", $output);
    }
}
