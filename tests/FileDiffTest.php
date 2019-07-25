<?php
namespace Psalm\Tests;

use function array_map;
use function count;
use function get_class;
use PhpParser;
use function strpos;
use function var_export;

class FileDiffTest extends TestCase
{
    /**
     * @dataProvider getChanges
     *
     * @param string $a
     * @param string $b
     * @param string[] $same_methods
     *
     * @return void
     */
    public function testCode(
        string $a,
        string $b,
        array $same_methods,
        array $same_signatures,
        array $changed_methods,
        array $diff_map_offsets
    ) {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $a_stmts = \Psalm\Internal\Provider\StatementsProvider::parseStatements($a);
        $b_stmts = \Psalm\Internal\Provider\StatementsProvider::parseStatements($b);

        $diff = \Psalm\Internal\Diff\FileStatementsDiffer::diff($a_stmts, $b_stmts, $a, $b);

        $this->assertSame(
            $same_methods,
            $diff[0]
        );

        $this->assertSame(
            $same_signatures,
            $diff[1]
        );

        $this->assertSame(
            $changed_methods,
            $diff[2]
        );

        $this->assertSame(count($diff_map_offsets), count($diff[3]));

        $found_offsets = array_map(
            /**
             * @param array{0: int, 1: int, 2: int, 3: int} $arr
             *
             * @return array{0: int, 1: int}
             */
            function (array $arr) {
                return [$arr[2], $arr[3]];
            },
            $diff[3]
        );

        $this->assertSame($diff_map_offsets, $found_offsets);
    }

    /**
     * @dataProvider getChanges
     *
     * @param string $a
     * @param string $b
     * @param string[] $same_methods
     * @param string[] $same_signatures
     * @param string[] $changed_methods
     * @param array<array-key,array{int,int}> $diff_map_offsets
     *
     * @return void
     */
    public function testPartialAstDiff(
        string $a,
        string $b,
        array $same_methods,
        array $same_signatures,
        array $changed_methods,
        array $diff_map_offsets
    ) {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $file_changes = \Psalm\Internal\Diff\FileDiffer::getDiff($a, $b);

        $a_stmts = \Psalm\Internal\Provider\StatementsProvider::parseStatements($a);

        $traverser = new PhpParser\NodeTraverser;
        $traverser->addVisitor(new \Psalm\Internal\Visitor\CloningVisitor);
        // performs a deep clone
        /** @var array<int, PhpParser\Node\Stmt> */
        $a_stmts_copy = $traverser->traverse($a_stmts);

        $this->assertTreesEqual($a_stmts, $a_stmts_copy);

        $b_stmts = \Psalm\Internal\Provider\StatementsProvider::parseStatements($b, null, $a, $a_stmts_copy, $file_changes);
        $b_clean_stmts = \Psalm\Internal\Provider\StatementsProvider::parseStatements($b);

        $this->assertTreesEqual($b_clean_stmts, $b_stmts);

        $diff = \Psalm\Internal\Diff\FileStatementsDiffer::diff($a_stmts, $b_clean_stmts, $a, $b);

        $this->assertSame(
            $same_methods,
            $diff[0]
        );

        $this->assertSame(
            $same_signatures,
            $diff[1]
        );

        $this->assertSame(
            $changed_methods,
            $diff[2]
        );

        $this->assertSame(count($diff_map_offsets), count($diff[3]));

        $found_offsets = array_map(
            /**
             * @param array{0: int, 1: int, 2: int, 3: int} $arr
             *
             * @return array{0: int, 1: int}
             */
            function (array $arr) {
                return [$arr[2], $arr[3]];
            },
            $diff[3]
        );

        $this->assertSame($diff_map_offsets, $found_offsets);
    }

    /**
     * @param  array<int, PhpParser\Node\Stmt>  $a
     * @param  array<int, PhpParser\Node\Stmt>  $b
     *
     * @return void
     */
    private function assertTreesEqual(array $a, array $b)
    {
        $this->assertSame(count($a), count($b));

        foreach ($a as $i => $a_stmt) {
            $b_stmt = $b[$i];

            $this->assertNotSame($a_stmt, $b_stmt);

            $this->assertSame(get_class($a_stmt), get_class($b_stmt));

            if ($a_stmt instanceof PhpParser\Node\Stmt\Expression
                && $b_stmt instanceof PhpParser\Node\Stmt\Expression
            ) {
                $this->assertSame(get_class($a_stmt->expr), get_class($b_stmt->expr));
            }

            if ($a_doc = $a_stmt->getDocComment()) {
                $b_doc = $b_stmt->getDocComment();

                $this->assertNotNull($b_doc, var_export($a_doc, true));

                $this->assertNotSame($a_doc, $b_doc);

                $this->assertSame($a_doc->getLine(), $b_doc->getLine());
            }

            $this->assertSame(
                $a_stmt->getAttribute('startFilePos'),
                $b_stmt->getAttribute('startFilePos')
            );
            $this->assertSame(
                $a_stmt->getAttribute('endFilePos'),
                $b_stmt->getAttribute('endFilePos'),
                ($a_stmt instanceof PhpParser\Node\Stmt\Expression
                    ? get_class($a_stmt->expr)
                    : get_class($a_stmt))
                    . ' on line ' . $a_stmt->getLine()
            );
            $this->assertSame($a_stmt->getLine(), $b_stmt->getLine());

            if (isset($a_stmt->stmts)) {
                $this->assertTrue(isset($b_stmt->stmts));

                /**
                 * @psalm-suppress UndefinedPropertyFetch
                 * @psalm-suppress MixedArgument
                 */
                $this->assertTreesEqual($a_stmt->stmts, $b_stmt->stmts);
            }
        }
    }

    /**
     * @return array<string,array{string,string,string[],string[],string[],array<array-key,array{int,int}>}>
     */
    public function getChanges()
    {
        return [
            'sameFile' => [
                '<?php
                namespace Foo;

                class A {
                    public $aB = 5;

                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public $aB = 5;

                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::$aB', 'foo\a::F', 'foo\a::foo', 'foo\a::bar'],
                [],
                [],
                [[0, 0], [0, 0], [0, 0], [0, 0]],
            ],
            'sameFileWithDoubleDocblocks' => [
                '<?php
                namespace Foo;

                class A {
                    public $aB = 5;

                    const F = 1;

                    /*
                     * another
                     */
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    // this is one line
                    // this is another
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public $aB = 5;

                    const F = 1;

                    /*
                     * another
                     */
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    // this is one line
                    // this is another
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::$aB', 'foo\a::F', 'foo\a::foo', 'foo\a::bar'],
                [],
                [],
                [[0, 0], [0, 0], [0, 0], [0, 0]],
            ],
            'lineChanges' => [
                '<?php
                namespace Foo;

                class A {
                    public $aB = 5;

                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {

                    public $aB = 5;


                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }



                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::$aB', 'foo\a::F', 'foo\a::foo', 'foo\a::bar'],
                [],
                [],
                [[1, 1], [2, 2], [2, 2], [5, 5]],
            ],
            'simpleBodyChangeWithoutSignatureChange' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() : void {
                        $a = 1;
                    }
                    public function bar() : void {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() : void {
                        $a = 12;
                    }
                    public function bar() : void {
                        $b = 1;
                    }
                }',
                ['foo\a::bar'],
                ['foo\a::foo'],
                [],
                [[1, 0]],
            ],
            'simpleBodyChangesWithoutSignatureChange' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() : void {
                        $a = 1;
                    }
                    public function bar() : void {
                        $c = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() : void {
                        $a = 1;
                        $b = 2;
                    }
                    public function bar() : void {
                        $c = 1;
                    }
                }',
                ['foo\a::bar'],
                ['foo\a::foo'],
                [],
                [[32, 1]],
            ],
            'simpleBodyChangeWithSignatureChange' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar(string $a) {
                        $b = 1;
                    }
                }',
                ['foo\a::foo'],
                [],
                ['foo\a::bar', 'foo\a::bar'],
                [[0, 0]],
            ],
            'propertyChange' => [
                '<?php
                namespace Foo;

                class A {
                    public $a;
                }',
                '<?php
                namespace Foo;

                class A {
                    public $b;
                }',
                [],
                [],
                ['foo\a::$a', 'foo\a::$b'],
                [],
            ],
            'propertyDefaultChange' => [
                '<?php
                namespace Foo;

                class A {
                    public $a = 1;
                }',
                '<?php
                namespace Foo;

                class A {
                    public $a = 2;
                }',
                [],
                ['foo\a::$a'],
                [],
                [],
            ],
            'propertyDefaultAddition' => [
                '<?php
                namespace Foo;

                class A {
                    public $a;
                }',
                '<?php
                namespace Foo;

                class A {
                    public $a = 2;
                }',
                [],
                ['foo\a::$a'],
                [],
                [],
            ],
            'propertySignatureChange' => [
                '<?php
                namespace Foo;

                class A {
                    /** @var ?string */
                    public $a;
                }',
                '<?php
                namespace Foo;

                class A {
                    /** @var ?int */
                    public $a;
                }',
                [],
                [],
                ['foo\a::$a', 'foo\a::$a'],
                [],
            ],
            'propertyStaticChange' => [
                '<?php
                namespace Foo;

                class A {
                    /** @var ?string */
                    public static $a;
                }',
                '<?php
                namespace Foo;

                class A {
                    /** @var ?string */
                    public $a;
                }',
                [],
                [],
                ['foo\a::$a', 'foo\a::$a'],
                [],
            ],
            'propertyVisibilityChange' => [
                '<?php
                namespace Foo;

                class A {
                    /** @var ?string */
                    public $a;
                }',
                '<?php
                namespace Foo;

                class A {
                    /** @var ?string */
                    private $a;
                }',
                [],
                [],
                ['foo\a::$a', 'foo\a::$a'],
                [],
            ],
            'addDocblockToFirst' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::bar'],
                [],
                ['foo\a::foo', 'foo\a::foo'],
                [[84, 3]],
            ],
            'addDocblockToSecond' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::foo'],
                [],
                ['foo\a::bar', 'foo\a::bar'],
                [[0, 0]],
            ],
            'removeDocblock' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 2;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::foo'],
                [],
                ['foo\a::bar', 'foo\a::bar'],
                [[0, 0]],
            ],
            'changeDocblock' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    /**
                     * @return string
                     */
                    public function bar() {
                        $b = 2;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::foo'],
                [],
                ['foo\a::bar', 'foo\a::bar'],
                [[0, 0]],
            ],
            'changeMethodVisibility' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 2;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    private function bar() {
                        $b = 2;
                    }
                }',
                ['foo\a::foo'],
                [],
                ['foo\a::bar', 'foo\a::bar'],
                [[0, 0]],
            ],
            'removeFunctionAtEnd' => [
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bat() {
                        $c = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::foo', 'foo\a::bar'],
                [],
                ['foo\a::bat'],
                [[0, 0], [0, 0]],
            ],
            'addSpaceInFunction' => [
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                        $b = 2;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $c = 3;
                        $d = 4;

                        if (true) {

                        }
                    }

                    /**
                     * @return void
                     */
                    public function bat() {
                        $e = 5;
                        $f = 6;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                        $b = 2;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $c = 3;




                        $d = 4;

                        if (true) {

                        }
                    }

                    /**
                     * @return void
                     */
                    public function bat() {
                        $e = 5;
                        $f = 6;
                    }
                }',
                ['foo\a::foo', 'foo\a::bat'],
                ['foo\a::bar'],
                [],
                [[0, 0], [4, 4]],
            ],
            'removeSpaceInFunction' => [
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                        $b = 2;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $c = 3;




                        $d = 4;

                        if (true) {

                        }
                    }

                    /**
                     * @return void
                     */
                    public function bat() {
                        $e = 5;
                        $f = 6;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                        $b = 2;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $c = 3;
                        $d = 4;

                        if (true) {

                        }
                    }

                    /**
                     * @return void
                     */
                    public function bat() {
                        $e = 5;
                        $f = 6;
                    }
                }',
                ['foo\a::foo', 'foo\a::bat'],
                ['foo\a::bar'],
                [],
                [[0, 0], [-4, -4]],
            ],
            'removeFunctionAtBeginning' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function bar() {
                        $b = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                }',
                ['foo\a::bar', 'foo\a::bat'],
                [],
                ['foo\a::foo'],
                [[-98, -3], [-98, -3]],
            ],
            'removeFunctionInMiddle' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bat() {
                        $c = 1;
                    }
                }',
                ['foo\a::foo', 'foo\a::bat'],
                [],
                ['foo\a::bar'],
                [[0, 0], [-98, -3]],
            ],
            'changeNamespace' => [
                '<?php
                namespace Bar;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    public function bar() {
                        $b = 2;
                    }
                }',
                [],
                [],
                [],
                [],
            ],
            'removeNamespace' => [
                '<?php
                namespace Bar;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                class A {
                    public function bar() {
                        $b = 2;
                    }
                }',
                [],
                [],
                [],
                [],
            ],
            'newFunctionAtEnd' => [
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bat() {
                        $c = 1;
                    }
                }',
                ['foo\a::foo', 'foo\a::bar'],
                [],
                ['foo\a::bat'],
                [[0, 0], [0, 0]],
            ],
            'newFunctionAtBeginning' => [
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function bat() {
                        $c = 1;
                    }

                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::foo', 'foo\a::bar'],
                [],
                ['foo\a::bat'],
                [[183, 7], [183, 7]],
            ],
            'newFunctionInMiddle' => [
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bat() {
                        $c = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\a::foo', 'foo\a::bar'],
                [],
                ['foo\a::bat'],
                [[0, 0], [183, 7]],
            ],
            'removeAdditionalComments' => [
                '<?php
                namespace Foo;

                class A {
                    /**
                     * more Comments
                     *
                     * @return void
                     */
                    public function foo() {
                        $a = 1;
                    }

                    /**
                     * more Comments
                     *
                     * @return void
                     */
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                use D;
                use E;

                class A {
                    /**
                     * @return void
                     */
                    public function foo() {
                        $c = 1;
                    }

                    /**
                     * @return void
                     */
                    public function bar() {
                        $a = 1;
                    }
                }',
                [],
                [],
                ['use:D', 'use:E', 'foo\a::foo', 'foo\a::bar', 'foo\a::foo', 'foo\a::bar'],
                [],
            ],
            'SKIPPED-whiteSpaceOnly' => [
                '<?php
                namespace Foo;

                class A {
                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                    namespace Foo;
                 class A {
                    public function foo() {

                            $a  = 1  ;
                    }

                    public function bar() {
                          $b  =   1;

                    }
                }',
                ['foo\a::foo', 'foo\a::bar'],
                [],
                [],
                [],
            ],
            'changeDeclaredMethodId' => [
                '<?php
                    namespace Foo;

                    class A {
                        public function __construct() {}
                        public static function bar() : void {}
                    }

                    class B extends A {
                        public static function bat() : void {}
                    }

                    class C extends B { }',
                '<?php
                    namespace Foo;

                    class A {
                        public function __construct() {}
                        public static function bar() : void {}
                    }

                    class B extends A {
                        public function __construct() {}
                        public static function bar() : void {}
                        public static function bat() : void {}
                    }

                    class C extends B { }',
                ['foo\a::__construct', 'foo\a::bar', 'foo\b::bat'],
                [],
                ['foo\b::__construct', 'foo\b::bar'],
                [[0, 0], [0, 0], [120, 2]],
            ],
            'sameTrait' => [
                '<?php
                namespace Foo;

                trait T {
                    public $aB = 5;

                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                '<?php
                namespace Foo;

                trait T {
                    public $aB = 5;

                    const F = 1;

                    public function foo() {
                        $a = 1;
                    }
                    public function bar() {
                        $b = 1;
                    }
                }',
                ['foo\t::$aB', 'foo\t::F', 'foo\t::foo', 'foo\t::bar'],
                [],
                [],
                [[0, 0], [0, 0], [0, 0], [0, 0]],
            ],
            'traitPropertyChange' => [
                '<?php
                namespace Foo;

                trait T {
                    public $a;
                }',
                '<?php
                namespace Foo;

                trait T {
                    public $b;
                }',
                [],
                [],
                ['foo\t::$a', 'foo\t::$b'],
                [],
            ],
            'traitMethodReturnTypeChange' => [
                '<?php
                    namespace Foo;

                    trait T {
                        public function barBar(): string {
                            return "hello";
                        }

                        public function bat(): string {
                            return "hello";
                        }
                    }',
                '<?php
                    namespace Foo;

                    trait T {
                        public function barBar(): int {
                            return 5;
                        }

                        public function bat(): string {
                            return "hello";
                        }
                    }',
                ['foo\t::bat'],
                [],
                ['foo\t::barbar', 'foo\t::barbar'],
                [[-9, 0]],
            ],
            'removeManyArguments' => [
                '<?php
                    namespace Foo;

                    class C {
                        public function barBar() {
                            foo(
                                $a,
                                $b
                            );
                        }

                        public function bat() {
                            return "hello";
                        }
                    }',
                '<?php
                    namespace Foo;

                    class C {
                        public function barBar() {
                            foo(
                                $a
                            );
                        }

                        public function bat() {
                            return "hello";
                        }
                    }',
                ['foo\c::bat'],
                ['foo\c::barbar'],
                [],
                [[-36, -1]],
            ],
            'docblockTwiceOver' => [
                '<?php
                    namespace Bar;

                    class Foo
                    {
                        public function a()
                        {
                            return 5;
                        }

                        /**
                         * @return bool
                         */
                        public function c()
                        {
                            return true;
                        }
                    }',
                '<?php
                    namespace Bar;

                    class Foo
                    {
                        public function a()
                        {
                            return 5;
                        }

                        /**
                         * @return void
                         */
                        public function b()
                        {
                            $a = 1;
                        }

                        /**
                         * @return bool
                         */
                        public function c()
                        {
                            return true;
                        }
                    }',
                ['bar\foo::a', 'bar\foo::c'],
                [],
                ['bar\foo::b'],
                [[0, 0], [229, 8]],
            ],
            'removeStatementsAbove' => [
                '<?php
                    namespace A;

                    class B
                    {
                        /**
                         * @return void
                         */
                        public static function foo() {
                            echo 4;
                            echo 5;
                        }

                        /**
                         * @return void
                         */
                        public static function bar() {
                            echo 4;
                            echo 5;
                        }
                    }',
                '<?php
                    namespace A;

                    class B
                    {
                        /**
                         * @return void
                         */
                        public static function foo() {
                            echo 5;
                        }

                        /**
                         * @return void
                         */
                        public static function bar() {
                            echo 5;
                        }
                    }',
                [],
                [
                    'a\b::foo',
                    'a\b::bar',
                ],
                [],
                [],
            ],
            'removeUse' => [
                '<?php
                    namespace Foo;

                    use Exception;

                    class A {
                        public function foo() : void {
                            throw new Exception();
                        }
                    }',
                '<?php
                    namespace Foo;

                    class A {
                        public function foo() : void {
                            throw new Exception();
                        }
                    }',
                ['foo\a::foo'],
                [],
                ['use:Exception'],
                [[-36, -2]],
            ],
            'addDocblockToFirstFunctionStatement' => [
                '<?php
                    namespace Foo;

                    class C {
                        public function foo(array $a) : void {
                            foreach ($a as $b) {
                                $b->bar();
                            }
                        }
                    }',
                '<?php
                    namespace Foo;

                    class C {
                        public function foo(array $a) : void {
                            /**
                             * @psalm-suppress MixedAssignment
                             */
                            foreach ($a as $b) {
                                $b->bar();
                            }
                        }
                    }',
                [],
                ['foo\c::foo'],
                [],
                [],
            ],
            'vimeoDiff' => [
                '<?php
                    namespace C;

                    class A extends B
                    {
                        /**
                         * Another thing
                         *
                         * @return D
                         */
                        public function foo() {
                            $a = 1;
                            $b = 2;
                        }

                        /**
                         * Other thing
                         *
                         * @return D
                         */
                        public function bar() {
                            $a = 1;
                            $b = 2;
                        }

                        /**
                         * Some thing
                         *
                         * @return D
                         */
                        public function zap() {
                            $a = 1;
                            $b = 2;
                        }

                        /**
                         * @return Foo
                         */
                        private function top(
                            D $c
                        ) {
                            return $c;
                        }
                    }
                    ',
                '<?php
                    namespace C;

                    class A extends B
                    {
                        /**
                         * @return D
                         */
                        public function rot() {
                            $c = 1;
                        }

                        /**
                         * @return D
                         */
                        public function bar() {
                            return $c;
                        }
                    }',
                [],
                [],
                ['c\a::foo', 'c\a::bar', 'c\a::zap', 'c\a::top', 'c\a::rot', 'c\a::bar'],
                [],
            ],
            'noUseChange' => [
                '<?php
                    namespace A;

                    use PhpParser;
                    use Psalm\Aliases;

                    class C
                    {
                        /**
                         * @return D
                         */
                        public function foo() {
                            $c = 1;
                        }
                    }
                    ',
                '<?php
                    namespace A;

                    use PhpParser;
                    use Psalm\Aliases;

                    class C
                    {
                        /**
                         * @return D
                         */
                        public function foo() {
                            $d = 1;
                        }
                    }
                    ',
                [],
                ['a\c::foo'],
                [],
                [],
            ],
            'diffMultipleBadDocblocks' => [
                '<?php
                    namespace Foo;

                    class A
                    {
                        /**
                         * @param string $s
                         * @param string $t
                         * @return Database
                         */
                        public static function foo()
                        {
                            return D::eep();
                        }

                        /**
                         * @param string $s
                         * @param string $t
                         * @return bool
                         */
                        public static function bar()
                        {
                            return 2;
                        }

                        /**
                         * @return C|null
                         */
                        public static function bat()
                        {
                            return 1;
                        }
                    }
                    ',
                    '<?php
                    namespace Foo;

                    class A
                    {
                        /**
                         * @param string $s
                         * @param string
                         * @return Database
                         */
                        public static function foo()
                        {
                            return D::eep();
                        }

                        /**
                         * @param string $s
                         * @param string
                         * @return bool
                         */
                        public static function bar()
                        {
                            return 2;
                        }

                        /**
                         * @return C|null
                         */
                        public static function bat()
                        {
                            return 1;
                        }
                    }
                    ',
                ['foo\a::bat'],
                [],
                ['foo\a::foo', 'foo\a::bar', 'foo\a::foo', 'foo\a::bar'],
                [[-6, 0]],
            ],
        ];
    }
}
