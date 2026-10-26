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
                class Client {}
                final class ClientBulkWrite {}
                class Collection {}
                class Database {}
            }
            namespace MongoDB\GridFS {
                class Bucket {}
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
            'clientChangeStreams' => [
                '<?php
                    function watch(MongoDB\Client $client): void {
                        $client->watch((array) $_GET["pipeline"]);
                        $client->watch([[\'$match\' => ["operationType" => "insert"]]]);
                    }',
                ['TaintedNosql on line 3'],
            ],
            'clientBulkWriteFiltersAndUpdates' => [
                '<?php
                    function write(MongoDB\ClientBulkWrite $bulk): void {
                        $input = (array) $_GET["filter"];
                        $bulk->deleteMany($input);
                        $bulk->deleteOne($input);
                        $bulk->replaceOne($input, ["name" => "safe"]);
                        $bulk->updateMany(["_id" => 1], $input);
                        $bulk->updateOne($input, [\'$set\' => ["name" => "safe"]]);
                        $bulk->replaceOne(["_id" => 1], $input);
                    }',
                [
                    'TaintedNosql on line 4',
                    'TaintedNosql on line 5',
                    'TaintedNosql on line 6',
                    'TaintedNosql on line 7',
                    'TaintedNosql on line 8',
                ],
            ],
            'gridFsFilters' => [
                '<?php
                    function files(MongoDB\GridFS\Bucket $bucket): void {
                        $input = (array) $_GET["filter"];
                        $bucket->find($input);
                        $bucket->findOne($input);
                        $bucket->find(["filename" => "safe"]);
                    }',
                [
                    'TaintedNosql on line 4',
                    'TaintedNosql on line 5',
                ],
            ],
        ];
    }
}
