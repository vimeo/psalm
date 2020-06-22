<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class TaintTest extends TestCase
{
    /**
     * @return void
     */
    public function testTaintedInputFromMethodReturnTypeSimple()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromFunctionReturnType()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput - somefile.php:6:22 - Detected tainted html in path: $_GET -> $_GET[\'name\'] (somefile.php:3:28) -> getname (somefile.php:6:22) -> call to echo (somefile.php:6:22) -> echo#1');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                function getName() : string {
                    return $_GET["name"] ?? "unknown";
                }

                echo getName();'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromExplicitTaintSource()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @psalm-taint-source input
                 */
                function getName() : string {
                    return "";
                }

                echo getName();'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromExplicitTaintSourceStaticMethod()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                class Request {
                    /**
                     * @psalm-taint-source input
                     */
                    public static function getName() : string {
                        return "";
                    }
                }


                echo Request::getName();'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromGetArray()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                function getName(array $data) : string {
                    return $data["name"] ?? "unknown";
                }

                $name = getName($_GET);

                echo $name;'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromReturnToInclude()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                $a = (string) $_GET["file"];
                $b = "hello" . $a;
                include str_replace("a", "b", $b);'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromReturnToEval()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                $a = $_GET["file"];
                eval("<?php" . $a);'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromReturnTypeToEcho()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputInCreatedArrayNotEchoed()
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                $name = $_GET["name"] ?? "unknown";
                $id = (int) $_GET["id"];

                $data = ["name" => $name, "id" => $id];

                echo "<h1>" . htmlentities($data["name"]) . "</h1>";
                echo "<p>" . $data["id"] . "</p>";'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputInCreatedArrayIsEchoed()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                $name = $_GET["name"] ?? "unknown";

                $data = ["name" => $name];

                echo "<h1>" . $data["name"] . "</h1>";'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputInAssignedArrayNotEchoed()
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                $name = $_GET["name"] ?? "unknown";
                $id = (int) $_GET["id"];

                $data = [];
                $data["name"] = $name;
                $data["id"] = $id;

                echo "<h1>" . htmlentities($data["name"]) . "</h1>";
                echo "<p>" . $data["id"] . "</p>";'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputInAssignedArrayIsEchoed()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                $name = $_GET["name"] ?? "unknown";

                $data = [];
                $data["name"] = $name;

                echo "<h1>" . $data["name"] . "</h1>";'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputDirectly()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public function deleteUser(PDO $pdo) : void {
                        $userId = (string) $_GET["user_id"];
                        $pdo->exec("delete from users where user_id = " . $userId);
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputDirectlySuppressed()
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public function deleteUser(PDO $pdo) : void {
                        /** @psalm-taint-escape sql */
                        $userId = (string) $_GET["user_id"];
                        $pdo->exec("delete from users where user_id = " . $userId);
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputDirectlySuppressedWithOtherUse()
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromReturnTypeWithBranch()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testSinkAnnotation()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromParam()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput - somefile.php:17:36 - Detected tainted sql in path: $_GET -> $_GET[\'user_id\'] (somefile.php:4:41) -> A::getUserId (somefile.php:3:51) -> concat (somefile.php:8:32) -> A::getAppendedUserId (somefile.php:7:59) -> $userId (somefile.php:12:25) -> call to A::deleteUser (somefile.php:13:49) -> A::deleteUser#2 (somefile.php:16:65) -> concat (somefile.php:17:36) -> call to PDO::exec (somefile.php:17:36) -> PDO::exec#1');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputToParam()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputToParamAfterAssignment()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputToParamButSafe()
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputToParamAlternatePath()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput - somefile.php:23:40 - Detected tainted sql in path: $_GET -> $_GET[\'user_id\'] (somefile.php:7:63) -> call to A::getAppendedUserId (somefile.php:7:54) -> A::getAppendedUserId#1 (somefile.php:11:62) -> concat (somefile.php:12:32) -> A::getAppendedUserId (somefile.php:11:37) -> call to A::deleteUser (somefile.php:7:29) -> A::deleteUser#3 (somefile.php:19:81) -> concat (somefile.php:23:40) -> call to PDO::exec (somefile.php:23:40) -> PDO::exec#1');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInParentLoader()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput - somefile.php:16:40 - Detected tainted sql in path: $_GET -> $_GET[\'user_id\'] (somefile.php:28:39) -> call to C::foo (somefile.php:28:30) -> C::foo#1 (somefile.php:23:48) -> call to AGrandChild::loadFull (somefile.php:24:47) -> AGrandChild::loadFull#1 (somefile.php:5:60) -> A::loadFull#1 (somefile.php:24:47) -> call to A::loadPartial (somefile.php:6:45) -> A::loadPartial#1 (somefile.php:3:72) -> AChild::loadPartial#1 (somefile.php:6:45) -> concat (somefile.php:16:40) -> call to PDO::exec (somefile.php:16:40) -> PDO::exec#1');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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

                (new C)->foo((string) $_GET["user_id"]);'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testValidatedInputFromParam()
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testUntaintedInput()
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromProperty()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testSpecializedCoreFunctionCall()
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                $a = (string) $_GET["user_id"];

                echo print_r([], true);

                $b = print_r($a, true);'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputFromPropertyViaMixin()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputViaStaticFunction()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testTaintedInputViaPureStaticFunction()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testUntaintedInputViaStaticFunctionWithSafePath()
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testUntaintedInputViaStaticFunctionWithoutSafePath()
    {
        $this->project_analyzer->trackTaintedInputs();
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintedInputFromMagicProperty() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintOverMixed() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @psalm-suppress MixedAssignment
                 * @psalm-suppress MixedArgument
                 */
                function foo() : void {
                    $a = $_GET["bad"];
                    echo $a;
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintStrConversion() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                function foo() : void {
                    $a = strtoupper(strtolower((string) $_GET["bad"]));
                    echo $a;
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintHtmlEntities() : void
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                function foo() : void {
                    $a = htmlentities((string) $_GET["bad"]);
                    echo $a;
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintIntoExec() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                function foo() : void {
                    $a = (string) $_GET["bad"];
                    exec($a);
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintIntoExecMultipleConcat() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                function foo() : void {
                    $a = "9" . "a" . "b" . "c" . ((string) $_GET["bad"]) . "d" . "e" . "f";
                    exec($a);
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintIntoNestedArrayUnnestedSeparately() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                function foo() : void {
                    $a = [[(string) $_GET["bad"]]];
                    exec($a[0][0]);
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintIntoArrayAndThenOutAgain() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                class C {
                    public static function foo() : array {
                        $a = (string) $_GET["bad"];
                        return [$a];
                    }

                    public static function bar() {
                        exec(self::foo()[0]);
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintAppendedToArray() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintOnSubstrCall() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintOnStrReplaceCallSimple() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintOnStrReplaceCallRemovedInFunction() : void
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintOnPregReplaceCallRemovedInFunction() : void
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintOnStrReplaceCallRemovedInline() : void
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintOnPregReplaceCall() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoTaintsOnSimilarPureCall() : void
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testIndirectGetAssignment() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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

                echo (new InputFilter("hello"))->getArg("get", "string");'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintPropertyPassingObject() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                UserUpdater::doDelete(new PDO(), $userObj);'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintPropertyPassingObjectSettingValueLater() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
                UserUpdater::doDelete(new PDO(), $userObj);'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintPropertyPassingObjectWithDifferentValue() : void
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTaintPropertyWithoutPassingObject() : void
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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

                $userObj = new User((string) $_GET["user_id"]);'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testImplodeExplode() : void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('TaintedInput');

        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                $a = $_GET["name"];
                $b = explode(" ", $a);
                $c = implode(" ", $b);
                echo $c;'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testSpecializeStaticMethod() : void
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
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
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
