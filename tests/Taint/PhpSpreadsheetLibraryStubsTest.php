<?php

declare(strict_types=1);

namespace Psalm\Tests\Taint;

use Override;

/**
 * The stub of stubs/libraries for phpoffice/phpspreadsheet, which Psalm loads because the code below declares classes
 * of the package, as its sources do
 */
final class PhpSpreadsheetLibraryStubsTest extends LibraryStubsTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    protected static function getLibraries(): string
    {
        return '<?php
            namespace PhpOffice\PhpSpreadsheet {
                abstract class IOFactory {}
                class Spreadsheet {}
            }

            namespace PhpOffice\PhpSpreadsheet\Reader {
                interface IReader {}
                interface IReader2 extends IReader {}
                abstract class BaseReader implements IReader2 {}
            }

            namespace PhpOffice\PhpSpreadsheet\Writer {
                interface IWriter {}
                abstract class BaseWriter implements IWriter {}
                class Xlsx extends BaseWriter {}
                class Csv extends BaseWriter {}
            }

            namespace PhpOffice\PhpSpreadsheet\Style {
                abstract class Supervisor {}
                class NumberFormat extends Supervisor {}
            }

            namespace PhpOffice\PhpSpreadsheet\Cell {
                class Cell implements \Stringable {
                    public function __toString(): string {
                        return "";
                    }
                }
            }

            namespace PhpOffice\PhpSpreadsheet\Worksheet {
                class Worksheet {}
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
            'files' => [
                '<?php
                    use PhpOffice\PhpSpreadsheet\IOFactory;
                    use PhpOffice\PhpSpreadsheet\Writer\IWriter;

                    function convert(IWriter $writer): void {
                        IOFactory::load((string) $_GET["in"]);
                        $writer->save((string) $_GET["out"]);
                    }',
                [
                    'TaintedFile on line 6',
                    'TaintedFile on line 7',
                ],
            ],
            'cellValues' => [
                '<?php
                    use PhpOffice\PhpSpreadsheet\Cell\Cell;
                    use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

                    function show(Cell $cell, Worksheet $sheet): void {
                        echo $cell->getValueString();
                        foreach ($sheet->toArray() as $row) {
                            echo (string) $row[0];
                        }
                    }',
                [
                    'TaintedHtml on line 6',
                    'TaintedHtml on line 8',
                    'TaintedTextWithQuotes on line 6',
                    'TaintedTextWithQuotes on line 8',
                ],
            ],
        ];
    }
}
