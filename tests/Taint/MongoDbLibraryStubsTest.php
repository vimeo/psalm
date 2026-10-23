<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stub of stubs/libraries for mongodb/mongodb, which Psalm loads because the code below declares classes of the
 * package, as its sources do
 */
final class MongoDbLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace MongoDB {
                class Collection {}
                class Database {}
            }';
    }

    /**
     * @return array<string, array{string, list<string>}>
     * @psalm-pure
     */
    #[Override]
    public static function providerTaints(): array
    {
        return [
            'collectionFiltersAndUpdates' => [
                '<?php
                    function query(MongoDB\Collection $collection): void {
                        $input = (array) $_GET["filter"];
                        $collection->find($input);
                        $collection->updateOne(["_id" => 1], $input);
                        $collection->aggregate($input);
                        $collection->find(["name" => "safe"]);
                    }',
                [
                    'TaintedNosql on line 4',
                    'TaintedNosql on line 5',
                    'TaintedNosql on line 6',
                ],
            ],
            'databaseCommands' => [
                '<?php
                    function run(MongoDB\Database $database): void {
                        $input = (array) $_GET["command"];
                        $database->command($input);
                        $database->aggregate($input);
                    }',
                [
                    'TaintedNosql on line 4',
                    'TaintedNosql on line 5',
                ],
            ],
        ];
    }
}
