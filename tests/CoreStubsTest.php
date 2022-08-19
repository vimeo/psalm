<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class CoreStubsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'RecursiveArrayIterator::CHILD_ARRAYS_ONLY (#6464)' => [
            '<?php

            new RecursiveArrayIterator([], RecursiveArrayIterator::CHILD_ARRAYS_ONLY);'
        ];
        yield 'proc_open() named arguments' => [
            '<?php

            proc_open(
                command: "ls",
                descriptor_spec: [],
                pipes: $pipes,
                cwd: null,
                env_vars: null,
                options: null
            );',
            'assertions' => [],
            'error_levels' => [],
            'php_version' => '8.0',
        ];
        yield 'Iterating over \DatePeriod (#5954) PHP7 Traversable' => [
            '<?php

            $period = new DatePeriod(
                new DateTimeImmutable("now"),
                DateInterval::createFromDateString("1 day"),
                new DateTime("+1 week")
            );
            $dt = null;
            foreach ($period as $dt) {
                echo $dt->format("Y-m-d");
            }',
            'assertions' => [
                '$period' => 'DatePeriod<DateTimeImmutable>',
                '$dt' => 'DateTimeInterface|null'
            ],
            'error_levels' => [],
            'php_version' => '7.3',
        ];
        yield 'Iterating over \DatePeriod (#5954) PHP8 IteratorAggregate' => [
            '<?php

            $period = new DatePeriod(
                new DateTimeImmutable("now"),
                DateInterval::createFromDateString("1 day"),
                new DateTime("+1 week")
            );
            $dt = null;
            foreach ($period as $dt) {
                echo $dt->format("Y-m-d");
            }',
            'assertions' => [
                '$period' => 'DatePeriod<DateTimeImmutable>',
                '$dt' => 'DateTimeImmutable|null'
            ],
            'error_levels' => [],
            'php_version' => '8.0',
        ];
        yield 'Iterating over \DatePeriod (#5954), ISO string' => [
            '<?php

            $period = new DatePeriod("R4/2012-07-01T00:00:00Z/P7D");
            $dt = null;
            foreach ($period as $dt) {
                echo $dt->format("Y-m-d");
            }',
            'assertions' => [
                '$period' => 'DatePeriod<string>',
                '$dt' => 'DateTime|null'
            ],
            'error_levels' => [],
            'php_version' => '8.0',
        ];
        yield 'DatePeriod implements IteratorAggregate on PHP 8' => [
            '<?php
            $period = new DatePeriod("R4/2012-07-01T00:00:00Z/P7D");
            $iterator = $period->getIterator();',
            'assertions' => ['$iterator' => 'Traversable<int, DateTime>&Iterator'],
            'error_levels' => [],
            'php_version' => '8.0',
        ];
        yield 'DatePeriod implements only Traversable on PHP 7' => [
        '<?php
            $period = new DatePeriod("R4/2012-07-01T00:00:00Z/P7D");
            $iterator = $period->getIterator();',
            'assertions' => ['$iterator' => 'Traversable<empty, empty>'],
            'error_levels' => [],
            'php_version' => '7.3',
         ];
    }
}
