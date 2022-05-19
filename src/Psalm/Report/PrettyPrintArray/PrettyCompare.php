<?php

namespace Psalm\Report\PrettyPrintArray;

use function count;
use function explode;
use function join;
use function sprintf;

use const PHP_EOL;

final class PrettyCompare
{
    public function compare(string $inferred, string $declared): string
    {
        $formatTable = '| %-50s | %-50s ';

        $requested = explode(PHP_EOL, $inferred);
        $provided = explode(PHP_EOL, $declared);

        $maxOf = count($requested) > count($provided) ? count($requested) : count($provided);
        $indexOne = 0;
        $paired = [];

        for ($indexTwo = 0; $indexTwo <= $maxOf; $indexTwo++) {
            $rowProvided = $provided[$indexTwo] ?? '';

            if (isset($requested[$indexOne]) && $requested[$indexOne] !== '') {
                $paired[] = [$requested[$indexOne], $rowProvided]; //tuple
            } else {
                $paired[] = ['', $rowProvided]; //tuple
            }
            $indexOne++;
        }

        if (!count($paired)) {
            return '';
        }

        $pairedFormattedResult[] = '|';
        $pairedFormattedResult[] = sprintf($formatTable, 'Expected', 'Provided');
        $pairedFormattedResult[] = sprintf($formatTable, '---', '---');

        foreach ($paired as $k => $rows) {
            $pairedFormattedResult[] = sprintf($formatTable, ...$rows);
        }

        return join(PHP_EOL, $pairedFormattedResult);
    }
}
