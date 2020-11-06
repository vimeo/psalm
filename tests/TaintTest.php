<?php
namespace Psalm\Tests;

use Psalm\Context;
use const DIRECTORY_SEPARATOR;

class TaintTest extends TestCase
{
    /**
     * @dataProvider providerValidCodeParse
     *
     *
     */
    public function testValidCode(string $code): void
    {
        $test_name = $this->getTestName();
        if (\strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        if (\strtoupper(\substr(\PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Skip taint tests in Windows for now');
        }

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $code
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context(), false);
    }

    /**
     * @dataProvider providerInvalidCodeParse
     *
     *
     */
    public function testInvalidCode(string $code, string $error_message): void
    {
        if (\strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        if (\strtoupper(\substr(\PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Skip taint tests in Windows for now');
        }

        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessageRegExp('/\b' . \preg_quote($error_message, '/') . '\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $code
        );

        $this->project_analyzer->trackTaintedInputs();

        $this->analyzeFile($file_path, new Context(), false);
    }

    /**
     * @return array<string, array{string}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'taintedInputInCreatedArrayNotEchoed' => [
                '<?php
                    $name = $_GET["name"] ?? "unknown";
                    $id = (int) $_GET["id"];

                    $data = ["name" => $name, "id" => $id];

                    echo "<h1>" . htmlentities($data["name"]) . "</h1>";
                    echo "<p>" . $data["id"] . "</p>";'
            ],
            'taintedInputInAssignedArrayNotEchoed' => [
                '<?php
                    $name = $_GET["name"] ?? "unknown";
                    $id = (int) $_GET["id"];

                    $data = [];
                    $data["name"] = $name;
                    $data["id"] = $id;

                    echo "<h1>" . htmlentities($data["name"]) . "</h1>";
                    echo "<p>" . $data["id"] . "</p>";'
            ],
            'taintedInputDirectlySuppressed' => [
                '<?php
                    class A {
                        public function deleteUser(PDO $pdo) : void {
                            /** @psalm-taint-escape sql */
                            $userId = (string) $_GET["user_id"];
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }'
            ],
            'taintedInputDirectlySuppressedWithOtherUse' => [
                '<?php
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
                    }'
            ],
            'taintedInputToParamButSafe' => [
                '<?php
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
                    }'
            ],
            'ValidatedInputFromParam' => [
                '<?php
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
                    }'
            ],
            'untaintedInputAfterIntCast' => [
                '<?php
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
                    }'
            ],
            'specializedCoreFunctionCall' => [
                '<?php
                    $a = (string) $_GET["user_id"];

                    echo print_r([], true);

                    $b = print_r($a, true);'
            ],
            'untaintedInputViaStaticFunctionWithSafePath' => [
                '<?php
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
                            echo(htmlentities(Utils::shorten((string) $_GET["user_id"])));
                        }

                        public function bar() : void {
                            echo(Utils::shorten("hello"));
                        }
                    }'
            ],
            'taintHtmlEntities' => [
                '<?php
                    function foo() : void {
                        $a = htmlentities((string) $_GET["bad"]);
                        echo $a;
                    }'
            ],
            'taintOnStrReplaceCallRemovedInFunction' => [
                '<?php
                    class U {
                        /**
                         * @psalm-pure
                         * @psalm-taint-escape html
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
                    }'
            ],
            'taintOnPregReplaceCallRemovedInFunction' => [
                '<?php
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
                    }'
            ],
            'taintOnStrReplaceCallRemovedInline' => [
                '<?php
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
                             */
                            $a = str_replace("foo", "bar", $o->s);
                            echo $a;
                        }
                    }'
            ],
            'NoTaintsOnSimilarPureCall' => [
                '<?php
                    class U {
                        /** @psalm-pure */
                        public static function shorten(string $s) : string {
                            return substr($s, 0, 15);
                        }

                        /** @psalm-pure */
                        public static function escape(string $s) : string {
                            return htmlentities($s);
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
                    }'
            ],
            'taintPropertyPassingObjectWithDifferentValue' => [
                '<?phps
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
                    UserUpdater::doDelete(new PDO(), $userObj);'
            ],
            'taintPropertyWithoutPassingObject' => [
                '<?phps
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

                    $userObj = new User((string) $_GET["user_id"]);',
            ],
            'specializeStaticMethod' => [
                '<?php
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

                    echo StringUtility::slugify("hello");'
            ],
            'taintFreeNestedArray' => [
                '<?php
                    $a = [];
                    $a[] = ["a" => $_GET["name"], "b" => "foo"];

                    foreach ($a as $m) {
                        echo $m["b"];
                    }'
            ],
            'taintFreeNestedArrayWithOffsetAccessedExplicitly' => [
                '<?php
                    $a = [];
                    $a[] = ["a" => $_GET["name"], "b" => "foo"];

                    echo $a[0]["b"];',
            ],
            'intUntainted' => [
                '<?php
                    $input = $_GET[\'input\'];
                    if (is_int($input)) {
                        echo "$input";
                    }',
            ],
            'dontTaintSpecializedInstanceProperty' => [
                '<?php
                    /** @psalm-taint-specialize */
                    class StringHolder {
                        public $x;

                        public function __construct(string $x) {
                            $this->x = $x;
                        }
                    }

                    $a = new StringHolder("a");
                    $b = new StringHolder($_GET["x"]);

                    echo $a->x;'
            ],
            'suppressTaintedInput' => [
                '<?php
                    function unsafe() {
                        /**
                         * @psalm-suppress TaintedInput
                         */
                        echo $_GET["x"];
                    }'
            ],
            'suppressTaintedAssignment' => [
                '<?php
                    $b = $_GET["x"];

                    /**
                     * @psalm-suppress TaintedInput
                     */
                    $a = $b;


                    echo $a;'
            ]
        ];
    }

    /**
     * @return array<string, array{0: string, error_message: string}>
     */
    public function providerInvalidCodeParse(): array
    {
        return [
            'taintedInputFromMethodReturnTypeSimple' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintedInputFromFunctionReturnType' => [
                '<?php
                    function getName() : string {
                        return $_GET["name"] ?? "unknown";
                    }

                    echo getName();',
                'error_message' => 'TaintedInput - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:26 - Detected tainted html in path: $_GET -> $_GET[\'name\'] (src/somefile.php:3:32) -> coalesce (src/somefile.php:3:32) -> getName (src/somefile.php:2:42) -> call to echo (src/somefile.php:6:26) -> echo#1',
            ],
            'taintedInputFromExplicitTaintSource' => [
                '<?php
                    /**
                     * @psalm-taint-source input
                     */
                    function getName() : string {
                        return "";
                    }

                    echo getName();',
                'error_message' => 'TaintedInput',
            ],
            'taintedInputFromExplicitTaintSourceStaticMethod' => [
                '<?php
                    class Request {
                        /**
                         * @psalm-taint-source input
                         */
                        public static function getName() : string {
                            return "";
                        }
                    }


                    echo Request::getName();',
                'error_message' => 'TaintedInput',
            ],
            'taintedInputFromGetArray' => [
                '<?php
                    function getName(array $data) : string {
                        return $data["name"] ?? "unknown";
                    }

                    $name = getName($_GET);

                    echo $name;',
                'error_message' => 'TaintedInput',
            ],
            'taintedInputFromReturnToInclude' => [
                '<?php
                    $a = (string) $_GET["file"];
                    $b = "hello" . $a;
                    include str_replace("a", "b", $b);',
                'error_message' => 'TaintedInput',
            ],
            'taintedInputFromReturnToEval' => [
                '<?php
                    $a = $_GET["file"];
                    eval("<?php" . $a);',
                'error_message' => 'TaintedInput',
            ],
            'taintedInputFromReturnTypeToEcho' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintedInputInCreatedArrayIsEchoed' => [
                '<?php
                    $name = $_GET["name"] ?? "unknown";

                    $data = ["name" => $name];

                    echo "<h1>" . $data["name"] . "</h1>";',
                'error_message' => 'TaintedInput',
            ],
            'testTaintedInputInAssignedArrayIsEchoed' => [
                '<?php
                    $name = $_GET["name"] ?? "unknown";

                    $data = [];
                    $data["name"] = $name;

                    echo "<h1>" . $data["name"] . "</h1>";',
                'error_message' => 'TaintedInput',
            ],
            'taintedInputDirectly' => [
                '<?php
                    class A {
                        public function deleteUser(PDO $pdo) : void {
                            $userId = (string) $_GET["user_id"];
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintedInputFromReturnTypeWithBranch' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'sinkAnnotation' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintedInputFromParam' => [
                '<?php
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
                'error_message' => 'TaintedInput - src' . DIRECTORY_SEPARATOR . 'somefile.php:17:40 - Detected tainted sql in path: $_GET -> $_GET[\'user_id\'] (src/somefile.php:4:45) -> A::getUserId (src/somefile.php:3:55) -> concat (src/somefile.php:8:36) -> A::getAppendedUserId (src/somefile.php:7:63) -> $userId (src/somefile.php:12:29) -> call to A::deleteUser (src/somefile.php:13:53) -> A::deleteUser#2 (src/somefile.php:16:69) -> $userId (src/somefile.php:16:69) -> concat (src/somefile.php:17:40) -> call to PDO::exec (src/somefile.php:17:40) -> PDO::exec#1',
            ],
            'taintedInputToParam' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintedInputToParamAfterAssignment' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintedInputToParamAlternatePath' => [
                '<?php
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
                'error_message' => 'TaintedInput - src' . DIRECTORY_SEPARATOR . 'somefile.php:23:44 - Detected tainted sql in path: $_GET -> $_GET[\'user_id\'] (src/somefile.php:7:67) -> call to A::getAppendedUserId (src/somefile.php:7:58) -> A::getAppendedUserId#1 (src/somefile.php:11:66) -> $user_id (src/somefile.php:11:66) -> concat (src/somefile.php:12:36) -> A::getAppendedUserId (src/somefile.php:11:78) -> call to A::deleteUser (src/somefile.php:7:33) -> A::deleteUser#3 (src/somefile.php:19:85) -> $userId2 (src/somefile.php:19:85) -> concat (src/somefile.php:23:44) -> call to PDO::exec (src/somefile.php:23:44) -> PDO::exec#1',
            ],
            'taintedInParentLoader' => [
                '<?php
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
                'error_message' => 'TaintedInput - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:44 - Detected tainted sql in path: $_GET -> $_GET[\'user_id\'] (src/somefile.php:28:43) -> call to C::foo (src/somefile.php:28:34) -> C::foo#1 (src/somefile.php:23:52) -> $user_id (src/somefile.php:23:52) -> call to AGrandChild::loadFull (src/somefile.php:24:51) -> AGrandChild::loadFull#1 (src/somefile.php:5:64) -> A::loadFull#1 (src/somefile.php:24:51) -> $sink (src/somefile.php:5:64) -> call to A::loadPartial (src/somefile.php:6:49) -> A::loadPartial#1 (src/somefile.php:3:76) -> AChild::loadPartial#1 (src/somefile.php:6:49) -> $sink (src/somefile.php:15:67) -> concat (src/somefile.php:16:44) -> call to PDO::exec (src/somefile.php:16:44) -> PDO::exec#1',
            ],
            'taintedInputFromProperty' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintedInputFromPropertyViaMixin' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintedInputViaStaticFunction' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintedInputViaPureStaticFunction' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'untaintedInputViaStaticFunctionWithoutSafePath' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintedInputFromMagicProperty' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintOverMixed' => [
                '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedArgument
                     */
                    function foo() : void {
                        $a = $_GET["bad"];
                        echo $a;
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintStrConversion' => [
                '<?php
                    function foo() : void {
                        $a = strtoupper(strtolower((string) $_GET["bad"]));
                        echo $a;
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintIntoExec' => [
                '<?php
                    function foo() : void {
                        $a = (string) $_GET["bad"];
                        exec($a);
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintIntoExecMultipleConcat' => [
                '<?php
                    function foo() : void {
                        $a = "9" . "a" . "b" . "c" . ((string) $_GET["bad"]) . "d" . "e" . "f";
                        exec($a);
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintIntoNestedArrayUnnestedSeparately' => [
                '<?php
                    function foo() : void {
                        $a = [[(string) $_GET["bad"]]];
                        exec($a[0][0]);
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintIntoArrayAndThenOutAgain' => [
                '<?php
                    class C {
                        public static function foo() : array {
                            $a = (string) $_GET["bad"];
                            return [$a];
                        }

                        public static function bar() {
                            exec(self::foo()[0]);
                        }
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintAppendedToArray' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintOnSubstrCall' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintOnStrReplaceCallSimple' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintOnPregReplaceCall' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'IndirectGetAssignment' => [
                '<?php
                    class InputFilter {
                        public string $name;

                        public function __construct(string $name) {
                            $this->name = $name;
                        }

                        /**
                         * @psalm-specialize-call
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
                'error_message' => 'TaintedInput',
            ],
            'taintPropertyPassingObject' => [
                '<?php
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
                    UserUpdater::doDelete(new PDO(), $userObj);',
                'error_message' => 'TaintedInput',
            ],
            'taintPropertyPassingObjectSettingValueLater' => [
                '<?php
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

                    class UserUpdater {
                        public static function doDelete(PDO $pdo, User $user) : void {
                            self::deleteUser($pdo, $user->id);
                        }

                        public static function deleteUser(PDO $pdo, string $userId) : void {
                            $pdo->exec("delete from users where user_id = " . $userId);
                        }
                    }

                    $userObj = new User("5");
                    $userObj->setId((string) $_GET["user_id"]);
                    UserUpdater::doDelete(new PDO(), $userObj);',
                'error_message' => 'TaintedInput',
            ],
            'ImplodeExplode' => [
                '<?php
                    $a = $_GET["name"];
                    $b = explode(" ", $a);
                    $c = implode(" ", $b);
                    echo $c;',
                'error_message' => 'TaintedInput',
            ],
            'ImplodeIndirect' => [
                '<?php
                    /** @var array $unsafe */
                    $unsafe = $_GET[\'unsafe\'];
                    echo implode(" ", $unsafe);',
                'error_message' => 'TaintedInput',
            ],
            'taintThroughPregReplaceCallback' => [
                '<?php
                    $a = $_GET["bad"];

                    $b = preg_replace_callback(
                        \'/foo/\',
                        function (array $matches) : string {
                            return $matches[1];
                        },
                        $a
                    );

                    echo $b;',
                'error_message' => 'TaintedInput',
            ],
            'taintedFunctionWithNoTypes' => [
                '<?php
                    function rawinput() {
                        return $_GET[\'rawinput\'];
                    }

                    echo rawinput();',
                'error_message' => 'TaintedInput',
            ],
            'taintedStaticCallWithNoTypes' => [
                '<?php
                    class A {
                        public static function rawinput() {
                            return $_GET[\'rawinput\'];
                        }
                    }

                    echo A::rawinput();',
                'error_message' => 'TaintedInput',
            ],
            'taintedInstanceCallWithNoTypes' => [
                '<?php
                    class A {
                        public function rawinput() {
                            return $_GET[\'rawinput\'];
                        }
                    }

                    echo (new A())->rawinput();',
                'error_message' => 'TaintedInput',
            ],
            'taintStringObtainedUsingStrval' => [
                '<?php
                    $unsafe = strval($_GET[\'unsafe\']);
                    echo $unsafe',
                'error_message' => 'TaintedInput',
            ],
            'taintStringObtainedUsingSprintf' => [
                '<?php
                    $unsafe = sprintf("%s", strval($_GET[\'unsafe\']));
                    echo $unsafe;',
                'error_message' => 'TaintedInput',
            ],
            'encapsulatedString' => [
                '<?php
                    $unsafe = $_GET[\'unsafe\'];
                    echo "$unsafe";',
                'error_message' => 'TaintedInput',
            ],
            'encapsulatedToStringMagic' => [
                '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }
                    $unsafe = new MyClass();
                    echo "unsafe: $unsafe";',
                'error_message' => 'TaintedInput',
            ],
            'castToStringMagic' => [
                '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }
                    $unsafe = new MyClass();
                    echo $unsafe;',
                'error_message' => 'TaintedInput',
            ],
            'castToStringViaArgument' => [
                '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }

                    function doesEcho(string $s) {
                        echo $s;
                    }

                    $unsafe = new MyClass();

                    doesEcho($unsafe);',
                'error_message' => 'TaintedInput',
            ],
            'toStringTaintInSubclass' => [
                '<?php // --taint-analysis
                    class TaintedBaseClass {
                        /** @psalm-taint-source input */
                        public function __toString() {
                            return "x";
                        }
                    }
                    class TaintedSubclass extends TaintedBaseClass {}
                    $x = new TaintedSubclass();
                    echo "Caught: $x\n";',
                'error_message' => 'TaintedInput',
            ],
            'implicitToStringMagic' => [
                '<?php
                    class MyClass {
                        public function __toString() {
                            return $_GET["blah"];
                        }
                    }
                    $unsafe = new MyClass();
                    echo $unsafe;',
                'error_message' => 'TaintedInput',
            ],
            'namespacedFunction' => [
                '<?php
                    namespace ns;

                    function identity(string $s) : string {
                        return $s;
                    }

                    echo identity($_GET[\'userinput\']);',
                'error_message' => 'TaintedInput',
            ],
            'print' => [
                '<?php
                    print($_GET["name"]);',
                'error_message' => 'TaintedInput - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:27 - Detected tainted html in path: $_GET -> $_GET[\'name\'] (src/somefile.php:2:27) -> call to print (src/somefile.php:2:27) -> print#1',
            ],
            'unpackArgs' => [
                '<?php
                    function test(...$args) {
                        echo $args[0];
                    }
                    test(...$_GET["other"]);',
                'error_message' => 'TaintedInput',
            ],
            'foreachArg' => [
                '<?php
                    $a = $_GET["bad"];

                    foreach ($a as $arg) {
                        echo $arg;
                    }',
                'error_message' => 'TaintedInput',
            ],
            'magicPropertyType' => [
                '<?php
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
                'error_message' => 'TaintedInput',
            ],
            'taintNestedArrayWithOffsetAccessedInForeach' => [
                '<?php
                    $a = [];
                    $a[0] = ["a" => $_GET["name"], "b" => "foo"];

                    foreach ($a as $m) {
                        echo $m["a"];
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintNestedArrayWithOffsetAccessedExplicitly' => [
                '<?php
                    $a = [];
                    $a[] = ["a" => $_GET["name"], "b" => "foo"];

                    echo $a[0]["a"];',
                'error_message' => 'TaintedInput',
            ],
            'taintThroughArrayMapExplicitClosure' => [
                '<?php
                    $get = array_map(function($str) { return trim($str);}, $_GET);
                    echo $get["test"];',
                'error_message' => 'TaintedInput',
            ],
            'taintThroughArrayMapExplicitTypedClosure' => [
                '<?php
                    $get = array_map(function(string $str) : string { return trim($str);}, $_GET);
                    echo $get["test"];',
                'error_message' => 'TaintedInput',
            ],
            'taintThroughArrayMapExplicitArrowFunction' => [
                '<?php
                    $get = array_map(fn($str) => trim($str), $_GET);
                    echo $get["test"];',
                'error_message' => 'TaintedInput',
            ],
            'taintThroughArrayMapImplicitFunctionCall' => [
                '<?php
                    $a = ["test" => $_GET["name"]];
                    $get = array_map("trim", $a);
                    echo $get["test"];',
                'error_message' => 'TaintedInput',
            ],
            'taintFilterVar' => [
                '<?php
                    $get = filter_var($_GET, FILTER_CALLBACK, ["options" => "trim"]);

                    echo $get["test"];',
                'error_message' => 'TaintedInput',
            ],
            'taintAfterReconciledType' => [
                '<?php
                    $input = $_GET[\'input\'];
                    if (is_string($input)) {
                        echo "$input";
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintExit' => [
                '<?php
                    if (rand(0, 1)) {
                        exit($_GET[\'a\']);
                    } else {
                        die($_GET[\'b\']);
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintSpecializedMethod' => [
                '<?php
                    /** @psalm-taint-specialize */
                    class Unsafe {
                        public function isUnsafe() {
                            return $_GET["unsafe"];
                        }
                    }
                    $a = new Unsafe();
                    echo $a->isUnsafe();',
                'error_message' => 'TaintedInput',
            ],
            'taintSpecializedInstanceProperty' => [
                '<?php
                    /** @psalm-taint-specialize */
                    class StringHolder {
                        public $x;

                        public function __construct(string $x) {
                            $this->x = $x;
                        }
                    }

                    $b = new StringHolder($_GET["x"]);

                    echo $b->x;',
                'error_message' => 'TaintedInput',
            ],
            'taintUnserialize' => [
                '<?php
                    $cb = unserialize($_POST[\'x\']);',
                'error_message' => 'TaintedInput',
            ],
            'taintCreateFunction' => [
                '<?php
                    $cb = create_function(\'$a\', $_GET[\'x\']);',
                'error_message' => 'TaintedInput',
            ],
            'taintException' => [
                '<?php
                    $e = new Exception();
                    echo $e;',
                'error_message' => 'TaintedInput',
            ],
            'taintError' => [
                '<?php
                    function foo() {}
                    try {
                        foo();
                    } catch (TypeError $e) {
                        echo "Caught: {$e->getTraceAsString()}\n";
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintThrowable' => [
                '<?php
                    function foo() {}
                    try {
                        foo();
                    } catch (Throwable $e) {
                        echo "Caught: $e";  // TODO: ("Caught" . $e) does not work.
                    }',
                'error_message' => 'TaintedInput',
            ],
            'taintReturnedArray' => [
                '<?php
                    function processParams(array $params) : array {
                        if (isset($params["foo"])) {
                            return $params;
                        }

                        return [];
                    }

                    $params = processParams($_GET);

                    echo $params["foo"];',
                'error_message' => 'TaintedInput',
            ],
            'taintFlow' => [
                '<?php

                /**
                 * @psalm-flow ($r) -> return
                 */
                function some_stub(string $r): string {}

                $r = $_GET["untrusted"];

                echo some_stub($r);',
                'error_message' => 'TaintedInput',
            ],
            'taintFlowPassthru' => [
                '<?php

                /**
                 * @psalm-taint-sink text $in
                 */
                function dummy_taint_sink(string $in): void {}

                /**
                 * @psalm-flow passthru dummy_taint_sink($r)
                 */
                function some_stub(string $r): string {}

                $r = $_GET["untrusted"];

                some_stub($r);',
                'error_message' => 'TaintedInput',
            ],
            'taintFlowPassthruAndReturn' => [
                '<?php

                function dummy_taintable(string $in): string {
                    return $in;
                }

                /**
                 * @psalm-flow passthru dummy_taintable($r) -> return
                 */
                function some_stub(string $r): string {}

                $r = $_GET["untrusted"];

                echo some_stub($r);',
                'error_message' => 'TaintedInput',
            ]
            /*
            // TODO: Stubs do not support this type of inference even with $this->message = $message.
            // Most uses of getMessage() would be with caught exceptions, so this is not representative of real code.
            'taintException' => [
                '<?php
                    $x = new Exception($_GET["x"]);
                    echo $x->getMessage();',
                'error_message' => 'TaintedInput',
            ],
            */
        ];
    }
}
