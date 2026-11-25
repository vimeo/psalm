<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\MethodIdentifier;

use function getcwd;

use const DIRECTORY_SEPARATOR;

final class MethodMutationTest extends TestCase
{
    public function testControllerMutation(): void
    {
        $this->addFile(
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

                    final public function setUser(): void
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
                    public function barBar(): Response {
                        $this->setUser();

                        if (rand(0, 1)) {
                            $this->title = "hello";
                        }

                        return new Response($this->user_viewdata);
                    }
                }',
        );

        new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $this->project_analyzer->getCodebase()->scanFiles();
        $method_context = new Context();
        $method_context->collect_initializations = true;
        $this->project_analyzer->getMethodMutations(
            new MethodIdentifier('FooController', 'barbar'),
            $method_context,
            'somefile.php',
            'somefile.php',
        );

        $this->assertSame('UserViewData', (string)$method_context->vars_in_scope['$this->user_viewdata']);
        $this->assertSame('string', (string)$method_context->vars_in_scope['$this->user_viewdata->name']);
        $this->assertTrue($method_context->vars_possibly_in_scope['$this->title']);
    }

    public function testNotSettingUser(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class User {}

                class FooController {
                    /** @var User|null */
                    public $user;

                    final public function doThingWithUser(): array
                    {
                        if (!$this->user) {
                            return [];
                        }

                        return ["hello"];
                    }

                    public function barBar(): void {
                        $this->user = rand(0, 1) ? new User() : null;

                        $this->doThingWithUser();
                    }
                }',
        );

        new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $this->project_analyzer->getCodebase()->scanFiles();
        $method_context = new Context();
        $method_context->collect_initializations = true;
        $this->project_analyzer->getMethodMutations(
            new MethodIdentifier('FooController', 'barbar'),
            $method_context,
            'somefile.php',
            'somefile.php',
        );

        $this->assertSame('User|null', (string)$method_context->vars_in_scope['$this->user']);
    }

    public function testParentControllerSet(): void
    {
        $this->addFile(
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
                }',
        );

        new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $this->project_analyzer->getCodebase()->scanFiles();
        $method_context = new Context();
        $method_context->collect_initializations = true;
        $this->project_analyzer->getMethodMutations(
            new MethodIdentifier('FooController', '__construct'),
            $method_context,
            'somefile.php',
            'somefile.php',
        );

        $this->assertSame('Foo', (string)$method_context->vars_in_scope['$this->foo']);
    }

    public function testTraitMethod(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                class Foo { }

                trait T {
                    private function setFoo(): void {
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
                }',
        );

        new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $this->project_analyzer->getCodebase()->scanFiles();
        $method_context = new Context();
        $method_context->collect_initializations = true;
        $this->project_analyzer->getMethodMutations(
            new MethodIdentifier('FooController', '__construct'),
            $method_context,
            'somefile.php',
            'somefile.php',
        );

        $this->assertSame('Foo', (string)$method_context->vars_in_scope['$this->foo']);
    }

    public function testMethodInheritedFromAnotherFileSeesConstantsOfRootFile(): void
    {
        $this->assertSame('5', $this->getCountSetBy(new MethodIdentifier('Child', 'setcount')));
    }

    public function testMethodOfAnotherFileSeesConstantsOfRootFile(): void
    {
        $this->assertSame('5', $this->getCountSetBy(new MethodIdentifier('Base', 'setcount')));
    }

    /**
     * The type of `$this->count` after a call of $method_id made in index.php, which includes the file defining the
     * constant the method sets it to: Base.php doesn't
     */
    private function getCountSetBy(MethodIdentifier $method_id): string
    {
        $root_file_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'index.php';
        $files = [
            $root_file_path => '<?php
                include "consts.php";
                include "Base.php";
                include "Child.php";',
            (string) getcwd() . DIRECTORY_SEPARATOR . 'consts.php' => '<?php
                define("FOO_COUNT", 5);',
            (string) getcwd() . DIRECTORY_SEPARATOR . 'Base.php' => '<?php
                class Base {
                    /** @var int|null */
                    public $count;

                    public function setCount(): void {
                        $this->count = FOO_COUNT;
                    }
                }',
            (string) getcwd() . DIRECTORY_SEPARATOR . 'Child.php' => '<?php
                class Child extends Base { }',
        ];

        $codebase = $this->project_analyzer->getCodebase();

        foreach ($files as $file_path => $contents) {
            $this->addFile($file_path, $contents);
            $codebase->scanner->addFilesToShallowScan([$file_path => $file_path]);
        }

        $codebase->addFilesToAnalyze([$root_file_path => $root_file_path]);
        $codebase->scanFiles();

        $method_context = new Context();
        $method_context->collect_initializations = true;
        $this->project_analyzer->getMethodMutations($method_id, $method_context, $root_file_path, 'index.php');

        return (string) $method_context->vars_in_scope['$this->count'];
    }
}
