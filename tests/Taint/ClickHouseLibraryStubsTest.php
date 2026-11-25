<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stub of stubs/libraries for smi2/phpclickhouse, which Psalm loads because the code below declares classes
 * of the package, as its sources do
 */
final class ClickHouseLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace ClickHouseDB {
                class Client {}
                class Statement {}
            }

            namespace ClickHouseDB\Quote {
                class FormatLine {}
                class ValueFormatter {}
            }

            namespace ClickHouseDB\Query {
                class WhereInFile {}
                class WriteToFile {}
            }

            namespace ClickHouseDB\Transport {
                class CurlerRequest {}
                class Stream {}
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
            'query' => [
                '<?php
                    $client = new ClickHouseDB\Client();
                    $client->select("SELECT * FROM t WHERE a = \'" . (string) $_GET["a"] . "\'");
                    $client->select("SELECT * FROM t WHERE a = :a", ["a" => (string) $_GET["a"]]);
                    $client->insert((string) $_GET["table"], [[1]]);',
                [
                    'TaintedSql on line 3',
                    'TaintedSql on line 5',
                ],
            ],
            'formattedValue' => [
                '<?php
                    use ClickHouseDB\Quote\ValueFormatter;

                    $client = new ClickHouseDB\Client();
                    $value = (string) $_GET["value"];
                    $client->write("INSERT INTO t VALUES (" . ValueFormatter::formatValue($value, true) . ")");
                    $client->write("INSERT INTO t VALUES (" . ValueFormatter::formatValue($value, false) . ")");',
                [
                    'TaintedSql on line 7',
                ],
            ],
            'storedRows' => [
                '<?php
                    $client = new ClickHouseDB\Client();
                    $client->insert("visits", [["page" => "home", "agent" => (string) $_GET["agent"]]], ["page", "agent"]);
                    $client->insertAssocBulk("clicks", ["page" => "home", "agent" => (string) $_GET["agent"]]);
                    function select(ClickHouseDB\Client $client, string $sql): void { $client->select($sql); }
                    foreach ($client->select("SELECT page, agent FROM visits")->rows() as $row) {
                        echo (string) $row["page"];
                        echo (string) $row["agent"];
                    }
                    echo (string) $client->select("SELECT page FROM clicks FINAL")->rows()[0]["page"];
                    echo (string) $client->select("SELECT agent FROM clicks FINAL")->fetchOne("agent");',
                [
                    'TaintedHtml on line 11',
                    'TaintedHtml on line 8',
                    'TaintedTextWithQuotes on line 11',
                    'TaintedTextWithQuotes on line 8',
                ],
            ],
            'outputFile' => [
                '<?php
                    new ClickHouseDB\Query\WriteToFile((string) $_GET["file"]);',
                [
                    'TaintedFile on line 2',
                ],
            ],
        ];
    }
}
