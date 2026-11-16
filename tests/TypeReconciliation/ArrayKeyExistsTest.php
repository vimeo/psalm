<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ArrayKeyExistsTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'arrayKeyExistsOfAKeyThatIsNotAnIntOrAStringKeepsItsType' => [
                'code' => '<?php
                    function ofMixed(mixed $key): void {
                        $items = [971 => "x", 972 => "y"];

                        if (array_key_exists($key, $items)) {
                            /** @psalm-check-type-exact $key = mixed */
                        }
                    }

                    function ofFloatOrString(float|string $key): void {
                        $items = [971 => "x", 972 => "y"];

                        if (array_key_exists($key, $items)) {
                            /** @psalm-check-type-exact $key = float|string */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'InvalidScalarArgument'],
                'php_version' => '8.1',
            ],
            'arrayKeyExistsOfAStringInAnArrayOfIntKeysNarrowsItToTheKeysAsStrings' => [
                'code' => '<?php
                    final class Codes {
                        public const MAP = ["AA" => "971", "BB" => "972"];
                    }

                    function lookup(string $zip): string {
                        $prefix = substr($zip, 0, 3);
                        $flipped = array_flip(Codes::MAP);

                        if (array_key_exists($prefix, $flipped)) {
                            /** @psalm-check-type-exact $prefix = "971"|"972" */
                            return $flipped[$prefix];
                        }

                        return "XX";
                    }

                    function ofInts(string|int $key): string {
                        $items = [971 => "x", 972 => "y"];

                        if (array_key_exists($key, $items)) {
                            /** @psalm-check-type-exact $key = "971"|"972"|971|972 */
                            return $items[$key];
                        }

                        return "z";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'negatedKeyCheckKeepsWhatTheOtherOffsetsOfAnArrayHold' => [
                'code' => '<?php
                    function f(array $options): string {
                        if (!isset($options["b"]) || !is_string($options["b"])) {
                            return "";
                        }
                        if (isset($options["a"])) {
                            return "a";
                        } else {
                            /** @psalm-check-type-exact $options[\'b\'] = string */;
                            return $options["b"];
                        }
                    }

                    /** @param array<string, int|string> $options */
                    function g(array $options): string {
                        $options["b"] = "x";
                        if (!array_key_exists("a", $options)) {
                            /** @psalm-check-type-exact $options[\'b\'] = \'x\' */;
                            return $options["b"];
                        }
                        return "";
                    }',
            ],
            'negatedArrayKeyExistsRemovesAnOptionalKey' => [
                'code' => '<?php
                    /**
                     * @param array{phone: string, descr?: string} $a
                     * @return array{phone: string}
                     */
                    function withoutDescr(array $a): array {
                        if (array_key_exists("descr", $a)) {
                            throw new \Exception();
                        }
                        return $a;
                    }',
            ],
            'negatedIssetRemovesAnOptionalKeyThatCantBeNull' => [
                'code' => '<?php
                    /**
                     * @param array{phone: string, descr?: string} $a
                     * @return array{phone: string}
                     */
                    function withoutDescr(array $a): array {
                        if (!isset($a["descr"])) {
                            return $a;
                        }
                        throw new \Exception();
                    }',
            ],
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
                'ignored_issues' => ['MixedReturnStatement'],
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
                'ignored_issues' => ['MixedReturnStatement'],
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
                'ignored_issues' => ['MixedReturnStatement'],
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
                    'MixedArgument', 'PossiblyNullArrayAccess', 'PossiblyNullArgument',
                    'PossiblyNullArrayOffset', 'InvalidReturnStatement', 'InvalidReturnType',
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
            'arrayKeyExistsNegatedAssignInForeachOverLiterals' => [
                'code' => '<?php
                    /** @return array<string, int> */
                    function a(): array {
                        $greetings = [];
                        foreach (["a", "b"] as $text) {
                            if (!\array_key_exists($text, $greetings)) {
                                $greetings[$text] = 123;
                            }
                        }
                        return $greetings;
                    }',
            ],
            'arrayKeyExistsOfAUnionOfLiteralKeysDoesNotRequireEachKey' => [
                'code' => '<?php
                    /**
                     * @param array{foo?: "hello", bar?: "world"} $optionalKeys
                     * @param "foo"|"bar"|"baz" $key
                     */
                    function stringKeys(array $optionalKeys, string $key): void {
                        if (array_key_exists($key, $optionalKeys)) {
                            /** @psalm-check-type-exact $optionalKeys = array{bar?: \'world\', baz?: \'hello\'|\'world\', foo?: \'hello\'} */;
                        }
                    }

                    /**
                     * @param array{1?: "a", 2?: "b"} $optionalKeys
                     * @param 1|2 $key
                     */
                    function intKeys(array $optionalKeys, int $key): void {
                        if (array_key_exists($key, $optionalKeys)) {
                            /** @psalm-check-type-exact $optionalKeys = array{1?: \'a\', 2?: \'b\'} */;
                        }
                    }',
            ],
            'arrayKeyExistsOfASingleLiteralKeyRequiresIt' => [
                'code' => '<?php
                    /**
                     * @param array{foo?: "hello", bar?: "world"} $optionalKeys
                     * @param "foo" $key
                     */
                    function stringKey(array $optionalKeys, string $key): void {
                        if (array_key_exists($key, $optionalKeys)) {
                            /** @psalm-check-type-exact $optionalKeys = array{bar?: \'world\', foo: \'hello\'} */;
                        }
                    }

                    /**
                     * @param array{1?: "a", 2?: "b"} $optionalKeys
                     * @param 2 $key
                     */
                    function intKey(array $optionalKeys, int $key): void {
                        if (array_key_exists($key, $optionalKeys)) {
                            /** @psalm-check-type-exact $optionalKeys = array{1?: \'a\', 2: \'b\'} */;
                        }
                    }',
            ],
            'arrayKeyExistsRemovesNullFromANullableArray' => [
                'code' => '<?php
                    /** @return array<string, list<string>>|null */
                    function config(): ?array {
                        return random_int(0, 1) === 1 ? ["SN" => ["a"]] : null;
                    }

                    function hasSn(string $sn): bool {
                        $cfg = config();
                        /** @psalm-suppress PossiblyNullArgument */
                        if (array_key_exists("SN", $cfg)) {
                            /** @psalm-check-type-exact $cfg = array{SN: list<string>, ...<string, list<string>>} */;
                            return in_array($sn, $cfg["SN"], true);
                        }

                        return false;
                    }',
            ],
            'arrayKeyExistsRemovesFalseAndNullFromTheArray' => [
                'code' => '<?php
                    /**
                     * @param array{SN?: int}|false|null $cfg
                     * @psalm-suppress PossiblyNullArgument
                     * @psalm-suppress PossiblyFalseArgument
                     */
                    function f($cfg): void {
                        if (key_exists("SN", $cfg)) {
                            /** @psalm-check-type-exact $cfg = array{SN: int} */;
                        }
                    }',
            ],
            'arrayKeyExistsOfAVariableKeyMakesTheArrayNonEmpty' => [
                'code' => '<?php
                    /**
                     * @param array<string, int>|null $cfg
                     * @psalm-suppress PossiblyNullArgument
                     */
                    function f(?array $cfg, string $key): void {
                        if (array_key_exists($key, $cfg)) {
                            /** @psalm-check-type-exact $cfg = non-empty-array<string, int> */;
                        }
                    }

                    /** @param array<string, int> $cfg */
                    function g(array $cfg, string $key): void {
                        if (array_key_exists($key, $cfg)) {
                            /** @psalm-check-type-exact $cfg = non-empty-array<string, int> */;
                        }
                    }',
            ],
            'arrayKeyExistsRemovesNullFromANestedArrayAndAPropertyArray' => [
                'code' => '<?php
                    /**
                     * @param array{a?: array<string, int>|null} $x
                     * @psalm-suppress PossiblyUndefinedArrayOffset
                     * @psalm-suppress PossiblyNullArgument
                     */
                    function f(array $x): void {
                        if (array_key_exists("SN", $x["a"])) {
                            /** @psalm-check-type-exact $x = array{a: array{SN: int, ...<string, int>}} */;
                        }
                    }

                    final class C {
                        /** @var array<string, int>|null */
                        public ?array $p = null;

                        /** @psalm-suppress PossiblyNullArgument */
                        public function m(): void {
                            if (array_key_exists("SN", $this->p)) {
                                /** @psalm-check-type-exact $this->p = array{SN: int, ...<string, int>} */;
                            }
                        }
                    }',
            ],
            'arrayKeyExistsKeepsNullWhereItIsFalseOrOred' => [
                'code' => '<?php
                    /**
                     * @param array<string, int>|null $cfg
                     * @psalm-suppress PossiblyNullArgument
                     */
                    function f(?array $cfg): void {
                        if (!array_key_exists("SN", $cfg)) {
                            /** @psalm-check-type-exact $cfg = array<string, int>|null */;
                        }

                        if (array_key_exists("SN", $cfg) || rand(0, 1)) {
                            /** @psalm-check-type-exact $cfg = array<string, int>|null */;
                        }
                    }',
            ],
            'arrayKeyExistsMakesAnOffsetOfUnknownTypeAnArray' => [
                'code' => '<?php
                    function f(array $content): void {
                        /** @psalm-suppress MixedArgument */
                        if (!array_key_exists("_defaults", $content["services"])) {
                            return;
                        }

                        /** @psalm-check-type-exact $content = array{services: array{_defaults: mixed, ...<array-key, mixed>}, ...<array-key, mixed>} */;
                        $defaults = $content["services"]["_defaults"];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'arrayKeyExistsMakesAnOffsetOfUnknownTypeArrayAccessibleBeforePhp8' => [
                'code' => '<?php
                    function f(array $content): void {
                        /** @psalm-suppress MixedArgument */
                        if (!array_key_exists("_defaults", $content["services"])) {
                            return;
                        }

                        /** @psalm-check-type-exact $content = array{services: ArrayAccess|array{_defaults: mixed, ...<array-key, mixed>}, ...<array-key, mixed>} */;
                        $defaults = $content["services"]["_defaults"];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '7.4',
            ],
            'arrayKeyExistsOfAnIntOrVariableKeyMakesAnOffsetOfUnknownTypeArrayAccessibleBeforePhp8' => [
                'code' => '<?php
                    /** @psalm-suppress MixedArgument */
                    function f(array $a, string $key): void {
                        if (!array_key_exists(3, $a["x"]) || !array_key_exists($key, $a["y"])) {
                            return;
                        }

                        /** @psalm-check-type-exact $a = array{x: ArrayAccess|array{3: mixed, ...<array-key, mixed>}, y: ArrayAccess|non-empty-array<array-key, mixed>, ...<array-key, mixed>} */;
                        echo (string) $a["x"][3];
                        echo (string) $a["y"][$key];
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'issetAfterAFailedArrayKeyExistsCheckStillMakesAMixedArrayAccessible' => [
                'code' => '<?php
                    /**
                     * @param mixed $config
                     * @psalm-suppress MixedArgument
                     */
                    function f($config): void {
                        if (array_key_exists("driver", $config) && $config["driver"] === null) {
                            return;
                        }

                        if (isset($config["driver"]) && $config["driver"] === "file") {
                            echo (string) $config["path"];
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'arrayKeyExistsKeepsTheTypeOfANestedArrayFilledInALoop' => [
                'code' => '<?php
                    /** @param list<list<string>> $rows */
                    function f(array $rows): void {
                        $unmerged = [];
                        foreach ($rows as $i => $row) {
                            foreach ($row as $j => $_) {
                                if (!array_key_exists($i, $unmerged) || !array_key_exists($j, $unmerged[$i])) {
                                    $unmerged[$i][$j] = ["a"];
                                }
                                $unmerged[$i][$j][] = "b";
                            }
                        }
                        /** @psalm-check-type-exact $unmerged = array<int<0, max>, array<int<0, max>, list{0: "a"|"b", 1?: "b", ...<"b">}>> */;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'arrayKeyExistsKeepsTheTypeOfANestedArrayFilledInALoopBeforePhp8' => [
                'code' => '<?php
                    /** @param list<list<string>> $rows */
                    function f(array $rows): void {
                        $unmerged = [];
                        foreach ($rows as $i => $row) {
                            foreach ($row as $j => $_) {
                                if (!array_key_exists($i, $unmerged) || !array_key_exists($j, $unmerged[$i])) {
                                    $unmerged[$i][$j] = ["a"];
                                }
                                $unmerged[$i][$j][] = "b";
                            }
                        }
                        /** @psalm-check-type-exact $unmerged = array<int<0, max>, array<int<0, max>, list{0: "a"|"b", 1?: "b", ...<"b">}>> */;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'arrayKeyExistsKeepsTheKnownTypeOfANestedArrayBeforePhp8' => [
                'code' => '<?php
                    /** @param array<int, array<int, list<string>>> $a */
                    function f(array $a, int $i, int $j): void {
                        if (!array_key_exists($i, $a) || !array_key_exists($j, $a[$i])) {
                            return;
                        }

                        /** @psalm-check-type-exact $a = non-empty-array<int, array<int, list<string>>> */;
                        $a[$i][$j][] = "x";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'arrayKeyExistsMakesAnArrayOrArrayAccessAnArrayFromPhp8' => [
                'code' => '<?php
                    /**
                     * @param array<string, int>|ArrayAccess<string, int> $a
                     */
                    function f($a): void {
                        if (array_key_exists("k", $a)) {
                            /** @psalm-check-type-exact $a = array{k: int, ...<string, int>} */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['PossiblyInvalidArgument'],
                'php_version' => '8.0',
            ],
            'arrayKeyExistsKeepsAnArrayAccessOfAKnownTypeBeforePhp8' => [
                'code' => '<?php
                    /**
                     * @param array<string, int>|ArrayAccess<string, int> $a
                     */
                    function f($a): void {
                        if (array_key_exists("k", $a)) {
                            /** @psalm-check-type-exact $a = ArrayAccess<string, int>|array{k: mixed, ...<string, int>} */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['PossiblyInvalidArgument'],
                'php_version' => '7.4',
            ],
            'arrayKeyExistsRemovesNullAfterANegatedCheck' => [
                'code' => '<?php
                    /**
                     * @param array<string, int>|null $a
                     * @psalm-suppress PossiblyNullArgument
                     */
                    function f(?array $a): int {
                        if (!array_key_exists("x", $a)) {
                            return 0;
                        }

                        /** @psalm-check-type-exact $a = array{x: int, ...<string, int>} */;
                        return $a["x"];
                    }

                    /**
                     * @param array<string, int>|null $a
                     * @psalm-suppress PossiblyNullArgument
                     */
                    function g(?array $a): int {
                        if (!array_key_exists("x", $a)) {
                            return 0;
                        } else {
                            /** @psalm-check-type-exact $a = array{x: int, ...<string, int>} */;
                            return $a["x"];
                        }
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'arrayKeyExistsOfAStringInAnArrayOfIntKeysDoesNotMakeAnyOffsetValid' => [
                'code' => '<?php
                    function lookup(string $key, string $other): string {
                        $items = [971 => "x", 972 => "y"];

                        if (array_key_exists($key, $items)) {
                            return $items[$other];
                        }

                        return "z";
                    }',
                'error_message' => 'InvalidArrayOffset',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'arrayKeyExistsOfATemplateOfStringInAnArrayOfIntKeysNarrowsItToTheKeysAsStrings' => [
                'code' => '<?php
                    function takesInt(int $i): void {}

                    /**
                     * @template T of string
                     * @param T $key
                     */
                    function ofTemplate(string $key): void {
                        $items = [971 => "x", 972 => "y"];

                        if (array_key_exists($key, $items)) {
                            takesInt($key);
                        }
                    }',
                'error_message' => 'expects int, but \'971\'|\'972\' provided',
            ],
            'negatedIssetKeepsAnOptionalKeyThatMayBeNull' => [
                'code' => '<?php
                    /**
                     * @param array{phone: string, descr?: ?string} $a
                     * @return array{phone: string}
                     */
                    function withoutDescr(array $a): array {
                        if (!isset($a["descr"])) {
                            return $a;
                        }
                        throw new \Exception();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
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
