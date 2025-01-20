<?php

namespace Psalm\Tests\TypeReconciliation;

use Psalm\Config;
use Psalm\Context;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ArrayKeyExistsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'arrayKeyExistsOnStringArrayShouldInformArrayness' => [
                'code' => '<?php
                    /**
                     * @param string[] $a
                     * @return array{b: string, ...}
                     */
                    function foo(array $a) {
                        if (array_key_exists("b", $a)) {
                            return $a;
                        }

                        throw new \Exception("bad");
                    }',
            ],
             'arrayKeyExistsThrice' => [
                'code' => '<?php
                    function three(array $a): void {
                        if (!array_key_exists("a", $a)
                            || !array_key_exists("b", $a)
                            || !array_key_exists("c", $a)
                            || (!is_string($a["a"]) && !is_int($a["a"]))
                            || (!is_string($a["b"]) && !is_int($a["b"]))
                            || (!is_string($a["c"]) && !is_int($a["c"]))
                        ) {
                            throw new \Exception();
                        }

                        echo $a["a"];
                        echo $a["b"];
                    }',
            ],
             'arrayKeyExistsNegation' => [
                'code' => '<?php
                    function getMethodName(array $data = []): void {
                        if (\array_key_exists("custom_name", $data) && $data["custom_name"] !== null) {
                        }
                        /** @psalm-check-type-exact $data = array<array-key, mixed> */
                    }
                ',
            ],
            'arrayKeyExistsNoSideEffects' => [
                'code' => '<?php
                    function getMethodName(array $ddata = []): void {
                        if (\array_key_exists("redirect", $ddata)) {
                            return;
                        }
                        if (random_int(0, 1)) {
                            $ddata["type"] = "test";
                        }
                        /** @psalm-check-type-exact $ddata = array<array-key, mixed> */
                    }
                ',
            ],
            'arrayKeyExistsTwice' => [
                'code' => '<?php
                    function two(array $a): void {
                        if (!array_key_exists("a", $a) || !(is_string($a["a"]) || is_int($a["a"])) ||
                            !array_key_exists("b", $a) || !(is_string($a["b"]) || is_int($a["b"]))
                        ) {
                            throw new \Exception();
                        }

                        echo $a["a"];
                        echo $a["b"];
                    }',
            ],
            'assertConstantOffsetsInMethod' => [
                'code' => '<?php
                    class C {
                        public const ARR = [
                            "a" => ["foo" => true],
                            "b" => []
                        ];

                        public function bar(string $key): bool {
                            if (!array_key_exists($key, self::ARR) || !array_key_exists("foo", self::ARR[$key])) {
                                return false;
                            }

                            return self::ARR[$key]["foo"];
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'assertSelfClassConstantOffsetsInFunction' => [
                'code' => '<?php
                    namespace Ns;

                    class C {
                        public const ARR = [
                            "a" => ["foo" => true],
                            "b" => []
                        ];

                        public function bar(?string $key): bool {
                            if ($key === null || !array_key_exists($key, self::ARR) || !array_key_exists("foo", self::ARR[$key])) {
                                return false;
                            }

                            return self::ARR[$key]["foo"];
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'assertNamedClassConstantOffsetsInFunction' => [
                'code' => '<?php
                    namespace Ns;

                    class C {
                        public const ARR = [
                            "a" => ["foo" => true],
                            "b" => [],
                        ];
                    }

                    function bar(?string $key): bool {
                        if ($key === null || !array_key_exists($key, C::ARR) || !array_key_exists("foo", C::ARR[$key])) {
                            return false;
                        }

                        return C::ARR[$key]["foo"];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'possiblyUndefinedArrayAccessWithArrayKeyExists' => [
                'code' => '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    if (array_key_exists(0, $a)) {
                        echo $a[0];
                    }',
            ],
            'arrayKeyExistsShouldNotModifyIntType' => [
                'code' => '<?php
                    class HttpError {
                        const ERRS = [
                            403 => "a",
                            404 => "b",
                            500 => "c"
                        ];
                    }

                    function init(string $code) : string {
                        if (array_key_exists($code, HttpError::ERRS)) {
                            return $code;
                        }

                        return "";
                    }',
            ],
            'arrayKeyExistsWithClassConst' => [
                'code' => '<?php
                    class C {}
                    class D {}

                    class A {
                        const FLAGS = [
                            0 => [C::class => "foo"],
                            1 => [D::class => "bar"],
                        ];

                        private function foo(int $i) : void {
                            if (array_key_exists(C::class, self::FLAGS[$i])) {
                                echo self::FLAGS[$i][C::class];
                            }
                        }
                    }',
            ],
            'constantArrayKeyExistsWithClassConstant' => [
                'code' => '<?php
                    class Foo {
                        public const F = "key";
                    }

                    /** @param array{key?: string} $a */
                    function one(array $a): void {
                        if (array_key_exists(Foo::F, $a)) {
                            echo $a[Foo::F];
                        }
                    }',
            ],
            'assertTypeNarrowedByNestedIsset' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedMethodCall
                     * @psalm-suppress MixedArgument
                     */
                    function foo(array $array = []): void {
                        if (array_key_exists("a", $array)) {
                            echo $array["a"];
                        }

                        if (array_key_exists("b", $array)) {
                            echo $array["b"]->format("Y-m-d");
                        }
                    }',
            ],
            'assertArrayKeyExistsRefinesType' => [
                'code' => '<?php
                    class Foo {
                        public const DAYS = [
                            1 => "mon",
                            2 => "tue",
                            3 => "wed",
                            4 => "thu",
                            5 => "fri",
                            6 => "sat",
                            7 => "sun",
                        ];

                        /** @param key-of<self::DAYS> $dayNum*/
                        private static function doGetDayName(int $dayNum): string {
                            return self::DAYS[$dayNum];
                        }

                        /** @throws LogicException */
                        public static function getDayName(int $dayNum): string {
                            if (! array_key_exists($dayNum, self::DAYS)) {
                                throw new \LogicException();
                            }
                            return self::doGetDayName($dayNum);
                        }
                    }',
            ],
            'arrayKeyExistsInferString' => [
                'code' => '<?php
                    function foo(mixed $file) : string {
                        /** @psalm-suppress MixedArgument */
                        if (array_key_exists($file, ["a" => 1, "b" => 2])) {
                            return $file;
                        }

                        return "";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'arrayKeyExistsComplex' => [
                'code' => '<?php
                    class A {
                        private const MAP = [
                            "a" => 1,
                            "b" => 2,
                            "c" => 3,
                            "d" => 4,
                            "e" => 5,
                            "f" => 6,
                            "g" => 7,
                            "h" => 8,
                            "i" => 9,
                            "j" => 10,
                            "k" => 11,
                        ];

                        public function doWork(string $a): void {
                            if (!array_key_exists($a, self::MAP)) {}
                        }
                    }',
            ],
            'arrayKeyExistsAccess' => [
                'code' => '<?php
                    /** @param array<int, string> $arr */
                    function foo(array $arr) : void {
                        if (array_key_exists(1, $arr)) {
                            $a = ($arr[1] === "b") ? true : false;
                        }
                    }',
            ],
            'arrayKeyExistsVariable' => [
                'code' => '<?php
                    class pony
                    {
                    }
                    /**
                     * @param array{0?: string, test?: string, pony?: string} $params
                     * @return string|null
                     */
                    function a(array $params = [])
                    {
                        foreach ([0, "test", pony::class] as $key) {
                            if (\array_key_exists($key, $params)) {
                                return $params[$key];
                            }
                        }
                    }',
            ],
            'noCrashOnArrayKeyExistsBracket' => [
                'code' => '<?php
                    class MyCollection {
                        /**
                         * @param int $commenter
                         * @param int $numToGet
                         * @return int[]
                         */
                        public function getPosters($commenter, $numToGet=10) {
                            $posters = array();
                            $count = 0;
                            $a = new ArrayObject([[1234]]);
                            $iter = $a->getIterator();
                            while ($iter->valid() && $count < $numToGet) {
                                $value = $iter->current();
                                if ($value[0] != $commenter) {
                                    if (!array_key_exists($value[0], $posters)) {
                                        $posters[$value[0]] = 1;
                                        $count++;
                                    }
                                }
                                $iter->next();
                            }
                            return array_keys($posters);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedArrayAccess', 'MixedAssignment', 'MixedArrayOffset',
                    'MixedArgument',
                ],
            ],
            'arrayKeyExistsTwoVars' => [
                'code' => '<?php
                    /**
                     * @param array{a: string, b: string, c?: string} $info
                     */
                    function getReason(array $info, string $key, string $value): bool {
                        if (array_key_exists($key, $info) && $info[$key] === $value) {
                            return true;
                        }

                        return false;
                    }',
            ],
            'allowIntKeysToo' => [
                'code' => '<?php
                    /**
                     * @param array<1|2|3, string> $arr
                     * @return 1|2|3
                     */
                    function checkArrayKeyExistsInt(array $arr, int $int): int
                    {
                        if (array_key_exists($int, $arr)) {
                            return $int;
                        }

                        return 1;
                    }',
            ],
            'comparesStringAndAllIntKeysCorrectly' => [
                'code' => '<?php
                    /**
                     * @param array<1|2|3, string> $arr
                     * @return bool
                     */
                    function checkArrayKeyExistsComparison(array $arr, string $key): bool
                    {
                        if (array_key_exists($key, $arr)) {
                            return true;
                        }
                        return false;
                    }',
            ],
            'comparesStringAndAllIntKeysCorrectlyNegated' => [
                'code' => '<?php
                    /**
                     * @param array<1|2|3, string> $arr
                     * @return bool
                     */
                    function checkArrayKeyExistsComparisonNegated(array $arr, string $key): bool
                    {
                        if (!array_key_exists($key, $arr)) {
                            return false;
                        }
                        return true;
                    }',
            ],
            'arrayKeyExistsAssertBothWays' => [
                'code' => '<?php
                    class a {
                        const STATE_A = 0;
                        const STATE_B = 1;
                        const STATE_C = 2;
                        /**
                         * @return array<self::STATE_*, non-empty-string>
                         * @psalm-pure
                         */
                        public static function getStateLabels(): array {
                            return [
                                self::STATE_A => "A",
                                self::STATE_B => "B",
                                self::STATE_C => "C",
                            ];
                        }
                        /**
                         * @param self::STATE_* $state
                         */
                        public static function getStateLabelIf(int $state): string {
                            $states = self::getStateLabels();
                            if (array_key_exists($state, $states)) {
                                return $states[$state];
                            }
                            return "";
                        }
                    }',
            ],
            'arrayKeyExistsComplex2' => [
                'code' => '<?php
                            /** @var array{
                             *			address_components: list<array{
                             *				long_name: string,
                             *				short_name: string,
                             *				types: list<("accounting"|"administrative_area_level_1"|"administrative_area_level_2"|"administrative_area_level_3"|
                             *		"administrative_area_level_4"|"administrative_area_level_5"|"airport"|"amusement_park"|"art_gallery"|"bar"|"bus_station"|"cafe"|
                             *		"campground"|"car_rental"|"cemetery"|"colloquial_area"|"continent"|"country"|"courthouse"|"embassy"|"establishment"|"finance"|
                             *		"floor"|"food"|"funeral_home"|"general_contractor"|"gym"|"health"|"hospital"|"intersection"|"lawyer"|"light_rail_station"|
                             *		"local_government_office"|"locality"|"lodging"|"moving_company"|"museum"|"natural_feature"|"neighborhood"|"night_club"|"park"|
                             *		"parking"|"plus_code"|"point_of_interest"|"police"|"political"|"post_box"|"post_office"|"postal_code"|"postal_code_prefix"|
                             *		"postal_code_suffix"|"postal_town"|"premise"|"real_estate_agency"|"restaurant"|"route"|"rv_park"|"school"|"spa"|"storage"|"store"|
                             *		"street_address"|"street_number"|"sublocality"|"sublocality_level_1"|"sublocality_level_2"|"sublocality_level_3"|
                             *		"sublocality_level_4"|"sublocality_level_5"|"subpremise"|"subway_station"|"tourist_attraction"|"town_square"|"train_station"|
                             *		"transit_station"|"travel_agency"|"university"|"ward"|"zoo")>
                             *			}>,
                             *			formatted_address: string,
                             *			geometry: array{
                             *				location: array{ lat: float, lng: float },
                             *				location_type: string,
                             *				viewport: array{
                             *					northeast: array{ lat: float, lng: float },
                             *					southwest: array{ lat: float, lng: float }
                             *				}
                             *			},
                             *			partial_match: bool,
                             *			types: list<string>
                             * }
                             */
                            $data = [];
                            $cmp = [];
                            foreach ($data["address_components"] as $component) {
                                foreach ($component["types"] as $type) {
                                    $cmp[$type] = $component["long_name"];
                                }
                            }

                            if (!\array_key_exists("locality", $cmp)) {
                                $cmp["locality"] = "";
                            }

                            if (!\array_key_exists("administrative_area_level_1", $cmp)) {
                                $cmp["administrative_area_level_1"] = "";
                            }
                            if ($cmp["administrative_area_level_1"] === "test") {
                                $cmp["administrative_area_level_1"] = "";
                            }',
            ],
            'arrayKeyExistsPoorPerformance' => [
                'code' => '<?php
                    class A {
                        private const CRITICAL_ERRORS = [
                            "category" => [],
                            "name" => [],
                            "geo" => [],
                            "city" => [],
                            "url" => [],
                            "comment_critical" => [],
                            "place" => [],
                            "price" => [],
                            "robot_error" => [],
                            "manual" => [],
                            "contacts" => [],
                            "not_confirmed_by_other_source" => [],
                        ];


                        public function isCriticalError(int|string $key): bool {
                            if (!\array_key_exists($key, A::CRITICAL_ERRORS)) {
                                return false;
                            }

                            return true;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'keyExistsAsAliasForArrayKeyExists' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @param array<string, string> $arr
                     */
                    function foo(array $arr): void {
                        if (key_exists("a", $arr)) {
                            echo $arr["a"];
                        }
                    }
                PHP,
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'possiblyUndefinedArrayAccessWithArrayKeyExistsOnWrongKey' => [
                'code' => '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    if (array_key_exists("a", $a)) {
                        echo $a[0];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'possiblyUndefinedArrayAccessWithArrayKeyExistsOnMissingKey' => [
                'code' => '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    if (array_key_exists("b", $a)) {
                        echo $a[0];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'dontCreateWeirdString' => [
                'code' => '<?php
                    /**
                     * @psalm-param array{inner:string} $options
                     */
                    function go(array $options): void {
                        if (!array_key_exists(\'size\', $options)) {
                            throw new Exception(\'bad\');
                        }

                        /** @psalm-suppress MixedArgument */
                        echo $options[\'\\\'size\\\'\'];
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
        ];
    }

    public function testAllowPropertyFetchAsNeedle(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
            class Foo {
                /** @var self::STATE_* $status */
                public int $status = self::STATE_A;
                public const STATE_A = 0;
                public const STATE_B = 1;
            }

            $foo = new Foo;

            /** @var array<string> $bar */
            $bar = [];

            if (array_key_exists($foo->status, $bar)) {
                echo $bar[$foo->status];
            }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAllowStaticPropertyFetchAsNeedle(): void
    {
        Config::getInstance()->ensure_array_int_offsets_exist = true;

        $this->addFile(
            'somefile.php',
            '<?php
            class Foo {
                /** @var self::STATE_* $status */
                public static int $status = self::STATE_A;
                public const STATE_A = 0;
                public const STATE_B = 1;
            }

            /** @var array<string> $bar */
            $bar = [];

            if (array_key_exists(Foo::$status, $bar)) {
                echo $bar[Foo::$status];
            }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
