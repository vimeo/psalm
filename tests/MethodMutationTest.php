<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Config;
use Psalm\Context;

class MethodMutationTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        self::$config = new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
        $this->project_checker->setConfig(self::$config);
    }

    /**
     * @return void
     */
    public function testControllerMutation()
    {
        $this->project_checker->registerFile(
            getcwd() . '/somefile.php',
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

        $file_checker = new FileChecker(getcwd() . '/somefile.php', $this->project_checker);
        $context = new Context();
        $file_checker->visit($context);
        $file_checker->analyze(false, true);
        $method_context = new Context();
        $this->project_checker->getMethodMutations('FooController::barBar', $method_context);

        $this->assertEquals('UserViewData', (string)$method_context->vars_in_scope['$this->user_viewdata']);
        $this->assertEquals('string', (string)$method_context->vars_in_scope['$this->user_viewdata->name']);
        $this->assertEquals(true, (string)$method_context->vars_possibly_in_scope['$this->title']);
    }
}
