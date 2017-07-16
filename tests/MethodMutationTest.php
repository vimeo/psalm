<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class MethodMutationTest extends TestCase
{
    /**
     * @return void
     */
    public function testControllerMutation()
    {
        $this->project_checker->registerFile(
            'somefile.php',
            '<?php
        class User {
            /** @var string */
            public $name;

            /**
             * @param string $name
             */
            protected function __construct($name) {
                $this->name = $name;
            }

            /** @return User|null */
            public static function loadUser(int $id) {
                if ($id === 3) {
                    $user = new User("bob");
                    return $user;
                }

                return null;
            }
        }

        class UserViewData {
            /** @var string|null */
            public $name;
        }

        class Response {
            public function __construct (UserViewData $viewdata) {}
        }

        class UnauthorizedException extends Exception { }

        class Controller {
            /** @var UserViewData */
            public $user_viewdata;

            /** @var string|null */
            public $title;

            public function __construct() {
                $this->user_viewdata = new UserViewData();
            }

            public function setUser() : void
            {
                $user_id = (int)$_GET["id"];

                if (!$user_id) {
                    throw new UnauthorizedException("No user id supplied");
                }

                $user = User::loadUser($user_id);

                if (!$user) {
                    throw new UnauthorizedException("User not found");
                }

                $this->user_viewdata->name = $user->name;
            }
        }

        class FooController extends Controller {
            public function barBar() : Response {
                $this->setUser();

                if (rand(0, 1)) {
                    $this->title = "hello";
                }

                return new Response($this->user_viewdata);
            }
        }'
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $this->project_checker->scanFiles();
        $this->project_checker->populateClassLikeStorages();
        $method_context = new Context();
        $this->project_checker->getMethodMutations('FooController::barBar', $method_context);

        $this->assertSame('UserViewData', (string)$method_context->vars_in_scope['$this->user_viewdata']);
        $this->assertSame('string', (string)$method_context->vars_in_scope['$this->user_viewdata->name']);
        $this->assertSame(true, $method_context->vars_possibly_in_scope['$this->title']);
    }

    /**
     * @return void
     */
    public function testParentControllerSet()
    {
        $this->project_checker->registerFile(
            'somefile.php',
            '<?php
        class Foo { }

        class Controller {
            /** @var Foo|null */
            public $foo;

            public function __construct() {
                $this->foo = new Foo();
            }
        }

        class FooController extends Controller {
            public function __construct() {
                parent::__construct();
            }
        }'
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $this->project_checker->scanFiles();
        $this->project_checker->populateClassLikeStorages();
        $method_context = new Context();
        $this->project_checker->getMethodMutations('FooController::__construct', $method_context);

        $this->assertSame('Foo', (string)$method_context->vars_in_scope['$this->foo']);
    }

    /**
     * @return void
     */
    public function testTraitMethod()
    {
        $this->project_checker->registerFile(
            'somefile.php',
            '<?php
        class Foo { }

        trait T {
            private function setFoo() : void {
                $this->foo = new Foo();
            }
        }

        class FooController {
            use T;

            /** @var Foo|null */
            public $foo;

            public function __construct() {
                $this->setFoo();
            }
        }'
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $this->project_checker->scanFiles();
        $this->project_checker->populateClassLikeStorages();
        $method_context = new Context();
        $this->project_checker->getMethodMutations('FooController::__construct', $method_context);

        $this->assertSame('Foo', (string)$method_context->vars_in_scope['$this->foo']);
    }
}
