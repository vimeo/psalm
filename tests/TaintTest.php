<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;

use function array_filter;
use function array_map;
use function array_values;
use function in_array;
use function preg_quote;
use function strpos;
use function trim;

use const DIRECTORY_SEPARATOR;

final class TaintTest extends TestCase
{
    // Somewhat legacy, do not add new issues here pls
    public const IGNORE = [
        'RiskyCast', 'PossiblyInvalidArgument', 'PossiblyInvalidCast',
        'ForbiddenCode', 'InvalidOperand', 'MixedAssignment',
        'InvalidScalarArgument', 'MissingParamType', 'UndefinedGlobalVariable', 'InvalidReturnType',
        'MixedArgument', 'PossiblyInvalidArgument', 'PossiblyInvalidCast', 'MixedReturnStatement',
        'MixedArgumentTypeCoercion', 'MixedArrayAccess', 'RedundantFunctionCall',
        'MissingPropertyType', 'UndefinedMagicPropertyAssignment', 'InvalidStringClass', 'PossiblyInvalidIterator',
        'InvalidReturnStatement', 'ArgumentTypeCoercion', 'UnresolvableInclude', 'UndefinedClass', 'RedundantCast',
        'MixedArrayAssignment', 'InvalidReturnStatement', 'InvalidArrayOffset', 'UndefinedFunction', 'ImplicitToStringCast',
        'InvalidArgument', 'UndefinedVariable',
    ];
    /**
     * @dataProvider providerValidCodeParse
     */
    public function testValidCode(string $code): void
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $code,
        );

        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        $this->project_analyzer->trackTaintedInputs();
        foreach (self::IGNORE as $issue_name) {
            Config::getInstance()->setCustomErrorLevel($issue_name, Config::REPORT_SUPPRESS);
        }

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $this->analyzeFile($file_path, new Context(), false);
    }

    /**
     * @dataProvider providerInvalidCodeParse
     */
    public function testInvalidCode(string $code, string $error_message, string $php_version = '8.0'): void
    {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/\b' . preg_quote($error_message, '/') . '\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->project_analyzer->setPhpVersion($php_version, 'tests');

        $this->addFile(
            $file_path,
            $code,
        );

        $this->project_analyzer->trackTaintedInputs();
        foreach (self::IGNORE as $issue_name) {
            Config::getInstance()->setCustomErrorLevel($issue_name, Config::REPORT_SUPPRESS);
        }

        $this->analyzeFile($file_path, new Context(), false);
    }

    /**
     * @return array<string, array{code:string}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'taintedInputInCreatedArrayNotEchoed' => [
                'code' => '<?php
                    $name = $_GET["name"] ?? "unknown";
                    $id = (int) $_GET["id"];

                    $data = ["name" => $name, "id" => $id];

                    echo "<h1>" . htmlentities($data["name"], \ENT_QUOTES) . "</h1>";
                    echo "<p>" . $data["id"] . "</p>";',
            ],
            'taintedInputInAssignedArrayNotEchoed' => [
                'code' => '<?php
                    $name = $_GET["name"] ?? "unknown";
                    $id = (int) $_GET["id"];

                    $data = [];
                    $data["name"] = $name;
                    $data["id"] = $id;

                    echo "<h1>" . htmlentities($data["name"], \ENT_QUOTES) . "</h1>";
                    echo "<p>" . $data["id"] . "</p>";',
            ],
            'taintedInputDirectlySuppressed' => [
                'code' => '<?php
                    class A {
                        public function deleteUser(PDO $pdo) : void {
                            /** @psalm-taint-escape sql */
                            $userId = (string) $_GET["user_id"];
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
            ],
            'taintedInputDirectlySuppressedWithOtherUse' => [
                'code' => '<?php
                    class A {
                        public function deleteUser(PDOWrapper $pdo) : void {
                            /**
                             * @psalm-taint-escape sql
                             */
                            $userId = (string) $_GET["user_id"];
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }

                        public function deleteUserSafer(PDOWrapper $pdo) : void {
                            $userId = $this->getSafeId();
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }

                        public function getSafeId() : string {
                            return "5";
                        }
                    }

                    class PDOWrapper {
                        /**
                         * @psalm-taint-sink sql $sql
                         */
                        public function exec(string $sql) : void {}
                    }',
            ],
            'taintedInputToParamButSafe' => [
                'code' => '<?php
                    class A {
                        public function getUserId(PDO $pdo) : void {
                            $this->deleteUser(
                                $pdo,
                                $this->getAppendedUserId((string) $_GET["user_id"])
                            );
                        }

                        public function getAppendedUserId(string $user_id) : string {
                            return "aaa" . $user_id;
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $userId2 = strlen($userId);
                            $pdo->exec("delete from users where user_id = " . $userId2);
                        }
                    }',
            ],
            'ValidatedInputFromParam' => [
                'code' => '<?php
                    /**
                     * @psalm-assert-untainted $userId
                     */
                    function validateUserId(string $userId) : void {
                        if (!is_numeric($userId)) {
                            throw new \Exception("bad");
                        }
                    }

                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function doDelete(PDO $pdo) : void {
                            $userId = $this->getUserId();
                            validateUserId($userId);
                            $this->deleteUser($pdo, $userId);
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
            ],
            'untaintedInputAfterIntCast' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : int {
                            return (int) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->getUserId();
                        }

                        public function deleteUser(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
            ],
            'specializedCoreFunctionCall' => [
                'code' => '<?php
                    $a = (string) ($data["user_id"] ?? "");

                    echo print_r([], true);

                    $b = print_r($a, true);',
            ],
            'untaintedInputViaStaticFunctionWithSafePath' => [
                'code' => '<?php
                    class Utils {
                        /**
                         * @psalm-pure
                         */
                        public static function shorten(string $str) : string {
                            return $str;
                        }
                    }

                    class A {
                        public function foo() : void {
                            echo(htmlentities(Utils::shorten((string) $_GET["user_id"]), \ENT_QUOTES));
                        }

                        public function bar() : void {
                            echo(Utils::shorten("hello"));
                        }
                    }',
            ],
            'taintHtmlEntities' => [
                'code' => '<?php
                    function foo() : void {
                        $a = htmlentities((string) $_GET["bad"], \ENT_QUOTES);
                        echo $a;
                    }',
            ],
            'taintFilterVar' => [
                'code' => '<?php
                    $args = [
                        filter_var($_GET["bad"], FILTER_VALIDATE_INT),
                        filter_var($_GET["bad"], FILTER_VALIDATE_BOOLEAN),
                        filter_var($_GET["bad"], FILTER_VALIDATE_FLOAT),
                        filter_var($_GET["bad"], FILTER_SANITIZE_NUMBER_INT),
                        filter_var($_GET["bad"], FILTER_SANITIZE_NUMBER_FLOAT),
                    ];

                    foreach($args as $arg){
                        new $arg;
                        unserialize($arg);
                        require_once $arg;
                        eval($arg);
                        ldap_connect($arg);
                        ldap_search("", "", $arg);
                        mysqli_query($conn, $arg);
                        echo $arg;
                        system($arg);
                        curl_init($arg);
                        file_get_contents($arg);
                        setcookie($arg);
                        header($arg);
                    }',
            ],
            'taintLdapEscape' => [
                'code' => '<?php
                    $ds = ldap_connect(\'example.com\');
                    $dn = \'o=Psalm, c=US\';
                    $filter = ldap_escape($_GET[\'filter\']);
                    ldap_search($ds, $dn, $filter, []);',
            ],
            'taintOnStrReplaceCallRemovedInFunction' => [
                'code' => '<?php
                    class U {
                        /**
                         * @psalm-pure
                         * @psalm-taint-escape html
                         * @psalm-taint-escape has_quotes
                         */
                        public static function shorten(string $s) : string {
                            return str_replace("foo", "bar", $s);
                        }
                    }

                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            echo U::shorten($o->s);
                        }
                    }',
            ],
            'taintOnPregReplaceCallRemovedInFunction' => [
                'code' => '<?php
                    class U {
                        /**
                         * @psalm-pure
                         */
                        public static function shorten(string $s) : string {
                            return preg_replace("/[^_a-z\/\.A-Z0-9]/", "bar", $s);
                        }
                    }

                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            echo U::shorten($o->s);
                        }
                    }',
            ],
            'taintOnStrReplaceCallRemovedInline' => [
                'code' => '<?php
                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            /**
                             * @psalm-taint-escape html
                             * @psalm-taint-escape has_quotes
                             */
                            $a = str_replace("foo", "bar", $o->s);
                            echo $a;
                        }
                    }',
            ],
            'NoTaintsOnSimilarPureCall' => [
                'code' => '<?php
                    class U {
                        /** @psalm-pure */
                        public static function shorten(string $s) : string {
                            return substr($s, 0, 15);
                        }

                        /** @psalm-pure */
                        public static function escape(string $s) : string {
                            return htmlentities($s, \ENT_QUOTES);
                        }
                    }

                    class O1 {
                        public string $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }
                    }

                    class O2 {
                        public string $t;

                        public function __construct() {
                            $this->t = (string) $_GET["FOO"];
                        }
                    }

                    class V1 {
                        public function foo() : void {
                            $o = new O1((string) $_GET["FOO"]);
                            echo U::escape(U::shorten($o->s));
                        }
                    }

                    class V2 {
                        public function foo(O2 $o) : void {
                            echo U::shorten(U::escape($o->t));
                        }
                    }',
            ],
            'taintPropertyPassingObjectWithDifferentValue' => [
                'code' => '<?phps
                    /** @psalm-immutable */
                    class User {
                        public string $id;
                        public $name = "Luke";

                        public function __construct(string $userId) {
                            $this->id = $userId;
                        }
                    }

                    class UserUpdater {
                        public static function doDelete(PDO $pdo, User $user) : void {
                            self::deleteUser($pdo, $user->name);
                        }

                        public static function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }

                    $userObj = new User((string) $_GET["user_id"]);
                    UserUpdater::doDelete(new PDO("t"), $userObj);',
            ],
            'taintPropertyWithoutPassingObject' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class User {
                        public string $id;

                        public function __construct(string $userId) {
                            $this->id = $userId;
                        }

                        public function setId(string $userId) : void {
                            $this->id = $userId;
                        }
                    }

                    function echoId(User $u2) : void {
                        echo $u2->id;
                    }

                    $u = new User("5");
                    echoId($u);
                    $u->setId($_GET["user_id"]);',
            ],
            'specializeStaticMethod' => [
                'code' => '<?php
                    StringUtility::foo($_GET["c"]);

                    class StringUtility {
                        /**
                         * @psalm-taint-specialize
                         */
                        public static function foo(string $str) : string
                        {
                            return $str;
                        }

                        /**
                         * @psalm-taint-specialize
                         */
                        public static function slugify(string $url) : string {
                            return self::foo($url);
                        }
                    }

                    echo StringUtility::slugify("hello");',
            ],
            'taintFreeNestedArray' => [
                'code' => '<?php
                    $a = [];
                    $a[] = ["a" => $_GET["name"], "b" => "foo"];

                    foreach ($a as $m) {
                        echo $m["b"];
                    }',
            ],
            'taintFreeNestedArrayWithOffsetAccessedExplicitly' => [
                'code' => '<?php
                    $a = [];
                    $a[] = ["a" => $_GET["name"], "b" => "foo"];

                    echo $a[0]["b"];',
            ],
            'dontTaintSpecializedInstanceProperty' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class StringHolder {
                        public $x;

                        public function __construct(string $x) {
                            $this->x = $x;
                        }
                    }

                    $a = new StringHolder("a");
                    $b = new StringHolder($_GET["x"]);

                    echo $a->x;',
            ],
            'dontTaintSpecializedCallsForAnonymousInstance' => [
                'code' => '<?php

                    class StringRenderer {
                        /** @psalm-taint-specialize */
                        public function render(string $x) {
                            return $x;
                        }
                    }

                    $notEchoed = (new StringRenderer())->render($_GET["untrusted"]);
                    echo (new StringRenderer())->render("a");',
            ],
            'dontTaintSpecializedCallsForStubMadeInstance' => [
                'code' => '<?php

                    class StringRenderer {
                        /** @psalm-taint-specialize */
                        public function render(string $x) {
                            return $x;
                        }
                    }

                    /** @psalm-suppress InvalidReturnType */
                    function stub(): StringRenderer { }

                    $notEchoed = stub()->render($_GET["untrusted"]);
                    echo stub()->render("a");',
            ],
            'suppressTaintedInput' => [
                'code' => '<?php
                    function unsafe() {
                        /**
                         * @psalm-suppress TaintedInput
                         */
                        echo $_GET["x"];
                    }',
            ],
            'suppressTaintedAssignment' => [
                'code' => '<?php
                    $b = $_GET["x"];

                    /**
                     * @psalm-suppress TaintedInput
                     */
                    $a = $b;


                    echo $a;',
            ],
            'dontPropagateTaintToChildConstructor' => [
                'code' => '<?php
                    class A {
                        public function __construct(string $a) {}
                    }

                    class B extends A {
                        public function __construct(string $a) {
                            echo $a;
                        }
                    }

                    new A($_GET["foo"]);',
            ],
            'dontTaintThroughChildConstructorWhenMethodOverridden' => [
                'code' => '<?php //--taint-analysis
                    class A {
                        private $taint;

                        public function __construct($taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {
                        public function __construct($taint) {}
                    }

                    $b = new B($_GET["bar"]);
                    echo $b->getTaint();',
            ],
            'immutableClassTrackInputThroughMethod' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        private string $taint = "";

                        public function __construct(string $taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    $b = new A($_GET["bar"]);
                    $a = new A("bar");
                    echo $a->getTaint();',
            ],
            'literalStringCannotCarryTaint' => [
                'code' => '<?php
                    $file = $_GET["foo"];

                    if ($file !== "") {
                        /**
                         * @psalm-taint-escape input
                         */
                        $file = basename($file);
                    }

                    echo $file;',
            ],
            'strTrNotTainted' => [
                'code' => '<?php
                $input = strtr(\'data\', \'data\', \'data\');
                setcookie($input, \'value\');',
            ],
            'conditionallyEscapedTaintPassedTrue' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-escape ($escape is true ? "html" : null)
                     */
                    function foo(string $string, bool $escape = true): string {
                        if ($escape) {
                            $string = htmlspecialchars($string);
                        }

                        return $string;
                    }
                    /** @psalm-suppress PossiblyInvalidArgument */
                    echo foo($_GET["foo"], true);
                    /** @psalm-suppress PossiblyInvalidArgument */
                    echo foo($_GET["foo"]);',
            ],
            'NoTaintForInt' => [
                'code' => '<?php // --taint-analysis

                    function foo(int $value): void {
                        echo $value;
                    }

                    /** @psalm-suppress InvalidScalarArgument */
                    foo($_GET["foo"]);

                    function bar(): int {
                        return $_GET["foo"];
                    }

                    echo bar();',
            ],
            'conditionallyEscapedTaintPassedTrueStaticCall' => [
                'code' => '<?php
                    class U {
                        /**
                         * @psalm-taint-escape ($escape is true ? "html" : null)
                         */
                        public static function foo(string $string, bool $escape = true): string {
                            if ($escape) {
                                $string = htmlspecialchars($string);
                            }

                            return $string;
                        }
                    }

                    echo U::foo($_GET["foo"], true);
                    echo U::foo($_GET["foo"]);',
            ],
            'keysAreNotTainted' => [
                'code' => '<?php
                    function takesArray(array $arr): void {
                        foreach ($arr as $key => $_) {
                            echo $key;
                        }
                    }

                    takesArray(["good" => $_GET["bad"]]);',
            ],
            'resultOfComparisonIsNotTainted' => [
                'code' => '<?php
                    $input = $_GET["foo"];
                    $var = $input === "x";
                    var_dump($var);',
            ],
            'resultOfPlusIsNotTainted' => [
                'code' => '<?php
                    $input = is_numeric( $_GET["foo"] ) ? $_GET["foo"] : "";
                    $var = $input + 1;
                    var_dump($var);',
            ],
            'NoTaintForIntTypeHintUsingAnnotatedSink' => [
                'code' => '<?php // --taint-analysis
                    function fetch(int $id): string
                    {
                        return query("SELECT * FROM table WHERE id=" . $id);
                    }
                    /**
                     * @return string
                     * @psalm-taint-sink sql $sql
                     * @psalm-taint-specialize
                     */
                    function query(string $sql) { return ""; }
                    $value = $_GET["value"];
                    $result = fetch($value);',
            ],
            'NoTaintForIntTypeCastUsingAnnotatedSink' => [
                'code' => '<?php // --taint-analysis
                    /** @psalm-suppress MissingParamType */
                    function fetch($id): string
                    {
                        return query("SELECT * FROM table WHERE id=" . (int)$id);
                    }
                    /**
                     * @return string
                     * @psalm-taint-sink sql $sql
                     * @psalm-taint-specialize
                     */
                    function query(string $sql) {}
                    $value = $_GET["value"];
                    $result = fetch($value);',
            ],
            'dontTaintArrayWithDifferentOffsetUpdated' => [
                'code' => '<?php
                    function foo(): void {
                        $foo = [
                            "a" => [["c" => "hello"]],
                            "b" => [],
                        ];

                        $foo["b"][] = [
                            "c" => $_GET["bad"],
                        ];

                        bar($foo["a"]);
                    }

                    function bar(array $arr): void {
                        foreach ($arr as $s) {
                            echo $s["c"];
                        }
                    }',
            ],
            'urlencode' => [
                'code' => '<?php
                    echo urlencode($_GET["bad"]);
                ',
            ],
            'mysqliEscapeFunctions' => [
                'code' => '<?php
                    $mysqli = new mysqli();

                    $a = $mysqli->escape_string($_GET["a"]);
                    $b = mysqli_escape_string($mysqli, $_GET["b"]);
                    $c = $mysqli->real_escape_string($_GET["c"]);
                    $d = mysqli_real_escape_string($mysqli, $_GET["d"]);

                    $mysqli->query("$a$b$c$d");',
            ],
            'querySimpleXMLElement' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-escape xpath
                     */
                    function my_escaping_function_for_xpath(string $input) : string {};

                    function queryExpression(SimpleXMLElement $xml) : array|false|null {
                        $expression = $_GET["expression"];
                        $expression = my_escaping_function_for_xpath($expression);
                        return $xml->xpath($expression);
                    }',
            ],
            'escapeSeconds' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-escape sleep
                     */
                    function my_escaping_function_for_seconds(mixed $input) : int {};

                    $seconds = my_escaping_function_for_seconds($_GET["seconds"]);
                    sleep($seconds);',
            ],
        ];
    }

    /**
     * @return array<string, array{code: string, error_message: string, php_version?: string}>
     */
    public function providerInvalidCodeParse(): array
    {
        return [
            'taintedInputFromMethodReturnTypeSimple' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->getUserId();
                        }

                        public function deleteUser(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputFromFunctionReturnType' => [
                'code' => '<?php
                    function getName() : string {
                        return $_GET["name"] ?? "unknown";
                    }

                    echo getName();',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:26 - Detected tainted HTML in path: $_GET -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32) -> coalesce (src' . DIRECTORY_SEPARATOR . 'somefile.php:3:32) -> getName (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:42) -> call to echo (src' . DIRECTORY_SEPARATOR . 'somefile.php:6:26) -> echo#1',
            ],
            'taintedInputFromExplicitTaintSource' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-source input
                     */
                    function getName() : string {
                        return "";
                    }

                    echo getName();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromExplicitTaintSourceStaticMethod' => [
                'code' => '<?php
                    class Request {
                        /**
                         * @psalm-taint-source input
                         */
                        public static function getName() : string {
                            return "";
                        }
                    }


                    echo Request::getName();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromGetArray' => [
                'code' => '<?php
                    function getName(array $data) : string {
                        return $data["name"] ?? "unknown";
                    }

                    $name = getName($_GET);

                    echo $name;',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromReturnToInclude' => [
                'code' => '<?php
                    $a = (string) $_GET["file"];
                    $b = "hello" . $a;
                    include str_replace("a", "b", $b);',
                'error_message' => 'TaintedInclude',
            ],
            'taintedInputFromReturnToEval' => [
                'code' => '<?php
                    $a = $_GET["file"];
                    eval("<?php" . $a);',
                'error_message' => 'TaintedEval',
            ],
            'taintedInputFromReturnTypeToEcho' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->getUserId();
                        }

                        public function deleteUser(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            echo $userId;
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputInCreatedArrayIsEchoed' => [
                'code' => '<?php
                    $name = $_GET["name"] ?? "unknown";

                    $data = ["name" => $name];

                    echo "<h1>" . $data["name"] . "</h1>";',
                'error_message' => 'TaintedHtml',
            ],
            'testTaintedInputInAssignedArrayIsEchoed' => [
                'code' => '<?php
                    $name = $_GET["name"] ?? "unknown";

                    $data = [];
                    $data["name"] = $name;

                    echo "<h1>" . $data["name"] . "</h1>";',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputDirectly' => [
                'code' => '<?php
                    class A {
                        public function deleteUser(PDO $pdo) : void {
                            $userId = (string) $_GET["user_id"];
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputFromReturnTypeWithBranch' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            $userId = $this->getUserId();

                            if (rand(0, 1)) {
                                $userId .= "aaa";
                            } else {
                                $userId .= "bb";
                            }

                            return $userId;
                        }

                        public function deleteUser(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'sinkAnnotation' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->getUserId();
                        }

                        public function deleteUser(PDOWrapper $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }

                    class PDOWrapper {
                        /**
                         * @psalm-taint-sink sql $sql
                         */
                        public function exec(string $sql) : void {}
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputFromParam' => [
                'code' => '<?php
                    class A {
                        public function getUserId() : string {
                            return (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->getUserId();
                        }

                        public function doDelete(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $this->deleteUser($pdo, $userId);
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql - src' . DIRECTORY_SEPARATOR . 'somefile.php:17:40 - Detected tainted SQL in path: $_GET -> $_GET[\'user_id\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:4:45) -> A::getUserId (src' . DIRECTORY_SEPARATOR . 'somefile.php:3:55) -> concat (src' . DIRECTORY_SEPARATOR . 'somefile.php:8:36) -> A::getAppendedUserId (src' . DIRECTORY_SEPARATOR . 'somefile.php:7:63) -> $userId (src' . DIRECTORY_SEPARATOR . 'somefile.php:12:29) -> call to A::deleteUser (src' . DIRECTORY_SEPARATOR . 'somefile.php:13:53) -> $userId (src' . DIRECTORY_SEPARATOR . 'somefile.php:16:69) -> call to PDO::exec (src' . DIRECTORY_SEPARATOR . 'somefile.php:17:40) -> PDO::exec#1',
            ],
            'taintedInputToParam' => [
                'code' => '<?php
                    class A {
                        public function getUserId(PDO $pdo) : void {
                            $this->deleteUser(
                                $pdo,
                                $this->getAppendedUserId((string) $_GET["user_id"])
                            );
                        }

                        public function getAppendedUserId(string $user_id) : string {
                            return "aaa" . $user_id;
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputToParamAfterAssignment' => [
                'code' => '<?php
                    class A {
                        public function getUserId(PDO $pdo) : void {
                            $this->deleteUser(
                                $pdo,
                                $this->getAppendedUserId((string) $_GET["user_id"])
                            );
                        }

                        public function getAppendedUserId(string $user_id) : string {
                            return "aaa" . $user_id;
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $userId2 = $userId;
                            $pdo->exec("delete from users where user_id = " . $userId2);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputToParamAlternatePath' => [
                'code' => '<?php
                    class A {
                        public function getUserId(PDO $pdo) : void {
                            $this->deleteUser(
                                $pdo,
                                self::doFoo(),
                                $this->getAppendedUserId((string) $_GET["user_id"])
                            );
                        }

                        public function getAppendedUserId(string $user_id) : string {
                            return "aaa" . $user_id;
                        }

                        public static function doFoo() : string {
                            return "hello";
                        }

                        public function deleteUser(PDO $pdo, string $userId, string $userId2) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);

                            if (rand(0, 1)) {
                                $pdo->exec("delete from users where user_id = " . $userId2);
                            }
                        }
                    }',
                'error_message' => 'TaintedSql - src' . DIRECTORY_SEPARATOR . 'somefile.php:23:44 - Detected tainted SQL in path: $_GET -> $_GET[\'user_id\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:7:67) -> call to A::getAppendedUserId (src' . DIRECTORY_SEPARATOR . 'somefile.php:7:58) -> $user_id (src' . DIRECTORY_SEPARATOR . 'somefile.php:11:66) -> concat (src' . DIRECTORY_SEPARATOR . 'somefile.php:12:36) -> A::getAppendedUserId (src' . DIRECTORY_SEPARATOR . 'somefile.php:11:78) -> call to A::deleteUser (src' . DIRECTORY_SEPARATOR . 'somefile.php:7:33) -> $userId2 (src' . DIRECTORY_SEPARATOR . 'somefile.php:19:85) -> call to PDO::exec (src' . DIRECTORY_SEPARATOR . 'somefile.php:23:44) -> PDO::exec#1',
            ],
            'taintedInParentLoader' => [
                'code' => '<?php
                    abstract class A {
                        abstract public static function loadPartial(string $sink) : void;

                        public static function loadFull(string $sink) : void {
                            static::loadPartial($sink);
                        }
                    }

                    function getPdo() : PDO {
                        return new PDO("connectionstring");
                    }

                    class AChild extends A {
                        public static function loadPartial(string $sink) : void {
                            getPdo()->exec("select * from foo where bar = " . $sink);
                        }
                    }

                    class AGrandChild extends AChild {}

                    class C {
                        public function foo(string $user_id) : void {
                            AGrandChild::loadFull($user_id);
                        }
                    }

                    (new C)->foo((string) $_GET["user_id"]);',
                'error_message' => 'TaintedSql - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:44 - Detected tainted SQL in path: $_GET -> $_GET[\'user_id\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:28:43) -> call to C::foo (src' . DIRECTORY_SEPARATOR . 'somefile.php:28:34) -> $user_id (src' . DIRECTORY_SEPARATOR . 'somefile.php:23:52) -> call to AGrandChild::loadFull (src' . DIRECTORY_SEPARATOR . 'somefile.php:24:51) -> AGrandChild::loadFull#1 (src' . DIRECTORY_SEPARATOR . 'somefile.php:5:64) -> A::loadFull#1 (src' . DIRECTORY_SEPARATOR . 'somefile.php:24:51) -> $sink (src' . DIRECTORY_SEPARATOR . 'somefile.php:5:64) -> call to A::loadPartial (src' . DIRECTORY_SEPARATOR . 'somefile.php:6:49) -> A::loadPartial#1 (src' . DIRECTORY_SEPARATOR . 'somefile.php:3:76) -> AChild::loadPartial#1 (src' . DIRECTORY_SEPARATOR . 'somefile.php:6:49) -> $sink (src' . DIRECTORY_SEPARATOR . 'somefile.php:15:67) -> call to PDO::exec (src' . DIRECTORY_SEPARATOR . 'somefile.php:16:44) -> PDO::exec#1',
            ],
            'taintedInputFromProperty' => [
                'code' => '<?php
                    class A {
                        public string $userId;

                        public function __construct() {
                            $this->userId = (string) $_GET["user_id"];
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->userId;
                        }

                        public function doDelete(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $this->deleteUser($pdo, $userId);
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputFromPropertyViaMixin' => [
                'code' => '<?php
                    class A {
                        public string $userId;

                        public function __construct() {
                            $this->userId = (string) $_GET["user_id"];
                        }
                    }

                    /** @mixin A */
                    class B {
                        private A $a;

                        public function __construct(A $a) {
                            $this->a = $a;
                        }

                        public function __get(string $name) {
                            return $this->a->$name;
                        }
                    }

                    class C {
                        private B $b;

                        public function __construct(B $b) {
                            $this->b = $b;
                        }

                        public function getAppendedUserId() : string {
                            return "aaaa" . $this->b->userId;
                        }

                        public function doDelete(PDO $pdo) : void {
                            $userId = $this->getAppendedUserId();
                            $this->deleteUser($pdo, $userId);
                        }

                        public function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedSql',
            ],
            'taintedInputViaStaticFunction' => [
                'code' => '<?php
                    class Utils {
                        public static function shorten(string $str) : string {
                            return $str;
                        }
                    }

                    class A {
                        public function foo() : void {
                            echo(Utils::shorten((string) $_GET["user_id"]));
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputViaPureStaticFunction' => [
                'code' => '<?php
                    class Utils {
                        /**
                         * @psalm-pure
                         */
                        public static function shorten(string $str) : string {
                            return substr($str, 0, 100);
                        }
                    }

                    class A {
                        public function foo() : void {
                            echo(Utils::shorten((string) $_GET["user_id"]));
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'untaintedInputViaStaticFunctionWithoutSafePath' => [
                'code' => '<?php
                    class Utils {
                        /**
                         * @psalm-pure
                         */
                        public static function shorten(string $str) : string {
                            return $str;
                        }
                    }

                    class A {
                        public function foo() : void {
                            echo(Utils::shorten((string) $_GET["user_id"]));
                        }

                        public function bar() : void {
                            echo(Utils::shorten("hello"));
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInputFromMagicProperty' => [
                'code' => '<?php
                    /**
                     * @property string $userId
                     */
                    class A {
                        /** @var array<string, string> */
                        private $vars = [];

                        public function __get(string $s) : string {
                            return $this->vars[$s];
                        }

                        public function __set(string $s, string $t) {
                            $this->vars[$s] = $t;
                        }
                    }

                    function getAppendedUserId() : void {
                        $a = new A();
                        $a->userId = (string) $_GET["user_id"];
                        echo $a->userId;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintOverMixed' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedArgument
                     */
                    function foo() : void {
                        $a = $_GET["bad"];
                        echo $a;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintStrConversion' => [
                'code' => '<?php
                    function foo() : void {
                        /** @psalm-suppress PossiblyInvalidCast */
                        $a = strtoupper(strtolower((string) $_GET["bad"]));
                        echo $a;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintIntoExec' => [
                'code' => '<?php
                    function foo() : void {
                        $a = (string) $_GET["bad"];
                        exec($a);
                    }',
                'error_message' => 'TaintedShell',
            ],
            'taintIntoExecMultipleConcat' => [
                'code' => '<?php
                    function foo() : void {
                        $a = "9" . "a" . "b" . "c" . ((string) $_GET["bad"]) . "d" . "e" . "f";
                        exec($a);
                    }',
                'error_message' => 'TaintedShell',
            ],
            'taintIntoNestedArrayUnnestedSeparately' => [
                'code' => '<?php
                    function foo() : void {
                        $a = [[(string) $_GET["bad"]]];
                        exec($a[0][0]);
                    }',
                'error_message' => 'TaintedShell',
            ],
            'taintIntoArrayAndThenOutAgain' => [
                'code' => '<?php
                    class C {
                        public static function foo() : array {
                            $a = (string) $_GET["bad"];
                            return [$a];
                        }

                        public static function bar() {
                            exec(self::foo()[0]);
                        }
                    }',
                'error_message' => 'TaintedShell',
            ],
            'taintAppendedToArray' => [
                'code' => '<?php
                    class C {
                        public static function foo() : array {
                            $a = [];
                            $a[] = (string) $_GET["bad"];
                            return $a;
                        }

                        public static function bar() {
                            exec(self::foo()[0]);
                        }
                    }',
                'error_message' => 'TaintedShell',
            ],
            'taintOnSubstrCall' => [
                'code' => '<?php
                    class U {
                        /** @psalm-pure */
                        public static function shorten(string $s) : string {
                            return substr($s, 0, 15);
                        }
                    }

                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            echo U::shorten($o->s);
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintOnStrReplaceCallSimple' => [
                'code' => '<?php
                    class U {
                        /** @psalm-pure */
                        public static function shorten(string $s) : string {
                            return str_replace("foo", "bar", $s);
                        }
                    }

                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            echo U::shorten($o->s);
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintOnPregReplaceCall' => [
                'code' => '<?php
                    class U {
                        /** @psalm-pure */
                        public static function shorten(string $s) : string {
                            return preg_replace("/foo/", "bar", $s);
                        }
                    }

                    class V {}

                    class O1 {
                        public string $s;

                        public function __construct() {
                            $this->s = (string) $_GET["FOO"];
                        }
                    }

                    class V1 extends V {
                        public function foo(O1 $o) : void {
                            echo U::shorten($o->s);
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'IndirectGetAssignment' => [
                'code' => '<?php
                    class InputFilter {
                        public string $name;

                        public function __construct(string $name) {
                            $this->name = $name;
                        }

                        /**
                         * @psalm-taint-specialize
                         */
                        public function getArg(string $method, string $type)
                        {
                            $arg = null;

                            switch ($method) {
                                case "post":
                                    if (isset($_POST[$this->name])) {
                                        $arg = $_POST[$this->name];
                                    }
                                    break;

                                case "get":
                                    if (isset($_GET[$this->name])) {
                                        $arg = $_GET[$this->name];
                                    }
                                    break;
                            }

                            return $this->filterInput($type, $arg);
                        }

                        protected function filterInput(string $type, $arg)
                        {
                            // input is null
                            if ($arg === null) {
                                return null;
                            }

                            // set to null if sanitize clears arg
                            if ($arg === "") {
                                $arg = null;
                            }

                            // type casting
                            if ($arg !== null) {
                                $arg = $this->typeCastInput($type, $arg);
                            }

                            return $arg;
                        }

                        protected function typeCastInput(string $type, $arg) {
                            if ($type === "string") {
                                return (string) $arg;
                            }

                            return null;
                        }
                    }

                    echo (new InputFilter("hello"))->getArg("get", "string");',
                'error_message' => 'TaintedHtml',
            ],
            'taintPropertyPassingObject' => [
                'code' => '<?php
                    /** @psalm-immutable */
                    class User {
                        public string $id;

                        public function __construct(string $userId) {
                            $this->id = $userId;
                        }
                    }

                    class UserUpdater {
                        public static function doDelete(PDO $pdo, User $user) : void {
                            self::deleteUser($pdo, $user->id);
                        }

                        public static function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }

                    $userObj = new User((string) $_GET["user_id"]);
                    UserUpdater::doDelete(new PDO("test"), $userObj);',
                'error_message' => 'TaintedSql',
            ],
            'taintPropertyPassingObjectSettingValueLater' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class User {
                        public string $id;

                        public function __construct(string $userId) {
                            $this->id = $userId;
                        }

                        public function setId(string $userId) : void {
                            $this->id = $userId;
                        }
                    }

                    function echoId(User $u2) : void {
                        echo $u2->id;
                    }

                    $u = new User("5");
                    $u->setId($_GET["user_id"]);
                    echoId($u);',
                'error_message' => 'TaintedHtml',
            ],
            'ImplodeExplode' => [
                'code' => '<?php
                    $a = $_GET["name"];
                    $b = explode(" ", $a);
                    $c = implode(" ", $b);
                    echo $c;',
                'error_message' => 'TaintedHtml',
            ],
            'ImplodeIndirect' => [
                'code' => '<?php
                    /** @var array $unsafe */
                    $unsafe = $_GET[\'unsafe\'];
                    echo implode(" ", $unsafe);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughPregReplaceCallback' => [
                'code' => '<?php
                    $a = $_GET["bad"];

                    $b = preg_replace_callback(
                        \'/foo/\',
                        function (array $matches) : string {
                            return $matches[1];
                        },
                        $a
                    );

                    echo $b;',
                'error_message' => 'TaintedHtml',
            ],
            'taintedFunctionWithNoTypes' => [
                'code' => '<?php
                    function rawinput() {
                        return $_GET[\'rawinput\'];
                    }

                    echo rawinput();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedStaticCallWithNoTypes' => [
                'code' => '<?php
                    class A {
                        public static function rawinput() {
                            return $_GET[\'rawinput\'];
                        }
                    }

                    echo A::rawinput();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedInstanceCallWithNoTypes' => [
                'code' => '<?php
                    class A {
                        public function rawinput() {
                            return $_GET[\'rawinput\'];
                        }
                    }

                    echo (new A())->rawinput();',
                'error_message' => 'TaintedHtml',
            ],
            'taintStringObtainedUsingStrval' => [
                'code' => '<?php
                    $unsafe = strval($_GET[\'unsafe\']);
                    echo $unsafe;',
                'error_message' => 'TaintedHtml',
            ],
            'taintStringObtainedUsingSprintf' => [
                'code' => '<?php
                    $unsafe = sprintf("%s", strval($_GET[\'unsafe\']));
                    echo $unsafe;',
                'error_message' => 'TaintedHtml',
            ],
            'encapsulatedString' => [
                'code' => '<?php
                    $unsafe = $_GET[\'unsafe\'];
                    echo "$unsafe";',
                'error_message' => 'TaintedHtml',
            ],
            'encapsulatedToStringMagic' => [
                'code' => '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }
                    $unsafe = new MyClass();
                    echo "unsafe: $unsafe";',
                'error_message' => 'TaintedHtml',
            ],
            'castToStringMagic' => [
                'code' => '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }
                    $unsafe = new MyClass();
                    echo $unsafe;',
                'error_message' => 'TaintedHtml',
            ],
            'castToStringViaArgument' => [
                'code' => '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }

                    function doesEcho(string $s): void {
                        echo $s;
                    }

                    $unsafe = new MyClass();

                    doesEcho($unsafe);',
                'error_message' => 'TaintedHtml',
            ],
            'toStringTaintInSubclass' => [
                'code' => '<?php // --taint-analysis
                    class TaintedBaseClass {
                        /** @psalm-taint-source input */
                        public function __toString() {
                            return "x";
                        }
                    }
                    class TaintedSubclass extends TaintedBaseClass {}
                    $x = new TaintedSubclass();
                    echo "Caught: $x\n";',
                'error_message' => 'TaintedHtml',
            ],
            'implicitToStringMagic' => [
                'code' => '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }
                    $unsafe = new MyClass();
                    echo $unsafe;',
                'error_message' => 'TaintedHtml',
            ],
            'namespacedFunction' => [
                'code' => '<?php
                    namespace ns;

                    function identity(string $s) : string {
                        return $s;
                    }

                    echo identity($_GET[\'userinput\']);',
                'error_message' => 'TaintedHtml',
            ],
            'print' => [
                'code' => '<?php
                    print($_GET["name"]);',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:27 - Detected tainted HTML in path: $_GET -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:27) -> call to print (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:27) -> print#1',
            ],
            'printf' => [
                'code' => '<?php
                    printf($_GET["name"]);',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:28 - Detected tainted HTML in path: $_GET -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:28) -> call to printf (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:28) -> printf#1',
            ],
            'print_r' => [
                'code' => '<?php
                    print_r($_GET["name"]);',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:29 - Detected tainted HTML in path: $_GET -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:29) -> call to print_r (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:29) -> print_r#1',
            ],
            'var_dump' => [
                'code' => '<?php
                    var_dump($_GET["name"]);',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:30 - Detected tainted HTML in path: $_GET -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:30) -> call to var_dump (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:30) -> var_dump#1',
            ],
            'var_export' => [
                'code' => '<?php
                    var_export($_GET["name"]);',
                'error_message' => 'TaintedHtml - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:32 - Detected tainted HTML in path: $_GET -> $_GET[\'name\'] (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:32) -> call to var_export (src' . DIRECTORY_SEPARATOR . 'somefile.php:2:32) -> var_export#1',
            ],
            'unpackArgs' => [
                'code' => '<?php
                    function test(...$args) {
                        echo $args[0];
                    }

                    /**
                     * @psalm-taint-source input
                     */
                    function getQueryParam() {}

                    // cannot use $_GET, see #8477
                    $foo = getQueryParam();
                    test(...$foo);',
                'error_message' => 'TaintedHtml',
            ],
            'foreachArg' => [
                'code' => '<?php
                    $a = $_GET["bad"];

                    foreach ($a as $arg) {
                        echo $arg;
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'magicPropertyType' => [
                'code' => '<?php
                    class Magic {
                        private $params = [];

                        public function __get(string $a) {
                            return $this->params[$a];
                        }

                        public function __set(string $a, $value) {
                            $this->params[$a] = $value;
                        }
                    }

                    $m = new Magic();
                    $m->taint = $_GET["input"];
                    echo $m->taint;',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedArrayWithOffsetAccessedInForeach' => [
                'code' => '<?php
                    $a = [];
                    $a[0] = ["a" => $_GET["name"], "b" => "foo"];

                    foreach ($a as $m) {
                        echo $m["a"];
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintNestedArrayWithOffsetAccessedExplicitly' => [
                'code' => '<?php
                    $a = [];
                    $a[] = ["a" => $_GET["name"], "b" => "foo"];

                    echo $a[0]["a"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapExplicitClosure' => [
                'code' => '<?php
                    $get = array_map(function($str) { return trim($str);}, $_GET);
                    echo $get["test"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapExplicitTypedClosure' => [
                'code' => '<?php
                    $get = array_map(function(string $str) : string { return trim($str);}, $_GET);
                    echo $get["test"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapExplicitArrowFunction' => [
                'code' => '<?php
                    $get = array_map(fn($str) => trim($str), $_GET);
                    echo $get["test"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughArrayMapImplicitFunctionCall' => [
                'code' => '<?php
                    $a = ["test" => $_GET["name"]];
                    $get = array_map("trim", $a);
                    echo $get["test"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintFilterVarCallback' => [
                'code' => '<?php
                    $get = filter_var($_GET, FILTER_CALLBACK, ["options" => "trim"]);

                    echo $get["test"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintAfterReconciledType' => [
                'code' => '<?php
                    $input = $_GET[\'input\'];
                    if (is_string($input)) {
                        echo "$input";
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintExit' => [
                'code' => '<?php
                    if (rand(0, 1)) {
                        /** @psalm-suppress PossiblyInvalidArgument */
                        exit($_GET[\'a\']);
                    } else {
                        /** @psalm-suppress PossiblyInvalidArgument */
                        die($_GET[\'b\']);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedMethod' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class Unsafe {
                        public function isUnsafe() {
                            return $_GET["unsafe"];
                        }
                    }
                    $a = new Unsafe();
                    echo $a->isUnsafe();',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedMethodForAnonymousInstance' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class Unsafe {
                        public function isUnsafe() {
                            return $_GET["unsafe"];
                        }
                    }
                    echo (new Unsafe())->isUnsafe();',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedMethodForStubMadeInstance' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class Unsafe {
                        public function isUnsafe() {
                            return $_GET["unsafe"];
                        }
                    }

                    /** @psalm-suppress InvalidReturnType */
                    function stub(): Unsafe { }

                    /** @psalm-suppress MixedArgument */
                    echo stub()->isUnsafe();',
                'error_message' => 'TaintedHtml',
            ],
            'doTaintSpecializedInstanceProperty' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    class StringHolder {
                        public $x;

                        public function __construct(string $x) {
                            $this->x = $x;
                        }
                    }

                    $b = new StringHolder($_GET["x"]);

                    echo $b->x;',
                'error_message' => 'TaintedHtml',
            ],
            'taintUnserialize' => [
                'code' => '<?php
                    $cb = unserialize($_POST[\'x\']);',
                'error_message' => 'TaintedUnserialize',
            ],
            'taintCreateFunction' => [
                'code' => '<?php
                    $cb = create_function(\'$a\', $_GET[\'x\']);',
                'error_message' => 'TaintedEval',
                'php_version' => '7.0',
            ],
            'taintException' => [
                'code' => '<?php
                    $e = new Exception();
                    echo $e;',
                'error_message' => 'TaintedHtml',
            ],
            'taintError' => [
                'code' => '<?php
                    function foo() {}
                    try {
                        foo();
                    } catch (TypeError $e) {
                        echo "Caught: {$e->getTraceAsString()}\n";
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThrowable' => [
                'code' => '<?php
                    function foo() {}
                    try {
                        foo();
                    } catch (Throwable $e) {
                        echo "Caught: $e";  // TODO: ("Caught" . $e) does not work.
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintReturnedArray' => [
                'code' => '<?php
                    function processParams(array $params) : array {
                        if (isset($params["foo"])) {
                            return $params;
                        }

                        return [];
                    }

                    $params = processParams($_GET);

                    echo $params["foo"];',
                'error_message' => 'TaintedHtml',
            ],
            'taintFlow' => [
                'code' => '<?php
                    /**
                     * @psalm-flow ($r) -> return
                     */
                    function some_stub(string $r): string { return ""; }

                    $r = $_GET["untrusted"];

                    echo some_stub($r);',
                'error_message' => 'TaintedHtml',
            ],
            'taintFlowProxy' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-sink callable $in
                     */
                    function dummy_taint_sink(string $in): void {}

                    /**
                     * @psalm-flow proxy dummy_taint_sink($r)
                     */
                    function some_stub(string $r): string {}

                    $r = $_GET["untrusted"];

                    some_stub($r);',
                'error_message' => 'TaintedCallable',
            ],
            'taintFlowProxyAndReturn' => [
                'code' => '<?php
                    function dummy_taintable(string $in): string {
                        return $in;
                    }

                    /**
                     * @psalm-flow proxy dummy_taintable($r) -> return
                     */
                    function some_stub(string $r): string {}

                    $r = $_GET["untrusted"];

                    echo some_stub($r);',
                'error_message' => 'TaintedHtml',
            ],
            'taintFlowMethodProxyAndReturn' => [
                'code' => '<?php
                    class dummy {
                        public function taintable(string $in): string {
                            return $in;
                        }
                    }

                    /**
                     * @psalm-flow proxy dummy::taintable($r) -> return
                     */
                    function some_stub(string $r): string {}

                    $r = $_GET["untrusted"];

                    echo some_stub($r);',
                'error_message' => 'TaintedHtml',
            ],
            'taintPopen' => [
                'code' => '<?php
                    /** @psalm-suppress PossiblyInvalidCast */
                    $cb = popen($_POST[\'x\'], \'r\');',
                'error_message' => 'TaintedShell',
            ],
            'taintProcOpen' => [
                'code' => '<?php
                    $arr = [];
                    $cb = proc_open($_POST[\'x\'], [], $arr);',
                'error_message' => 'TaintedShell',
            ],
            'taintedCurlInit' => [
                'code' => '<?php
                    $ch = curl_init($_GET[\'url\']);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintedCurlSetOpt' => [
                'code' => '<?php
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $_GET[\'url\']);',
                'error_message' => 'TaintedSSRF',
            ],
            'taintThroughChildConstructorWithoutMethodOverride' => [
                'code' => '<?php //--taint-analysis
                    class A {
                        private $taint;

                        public function __construct($taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {}


                    $b = new B($_GET["bar"]);
                    echo $b->getTaint();',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughChildConstructorCallingParentMethod' => [
                'code' => '<?php //--taint-analysis
                    class A {
                        private $taint;

                        public function __construct($taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {}

                    class C extends B {}

                    $c = new C($_GET["bar"]);

                    function foo(B $b) {
                        echo $b->getTaint();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughChildConstructorCallingGrandParentMethod' => [
                'code' => '<?php //--taint-analysis
                    class A {
                        private $taint;

                        public function __construct($taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {}

                    class C extends B {}

                    $c = new C($_GET["bar"]);

                    function foo(A $a) {
                        echo $a->getTaint();
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughChildConstructorWhenMethodOverriddenWithParentConstructorCall' => [
                'code' => '<?php //--taint-analysis
                    class A {
                        private $taint;

                        public function __construct($taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {
                        public function __construct($taint) {
                            parent::__construct($taint);
                        }
                    }

                    $b = new B($_GET["bar"]);
                    echo $b->getTaint();',
                'error_message' => 'TaintedHtml',
            ],
            'taintedLdapSearch' => [
                'code' => '<?php
                    $ds = ldap_connect(\'example.com\');
                    $dn = \'o=Psalm, c=US\';
                    $filter = $_GET[\'filter\'];
                    ldap_search($ds, $dn, $filter, []);',
                'error_message' => 'TaintedLdap',
            ],
            'taintedFile' => [
                'code' => '<?php
                file_get_contents($_GET[\'taint\']);',
            'error_message' => 'TaintedFile',
            ],
            'taintedHeader' => [
                'code' => '<?php
                header($_GET[\'taint\']);',
            'error_message' => 'TaintedHeader',
            ],
            'taintedCookie' => [
                'code' => '<?php
                setcookie($_GET[\'taint\'], \'value\');',
            'error_message' => 'TaintedCookie',
            ],
            'variadicTaintPropagation' => [
                'code' => '<?php

                /**
                 * @psalm-pure
                 *
                 * @param string|int|float $args
                 *
                 * @psalm-flow ($format, $args) -> return
                 */
                function variadic_test(string $format, ...$args) : string {
                }

                echo variadic_test(\'\', \'\', $_GET[\'taint\'], \'\');',
                'error_message' => 'TaintedHtml',
            ],
            'potentialTaintThroughChildClassSettingProperty' => [
                'code' => '<?php
                    class A {
                        public string $taint = "";

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    class B extends A {
                        public function __construct(string $taint) {
                            $this->taint = $taint;
                        }
                    }

                    $b = new B($_GET["bar"]);
                    echo $b->getTaint();',
                'error_message' => 'TaintedHtml',
            ],
            'immutableClassTrackInputThroughMethod' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        private string $taint = "";

                        public function __construct(string $taint) {
                            $this->taint = $taint;
                        }

                        public function getTaint() : string {
                            return $this->taint;
                        }
                    }

                    $a = new A($_GET["bar"]);
                    echo $a->getTaint();',
                'error_message' => 'TaintedHtml',
            ],
            'strTrReturnTypeTaint' => [
                'code' => '<?php
                    $input = strtr(\'data\', $_GET[\'taint\'], \'data\');
                    setcookie($input, \'value\');',
                'error_message' => 'TaintedCookie',
            ],
            'conditionallyEscapedTaintPassedFalse' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-escape ($escape is true ? "html" : null)
                     */
                    function foo(string $string, bool $escape = true): string {
                        if ($escape) {
                            $string = htmlspecialchars($string);
                        }

                        return $string;
                    }

                    echo foo($_GET["foo"], false);',
                'error_message' => 'TaintedHtml',
            ],
            'suppressOneCatchAnother' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function data(array $data, string $key) {
                        return $data[$key];
                    }

                    function get(string $key) {
                        return data($_GET, $key);
                    }

                    function post(string $key) {
                        return data($_POST, $key);
                    }

                    echo get("x");
                    /** @psalm-suppress TaintedInput */
                    echo post("x");',
                'error_message' => 'TaintedHtml',
            ],
            'taintSpecializedTwice' => [
                'code' => '<?php
                    /** @psalm-taint-specialize */
                    function data(array $data, string $key) {
                        return $data[$key];
                    }

                    /** @psalm-taint-specialize */
                    function get(string $key) {
                        return data($_GET, $key);
                    }

                    echo get("x");',
                'error_message' => 'TaintedHtml',
            ],
            'conditionallyEscapedTaintsAll' => [
                'code' => '<?php
                    /** @psalm-taint-escape ($type is "int" ? "html" : null) */
                    function cast(mixed $value, string $type): mixed
                    {
                        if ("int" === $type) {
                            return (int) $value;
                        }
                        return (string) $value;
                    }

                    /** @psalm-taint-specialize */
                    function data(array $data, string $key, string $type) {
                        return cast($data[$key], $type);
                    }

                    // technically a false-positive, but desired behaviour in lieu
                    // of better information
                    echo data($_GET, "x", "int");',
                'error_message' => 'TaintedHtml',
            ],
            'psalmFlowOnInstanceMethod' => [
                'code' => '<?php //--taint-analysis
                    class Wdb {
                        /**
                          * @psalm-pure
                          *
                          * @param string $text
                          * @return string
                          * @psalm-flow ($text) -> return
                          */
                        public function esc_like($text) {}

                        /**
                          * @param string $query
                          * @return int|bool
                          *
                          * @psalm-taint-sink sql $query
                          */
                        public function query($query){}
                    }

                    $wdb = new Wdb();

                    $order = $wdb->esc_like($_GET["order"]);
                    $res = $wdb->query("SELECT blah FROM tablea ORDER BY ". $order. " DESC");',
                'error_message' => 'TaintedSql',
            ],
            'psalmFlowOnStaticMethod' => [
                'code' => '<?php //--taint-analysis
                    class Wdb {
                        /**
                          * @psalm-pure
                          *
                          * @param string $text
                          * @return string
                          * @psalm-flow ($text) -> return
                          */
                        public static function esc_like($text) {}

                        /**
                          * @param string $query
                          * @return int|bool
                          *
                          * @psalm-taint-sink sql $query
                          */
                        public static function query($query){}
                    }

                    $order = Wdb::esc_like($_GET["order"]);
                    $res = Wdb::query("SELECT blah FROM tablea ORDER BY ". $order. " DESC");',
                'error_message' => 'TaintedSql',
            ],
            'keysAreTainted' => [
                'code' => '<?php
                    function takesArray(array $arr): void {
                        foreach ($arr as $key => $_) {
                            echo $key;
                        }
                    }

                    takesArray([$_GET["bad"] => "good"]);',
                'error_message' => 'TaintedHtml',
            ],
            'resultOfPlusIsTaintedOnArrays' => [
                'code' => '<?php
                    scope($_GET["foo"]);
                    function scope(array $foo)
                    {
                        $var = $foo + [];
                        var_dump($var);
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayKeyWithExplicitSink' => [
                'code' => '<?php
                    /** @psalm-taint-sink html $values */
                    function doTheMagic(array $values) {}

                    doTheMagic([(string)$_GET["bad"] => "foo"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintThroughReset' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        if ($arr) {
                            echo reset($arr);
                        }
                    }

                    foo([$_GET["a"]]);',
                'error_message' => 'TaintedHtml',
            ],
            'shellExecBacktick' => [
                'code' => '<?php

                    $input = $_GET["input"];
                    $x = `$input`;
                    ',
                'error_message' => 'TaintedShell',
            ],
            'shellExecBacktickConcat' => [
                'code' => '<?php

                    $input = $_GET["input"];
                    $x = `ls $input`;
                    ',
                'error_message' => 'TaintedShell',
            ],
            /*
            // TODO: Stubs do not support this type of inference even with $this->message = $message.
            // Most uses of getMessage() would be with caught exceptions, so this is not representative of real code.
            'taintException' => [
                '<?php
                    $x = new Exception($_GET["x"]);
                    echo $x->getMessage();',
                'error_message' => 'TaintedHtml',
            ],
            */
            'castToArrayPassTaints' => [
                'code' => '<?php
                    $args = $_POST;

                    $args = (array) $args;

                    pg_query($connection, "SELECT * FROM tableA where key = " .$args["key"]);
                    ',
                'error_message' => 'TaintedSql',
            ],
            'taintSinkWithComments' => [
                'code' => '<?php

                    /**
                     * @psalm-taint-sink html $sink
                     *
                     * Not working
                     */
                    function sinkNotWorking($sink) : string {}

                    echo sinkNotWorking($_GET["taint"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintEscapedInTryMightNotWork' => [
                'code' => '<?php
                    /** @psalm-taint-escape html */
                    function escapeHtml(string $arg): string
                    {
                        return htmlspecialchars($arg);
                    }

                    $tainted = $_GET["foo"];

                    try {
                        $tainted = escapeHtml($tainted);
                    } catch (Throwable $_) {
                    }

                    echo $tainted;
                ',
                'error_message' => 'TaintedHtml',
            ],
            'taintArrayWithOffsetUpdated' => [
                'code' => '<?php
                    function foo() {
                        $foo = [
                            "a" => [["c" => "hello"]],
                            "b" => [],
                        ];

                        $foo["b"][] = [
                            "c" => $_GET["bad"],
                        ];

                        bar($foo["b"]);
                    }

                    function bar(array $arr): void {
                        foreach ($arr as $s) {
                            echo $s["c"];
                        }
                    }',
                'error_message' => 'TaintedHtml',
            ],
            'checkMemoizedStaticMethodCallTaints' => [
                'code' => '<?php
                    class A {
                        private static string $prev = "";

                        public static function getPrevious(string $s): string {
                            $prev = self::$prev;
                            self::$prev = $s;
                            return $prev;
                        }
                    }

                    A::getPrevious($_GET["a"]);
                    echo A::getPrevious("foo");',
                'error_message' => 'TaintedHtml',
            ],
            'taintedNewCall' => [
                'code' => '<?php
                    $a = $_GET["a"];
                    $b = $_GET["b"];
                    new $a($b);',
                'error_message' => 'TaintedCallable',
            ],
            'urlencode' => [
                /**
                 * urlencode() should only prevent html & has_quotes taints
                 * All other taint types should be unaffected.
                 * We arbitrarily chose system() to test this.
                 */
                'code' => '<?php
                    /** @psalm-suppress PossiblyInvalidArgument */
                    system(urlencode($_GET["bad"]));
                ',
                'error_message' => 'TaintedShell',
            ],
            'assertMysqliOnlyEscapesSqlTaints1' => [
                'code' => '<?php
                    $mysqli = new mysqli();
                    echo $mysqli->escape_string($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertMysqliOnlyEscapesSqlTaints2' => [
                'code' => '<?php
                    $mysqli = new mysqli();
                    echo $mysqli->real_escape_string($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertMysqliOnlyEscapesSqlTaints3' => [
                'code' => '<?php
                    $mysqli = new mysqli();
                    echo mysqli_escape_string($mysqli, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertMysqliOnlyEscapesSqlTaints4' => [
                'code' => '<?php
                    $mysqli = new mysqli();
                    echo mysqli_real_escape_string($mysqli, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertDb2OnlyEscapesSqlTaints' => [
                'code' => '<?php
                    echo db2_escape_string($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertCubridOnlyEscapesSqlTaints' => [
                'code' => '<?php
                    echo cubrid_real_escape_string($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertSQLiteOnlyEscapesSqlTaints' => [
                'code' => '<?php
                    echo SQLite3::escapeString($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints1' => [
                'code' => '<?php
                    echo pg_escape_bytea($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints2' => [
                'code' => '<?php
                    echo pg_escape_bytea($conn, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints3' => [
                'code' => '<?php
                    echo pg_escape_identifier($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints4' => [
                'code' => '<?php
                    echo pg_escape_identifier($conn, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints5' => [
                'code' => '<?php
                    echo pg_escape_literal($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints6' => [
                'code' => '<?php
                    echo pg_escape_literal($conn, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints7' => [
                'code' => '<?php
                    echo pg_escape_string($_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'assertPGOnlyEscapesSqlTaints8' => [
                'code' => '<?php
                    echo pg_escape_string($conn, $_GET["a"]);',
                'error_message' => 'TaintedHtml',
            ],
            'taintedReflectionClass' => [
                'code' => '<?php
                    $name = $_GET["name"];
                    $reflector = new ReflectionClass($name);
                    $reflector->newInstance();',
                'error_message' => 'TaintedCallable',
            ],
            'taintedReflectionFunction' => [
                'code' => '<?php
                    $name = $_GET["name"];
                    $function = new ReflectionFunction($name);
                    $function->invoke();',
                'error_message' => 'TaintedCallable',
            ],
            'querySimpleXMLElement' => [
                'code' => '<?php
                    function queryExpression(SimpleXMLElement $xml) : array|false|null {
                        $expression = $_GET["expression"];
                        return $xml->xpath($expression);
                    }',
                'error_message' => 'TaintedXpath',
            ],
            'queryDOMXPath' => [
                'code' => '<?php
                    function queryExpression(DOMXPath $xpath) : mixed {
                        $expression = $_GET["expression"];
                        return $xpath->query($expression);
                    }',
                'error_message' => 'TaintedXpath',
            ],
            'evaluateDOMXPath' => [
                'code' => '<?php
                    function evaluateExpression(DOMXPath $xpath) : mixed {
                        $expression = $_GET["expression"];
                        return $xpath->evaluate($expression);
                    }',
                'error_message' => 'TaintedXpath',
            ],
            'taintedSleep' => [
                'code' => '<?php
                    sleep($_GET["seconds"]);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedUsleep' => [
                'code' => '<?php
                    usleep($_GET["microseconds"]);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedTimeNanosleepSeconds' => [
                'code' => '<?php
                    time_nanosleep($_GET["seconds"], 42);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedTimeNanosleepNanoseconds' => [
                'code' => '<?php
                    time_nanosleep(42, $_GET["nanoseconds"]);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedTimeSleepUntil' => [
                'code' => '<?php
                    time_sleep_until($_GET["timestamp"]);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedExtract' => [
                'code' => '<?php
                    $array = $_GET;
                    extract($array);',
                'error_message' => 'TaintedExtract',
            ],
            'extractPost' => [
                'code' => '<?php
                    extract($_POST);',
                'error_message' => 'TaintedExtract',
            ],
            'TaintForIntTypeCastUsingAnnotatedSink' => [
                'code' => '<?php // --taint-analysis
                    /** @param int $id */
                    function fetch($id): string
                    {
                        return query("SELECT * FROM table WHERE id=" . $id);
                    }
                    /**
                     * @return string
                     * @psalm-taint-sink sql $sql
                     * @psalm-taint-specialize
                     */
                    function query(string $sql) {}
                    $value = $_GET["value"];
                    $result = fetch($value);',
                'error_message' => 'TaintedSql',
            ],
            'TaintForIntSleep' => [
                'code' => '<?php // --taint-analysis
                    function s(int $id): void
                    {
                        sleep($id);
                    }
                    $value = $_GET["value"];
                    s($value);',
                'error_message' => 'TaintedSleep',
            ],
            'taintedExecuteQueryFunction' => [
                'code' => '<?php
                    $userId = $_GET["user_id"];
                    $query = "delete from users where user_id = " . $userId;
                    $link = mysqli_connect("localhost", "my_user", "my_password", "world");
                    $result = mysqli_execute_query($link, $query);',
                'error_message' => 'TaintedSql',
            ],
            'taintedExecuteQueryMethod' => [
                'code' => '<?php
                    $userId = $_GET["user_id"];
                    $query = "delete from users where user_id = " . $userId;
                    $mysqli = new mysqli("localhost", "my_user", "my_password", "world");
                    $result = $mysqli->execute_query($query);',
                'error_message' => 'TaintedSql',
            ],
            'taintedRegisterShutdownFunction' => [
                'code' => '<?php
                    $foo = $_GET["foo"];
                    register_shutdown_function($foo);',
                'error_message' => 'TaintedCallable',
            ],
            'taintedRegisterTickFunction' => [
                'code' => '<?php
                    $foo = $_GET["foo"];
                    register_tick_function($foo);',
                'error_message' => 'TaintedCallable',
            ],
            'taintedForwardStaticCall' => [
                'code' => '<?php
                    $foo = $_GET["foo"];
                    class B
                    {
                        public static function test($foo) {
                            forward_static_call($foo, "one", "two");
                        }
                    }
                    B::test($foo);',
                'error_message' => 'TaintedCallable',
            ],
            'taintedForwardStaticCallArray' => [
                'code' => '<?php
                    $foo = $_GET["foo"];
                    class B
                    {
                        public static function test($foo) {
                            forward_static_call_array($foo, array("one", "two"));
                        }
                    }
                    B::test($foo);',
                'error_message' => 'TaintedCallable',
            ],
        ];
    }

    /**
     * @param list<string> $expectedIssuesTypes
     * @test
     * @dataProvider multipleTaintIssuesAreDetectedDataProvider
     */
    public function multipleTaintIssuesAreDetected(string $code, array $expectedIssuesTypes): void
    {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        // disables issue exceptions - we need all, not just the first
        $this->testConfig->throw_exception = false;
        $filePath = self::$src_dir_path . 'somefile.php';
        $this->addFile($filePath, $code);
        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($filePath, new Context(), false);

        $actualIssueTypes = array_map(
            static fn(IssueData $issue): string => $issue->type . '{ ' . trim($issue->snippet) . ' }',
            array_values(array_filter(
                IssueBuffer::getIssuesDataForFile($filePath),
                static fn(IssueData $issue): bool => !in_array($issue->type, self::IGNORE, true),
            )),
        );
        self::assertSame($expectedIssuesTypes, $actualIssueTypes);
    }

    /**
     * @return array<string, array{code: string, expectedIssueTypes: list<string>}>
     */
    public function multipleTaintIssuesAreDetectedDataProvider(): array
    {
        return [
            'taintSinkFlow' => [
                'code' => '<?php
                    /**
                     * @param string $value
                     * @return string
                     *
                     * @psalm-flow ($value) -> return
                     * @psalm-taint-sink html $value
                     */
                    function process(string $value): string { return ""; }
                    $data = process((string)($_GET["inject"] ?? ""));
                    exec($data);
                ',
                'expectedIssueTypes' => [
                    'TaintedHtml{ function process(string $value): string { return ""; } }',
                    'TaintedShell{ exec($data); }',
                ],
            ],
            'taintSinkCascade' => [
                'code' => '<?php
                    function triggerHtml(string $value): string
                    {
                        echo $value;
                        return $value;
                    }
                    function triggerShell(string $value): string
                    {
                        exec($value);
                        return $value;
                    }
                    function triggerFile(string $value): string
                    {
                        file_get_contents($value);
                        return $value;
                    }
                    $value = (string)($_GET["inject"] ?? "");
                    $value = triggerHtml($value);
                    $value = triggerShell($value);
                    $value = triggerFile($value);
                ',
                'expectedIssueTypes' => [
                    'TaintedHtml{ echo $value; }',
                    'TaintedTextWithQuotes{ echo $value; }',
                    'TaintedShell{ exec($value); }',
                    'TaintedFile{ file_get_contents($value); }',
                ],
            ],
            'taintedIncludes' => [
                'code' => '<?php
                    $first = (string)($_GET["first"] ?? "");
                    $second = (string)($_GET["second"] ?? "");
                    /** @psalm-suppress UnresolvableInclude */
                    require $first;
                    /** @psalm-suppress MissingFile */
                    require dirname(__DIR__)."/first.php";
                    /** @psalm-suppress UnresolvableInclude */
                    require $second;
                    /** @psalm-suppress MissingFile */
                    require dirname(__DIR__)."/second.php";
                ',
                'expectedIssueTypes' => [
                    'TaintedInclude{ require $first; }',
                    'TaintedInclude{ require $second; }',
                ],
            ],
        ];
    }
}
