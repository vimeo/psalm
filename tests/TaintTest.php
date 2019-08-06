<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

class TaintTest extends TestCase
{
    /**
     * @return void
     */
    public function testTaintedInputFromReturnType()
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
                    /** @psalm-suppress TaintedInput */
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
    public function testTaintedInputDirectlySuppressedWithOtherUse()
    {
        $this->project_analyzer->trackTaintedInputs();

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    /** @psalm-suppress TaintedInput */
                    public function deleteUser(PDOWrapper $pdo) : void {
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
                     * @psalm-taint-sink $sql
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
                     * @psalm-taint-sink $sql
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
        $this->expectExceptionMessage('TaintedInput - somefile.php:8:32 - in path $_GET (somefile.php:4) -> a::getuserid (somefile.php:8) out path a::getappendeduserid (somefile.php:8) -> a::deleteuser#2 (somefile.php:13) -> pdo::exec#1 (somefile.php:17)');

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
        $this->expectExceptionMessage('TaintedInput - somefile.php:7:29 - in path  -> a::getappendeduserid#1 (somefile.php:11) -> a::getappendeduserid (somefile.php:7) out path a::deleteuser#3 (somefile.php:7) -> pdo::exec#1 (somefile.php:23)');

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
        $this->expectExceptionMessage('TaintedInput - somefile.php:24:47 - in path $_GET (somefile.php:28) -> c::foo#1 (somefile.php:23) out path agrandchild::loadfull#1 (somefile.php:24) -> a::loadpartial#1 (somefile.php:6) -> pdo::exec#1 (somefile.php:16)');

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
    public function testUntaintedInputViaStaticFunction()
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
}
