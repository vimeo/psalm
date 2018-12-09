<?php
namespace Psalm\Output;

use Psalm\Config;
use Psalm\Output;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;

class Compact extends Output
{
    /**
     * {{@inheritdoc}}
     * @psalm-suppress PossiblyNullReference
     */
    public function create(): string
    {
        /** @var BufferedOutput|null $buffer */
        $buffer = null;

        /** @var Table|null $table */
        $table = null;

        /** @var string|null $current_file */
        $current_file = null;

        $output = [];
        foreach ($this->issues_data as $i => $issue_data) {
            if (!$this->show_info && $issue_data['severity'] === Config::REPORT_INFO) {
                continue;
            } elseif (is_null($current_file) || $current_file !== $issue_data['file_name']) {
                // If we're processing a new file, then wrap up the last table and render it out.
                if ($buffer !== null) {
                    $table->render();
                    $output[] = $buffer->fetch();
                }

                $output[] = 'FILE: ' . $issue_data['file_name'] . "\n";

                $buffer = new BufferedOutput();
                $table = new Table($buffer);
                $table->setColumnMaxWidth(3, 70);
                $table->setHeaders(['SEVERITY', 'LINE', 'ISSUE', 'DESCRIPTION']);
            }

            $is_error = $issue_data['severity'] === Config::REPORT_ERROR;
            if ($is_error) {
                $severity = ($this->use_color ? "\e[0;31mERROR\e[0m" : 'ERROR');
            } else {
                $severity = strtoupper($issue_data['severity']);
            }

            $table->addRow([
                $severity,
                $issue_data['line_from'],
                $issue_data['type'],
                $issue_data['message']
            ]);

            $current_file = $issue_data['file_name'];

            // If we're at the end of the issue sets, then wrap up the last table and render it out.
            if ($i === count($this->issues_data) - 1) {
                $table->render();
                $output[] = $buffer->fetch();
            }
        }

        return implode("\n", $output);
    }
}
