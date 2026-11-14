<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;
use function sort;

use const DIRECTORY_SEPARATOR;

final class PropertyTypeInvarianceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'importedTypeAliasIsTheTypeItStandsFor' => [
                'code' => '<?php
                    /**
                     * @psalm-type FatherType = array<string, string>
                     */
                    abstract class Father {
                        /** @var FatherType */
                        public array $a = [];
                    }

                    /**
                     * @psalm-import-type FatherType from Father
                     */
                    class Child extends Father {
                        /** @var FatherType */
                        public array $a = ["key" => "value"];
                    }',
            ],
            'validcode' => [
                'code' => '<?php
                    class ParentClass
                    {
                        /** @var null|string */
                        protected $mightExist;

                        protected ?string $mightExistNative = null;

                        /** @var string */
                        protected $doesExist = "";

                        protected string $doesExistNative = "";
                    }

                    class ChildClass extends ParentClass
                    {
                        /** @var null|string */
                        protected $mightExist = "";

                        protected ?string $mightExistNative = null;

                        /** @var string */
                        protected $doesExist = "";

                        protected string $doesExistNative = "";
                    }',
            ],
            'allowTemplatedInvariance' => [
                'code' => '<?php
                    /**
                     * @template T as string|null
                     */
                    abstract class A {
                        /** @var T */
                        public $foo;
                    }

                    /**
                     * @extends A<string>
                     */
                    class AChild extends A {
                        /** @var string */
                        public $foo = "foo";
                    }',
            ],
            'allowTemplatedInvarianceWithListTemplate' => [
                'code' => '<?php
                    abstract class Item {}
                    class Foo extends Item {}

                    /** @template TItem of Item */
                    abstract class ItemCollection
                    {
                        /** @var list<TItem> */
                        protected $items = [];
                    }

                    /** @extends ItemCollection<Foo> */
                    class FooCollection extends ItemCollection
                    {
                        /** @var list<Foo> */
                        protected $items = [];
                    }',
            ],
            'allowTemplatedInvarianceWithClassTemplate' => [
                'code' => '<?php
                    abstract class Item {}
                    class Foo extends Item {}

                    /** @template T */
                    class Collection {}

                    /** @template TItem of Item */
                    abstract class ItemCollection
                    {
                        /** @var Collection<TItem>|null */
                        protected $items;
                    }

                    /** @extends ItemCollection<Foo> */
                    class FooCollection extends ItemCollection
                    {
                        /** @var Collection<Foo>|null */
                        protected $items;
                    }',
            ],
            'allowTemplatedInvarianceWithClassStringTemplate' => [
                'code' => '<?php
                    abstract class Item {}
                    class Foo extends Item {}

                    /** @template T of Item */
                    abstract class ItemType
                    {
                        /** @var class-string<T>|null */
                        protected $type;
                    }

                    /** @extends ItemType<Foo> */
                    class FooTypes extends ItemType
                    {
                        /** @var class-string<Foo>|null */
                        protected $type;
                    }',
            ],
            'templatedInvarianceGrandchild' => [
                'code' => '<?php
                    abstract class Item {}
                    class Foo extends Item {}
                    class Bar extends Foo {}

                    /** @template T of Item */
                    abstract class ItemCollection
                    {
                        /** @var list<T> */
                        protected $items = [];
                    }

                    /**
                     * @template T of Foo
                     * @extends ItemCollection<T>
                     */
                    class FooCollection extends ItemCollection
                    {
                        /** @var list<T> */
                        protected $items = [];
                    }

                    /** @extends FooCollection<Bar> */
                    class BarCollection extends FooCollection
                    {
                        /** @var list<Bar> */
                        protected $items = [];
                    }',
            ],
            'allowTemplateCovariant' => [
                'code' => '<?php
                    class Foo {}
                    class Bar extends Foo {}
                    class Baz extends Bar {}

                    /** @template-covariant T */
                    class Pair
                    {
                        /** @var T|null */
                        public $a;

                        /** @var T|null */
                        public $b;
                    }

                    /** @extends Pair<Foo> */
                    class FooPair extends Pair
                    {
                        /** @var Bar|null */
                        public $a;

                        /** @var Baz|null */
                        public $b;
                    }',
            ],
            'allowTemplateCovariantManyTemplates' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends B {}

                    /**
                     * @template Ta
                     * @template Tb
                     * @template-covariant Tc
                     * @template Td
                     */
                    class Foo {
                        /** @var Ta|null */
                        public $a;

                        /** @var Tb|null */
                        public $b;

                        /** @var Tc|null */
                        public $c;

                        /** @var Td|null */
                        public $d;
                    }

                    /**
                     * @template Ta
                     * @template Tb
                     * @template-covariant Tc
                     * @template Td
                     * @extends Foo<Ta, Tb, Tc, Td>
                     */
                    class Bar extends Foo {}

                    /**
                     * @template Ta
                     * @template Tb
                     * @template-covariant Tc
                     * @template Td
                     * @extends Bar<A, B, A, C>
                     */
                    class Baz extends Bar {
                        /** @var A|null */
                        public $a;

                        /** @var B|null */
                        public $b;

                        /** @var C|null */
                        public $c;

                        /** @var C|null */
                        public $d;
                    }',
            ],
            'allowReadonly' => [
                'code' => '<?php
                    class ParentClass
                    {
                        /**
                         * @readonly
                         * @var null|string
                         */
                        protected $mightExist;
                    }

                    class ChildClass extends ParentClass
                    {
                        /** @var string */
                        protected $mightExist = "";
                    }',
            ],
            'redeclaredPropertyWithoutDocblockTypeHasTheInheritedType' => [
                'code' => '<?php
                    abstract class Model {}
                    final class Post extends Model {}

                    abstract class BaseResource {
                        /** @var class-string<Model> */
                        public static string $model = Model::class;

                        /** @var list<non-empty-string> */
                        public array $columns = [];
                    }

                    final class PostResource extends BaseResource {
                        public static string $model = Post::class;

                        /** @inheritDoc */
                        public array $columns = ["id"];

                        public function f(): void {
                            $columns = $this->columns;
                            /** @psalm-check-type-exact $columns = list<non-empty-string> */
                            $self = self::$model;
                            /** @psalm-check-type-exact $self = class-string<Model> */
                            $static = static::$model;
                            /** @psalm-check-type-exact $static = class-string<Model> */
                        }
                    }

                    $model = PostResource::$model;
                    $columns = (new PostResource())->columns;',
                'assertions' => [
                    '$model===' => 'class-string<Model>',
                    '$columns===' => 'list<non-empty-string>',
                ],
            ],
            'untypedRedeclaredPropertyWithoutDocblockTypeHasTheInheritedType' => [
                'code' => '<?php
                    abstract class Model {}
                    final class Post extends Model {}

                    abstract class BaseResource {
                        /** @var class-string<Model> */
                        public static $model = Model::class;

                        /** @var non-empty-string */
                        public $name = "a";
                    }

                    final class PostResource extends BaseResource {
                        public static $model = Post::class;

                        public $name = "b";

                        public function f(): void {
                            $name = $this->name;
                            /** @psalm-check-type-exact $name = non-empty-string */
                            $self = self::$model;
                            /** @psalm-check-type-exact $self = class-string<Model> */
                            $static = static::$model;
                            /** @psalm-check-type-exact $static = class-string<Model> */
                        }
                    }

                    $model = PostResource::$model;
                    $name = (new PostResource())->name;',
                'assertions' => [
                    '$model===' => 'class-string<Model>',
                    '$name===' => 'non-empty-string',
                ],
            ],
            'redeclaredPropertyWithoutDocblockTypeHasTheNearestInheritedType' => [
                'code' => '<?php
                    class GrandParentClass {
                        /** @var non-empty-string */
                        public string $typed = "a";

                        /**
                         * @readonly
                         * @var string
                         */
                        public $untyped = "a";
                    }

                    class ParentClass extends GrandParentClass {
                        public string $typed = "b";

                        /** @var non-empty-string */
                        public $untyped = "b";
                    }

                    final class ChildClass extends ParentClass {
                        public string $typed = "c";

                        public $untyped = "c";
                    }

                    class OtherParentClass extends GrandParentClass {}

                    final class OtherChildClass extends OtherParentClass {
                        public string $typed = "c";
                    }

                    $typed = (new ChildClass())->typed;
                    $untyped = (new ChildClass())->untyped;
                    $other_typed = (new OtherChildClass())->typed;',
                'assertions' => [
                    '$typed===' => 'non-empty-string',
                    '$untyped===' => 'non-empty-string',
                    '$other_typed===' => 'non-empty-string',
                ],
            ],
            'inheritedPropertyTypeIsLocalizedToTheChild' => [
                'code' => '<?php
                    abstract class Model {}
                    final class Post extends Model {}

                    /** @template T of Model */
                    abstract class Repository {
                        /** @var class-string<T>|null */
                        public ?string $class = null;

                        /** @var list<T> */
                        public array $items = [];
                    }

                    /** @extends Repository<Post> */
                    final class PostRepository extends Repository {
                        public ?string $class = Post::class;

                        public array $items = [];
                    }

                    /**
                     * @template U of Model
                     * @extends Repository<U>
                     */
                    abstract class CachedRepository extends Repository {
                        public array $items = [];

                        /** @return list<U> */
                        public function all(): array {
                            return $this->items;
                        }
                    }

                    /** @extends CachedRepository<Post> */
                    final class CachedPostRepository extends CachedRepository {
                        public array $items = [];
                    }

                    /** @param CachedRepository<Post> $repository */
                    function f(CachedRepository $repository): void {
                        $items = $repository->items;
                        /** @psalm-check-type-exact $items = list<Post> */
                    }

                    $class = (new PostRepository())->class;
                    $items = (new PostRepository())->items;
                    $cached_items = (new CachedPostRepository())->items;',
                'assertions' => [
                    '$class===' => 'class-string<Post>|null',
                    '$items===' => 'list<Post>',
                    '$cached_items===' => 'list<Post>',
                ],
            ],
            'inheritedPropertyTypeHasTheSelfOfTheParent' => [
                'code' => '<?php
                    class ParentClass {
                        const A = 1;
                        const B = 2;

                        /** @var self|null */
                        public $next;

                        /** @var self::A|self::B */
                        public $constant = self::A;

                        /** @var list<static> */
                        public array $all = [];
                    }

                    final class ChildClass extends ParentClass {
                        public $next;

                        public $constant = 1;

                        public array $all = [];
                    }

                    $next = (new ChildClass())->next;
                    $constant = (new ChildClass())->constant;
                    $all = (new ChildClass())->all;',
                'assertions' => [
                    '$next===' => 'ParentClass|null',
                    '$constant===' => '1|2',
                    '$all===' => 'list<ChildClass&static>',
                ],
            ],
            'nullableRedeclaredPropertyWithoutDocblockTypeHasTheInheritedType' => [
                'code' => '<?php
                    abstract class Model {}
                    final class Post extends Model {}

                    class ParentClass {
                        /** @var class-string<Model>|null */
                        public ?string $class = null;
                    }

                    final class ChildClass extends ParentClass {
                        public ?string $class = Post::class;
                    }

                    $class = (new ChildClass())->class;',
                'assertions' => [
                    '$class===' => 'class-string<Model>|null',
                ],
            ],
            'promotedPropertyWithoutDocblockTypeHasTheInheritedType' => [
                'code' => '<?php
                    class ParentClass {
                        /** @var non-empty-string */
                        public string $name = "a";
                    }

                    final class ChildClass extends ParentClass {
                        public function __construct(public string $name) {
                            /** @psalm-check-type-exact $name = non-empty-string */
                        }
                    }

                    $name = (new ChildClass("b"))->name;',
                'assertions' => [
                    '$name===' => 'non-empty-string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyOfAParentTraitRedeclaredWithoutDocblockType' => [
                'code' => '<?php
                    trait T {
                        /** @var non-empty-string */
                        public $name = "a";
                    }

                    class ParentClass {
                        use T;
                    }

                    final class ChildClass extends ParentClass {
                        public $name = "b";
                    }

                    $name = (new ChildClass())->name;',
                'assertions' => [
                    '$name===' => 'non-empty-string',
                ],
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
            'importedTypeAliasOfAnotherType' => [
                'code' => '<?php
                    /**
                     * @psalm-type FatherType = array<string, string>
                     * @psalm-type OtherType = array<string, int>
                     */
                    abstract class Father {
                        /** @var FatherType */
                        public array $a = [];
                    }

                    /**
                     * @psalm-import-type OtherType from Father
                     */
                    class Child extends Father {
                        /** @var OtherType */
                        public array $a = [];
                    }',
                'error_message' => 'NonInvariantDocblockPropertyType',
            ],
            'variantDocblockProperties' => [
                'code' => '<?php
                    class ParentClass
                    {
                        /** @var null|string */
                        protected $mightExist;
                    }

                    class ChildClass extends ParentClass
                    {
                        /** @var string */
                        protected $mightExist = "";
                    }',
                'error_message' => 'NonInvariantDocblockPropertyType',
            ],
            'variantProperties' => [
                'code' => '<?php
                    class ParentClass
                    {
                        protected ?string $mightExist = null;
                    }

                    class ChildClass extends ParentClass
                    {
                        protected string $mightExist = "";
                    }',
                'error_message' => 'NonInvariantPropertyType',
            ],
            'variantTemplatedProperties' => [
                'code' => '<?php
                    /**
                     * @template T as string|null
                     */
                    abstract class A {
                        /** @var T */
                        public $foo;
                    }

                    /**
                     * @extends A<string>
                     */
                    class AChild extends A {
                        /** @var int */
                        public $foo = 0;
                    }',
                'error_message' => 'NonInvariantDocblockPropertyType',
            ],
            'variantTemplatedGrandchild' => [
                'code' => '<?php
                    abstract class Item {}
                    class Foo extends Item {}
                    class Bar extends Foo {}

                    /** @template T of Item */
                    abstract class ItemCollection
                    {
                        /** @var list<T> */
                        protected $items = [];
                    }

                    /**
                     * @template T of Foo
                     * @extends ItemCollection<T>
                     */
                    class FooCollection extends ItemCollection
                    {
                        /** @var list<T> */
                        protected $items = [];
                    }

                    /** @extends FooCollection<Bar> */
                    class BarCollection extends FooCollection
                    {
                        /** @var list<Item> */ // Should be list<Bar>
                        protected $items = [];
                    }',
                'error_message' => 'NonInvariantDocblockPropertyType',
            ],
            'variantPropertiesWithTemplateNotSpecified' => [
                'code' => '<?php
                    class Foo {}

                    /** @template T */
                    class Pair
                    {
                        /** @var T|null */
                        protected $a;

                        /** @var T|null */
                        protected $b;
                    }

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class FooPair extends Pair
                    {
                        /** @var Foo|null */ // Template defaults to mixed, this is invariant
                        protected $a;

                        /** @var Foo|null */
                        protected $b;
                    }',
                'error_message' => 'NonInvariantDocblockPropertyType',
            ],
            'redeclaredStubPropertyWithoutDocblockTypeHasTheInheritedType' => [
                'code' => '<?php
                    final class MyException extends Exception {
                        protected $message = 42;
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'untypedRedeclaredPropertyWithoutDefaultHasTheInheritedType' => [
                'code' => '<?php
                    class ParentClass {
                        /** @var non-empty-string */
                        public $name = "a";
                    }

                    final class ChildClass extends ParentClass {
                        public $name;
                    }',
                'error_message' => 'MissingConstructor',
            ],
            'promotedPropertyWithoutDocblockTypeChecksTheArgument' => [
                'code' => '<?php
                    class ParentClass {
                        /** @var non-empty-string */
                        public string $name = "a";
                    }

                    final class ChildClass extends ParentClass {
                        public function __construct(public string $name) {}
                    }

                    new ChildClass("");',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:36',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'promotedPropertyWithOwnParamTypeIsInvariant' => [
                'code' => '<?php
                    class ParentClass {
                        /** @var non-empty-string */
                        public string $name = "a";
                    }

                    final class ChildClass extends ParentClass {
                        /** @param non-empty-lowercase-string $name */
                        public function __construct(public string $name) {}
                    }',
                'error_message' => 'NonInvariantDocblockPropertyType',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    public function testInheritedPropertyTypeIsCheckedOnDefaultsAndWrites(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class ParentClass {
                    /** @var non-empty-string */
                    public string $typed = "a";

                    /** @var non-empty-string */
                    public static string $static = "a";

                    /** @var non-empty-string */
                    public $untyped = "a";

                    /** @var non-empty-string */
                    public $promoted = "a";
                }

                final class ChildClass extends ParentClass {
                    public string $typed = "";

                    public static string $static = "";

                    public $untyped = "";

                    public function __construct(public $promoted = "") {}

                    public function f(): void {
                        $this->typed = "";
                    }
                }

                (new ChildClass())->untyped = "";
                ChildClass::$static = "";',
        );

        $this->analyzeFile($file_path, new Context());

        $issues = array_map(
            static fn(IssueData $issue): string => $issue->line_from . ':' . $issue->column_from . ' ' . $issue->type,
            IssueBuffer::getIssuesDataForFile($file_path),
        );
        sort($issues);

        self::assertSame(
            [
                '17:44 InvalidPropertyAssignmentValue',
                '19:52 InvalidPropertyAssignmentValue',
                '21:39 InvalidPropertyAssignmentValue',
                '23:56 InvalidParamDefault',
                '26:40 InvalidPropertyAssignmentValue',
                '30:47 InvalidPropertyAssignmentValue',
                '31:39 InvalidPropertyAssignmentValue',
            ],
            $issues,
        );
    }

    public function testInheritedPropertyTypeIsPopulatedAgain(): void
    {
        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class ParentClass {
                    /** @var non-empty-string */
                    public $inferred = "a";

                    /** @var non-empty-string */
                    public string $promoted = "a";
                }

                final class ChildClass extends ParentClass {
                    public $inferred;

                    public function __construct(string $inferred, public string $promoted) {
                        $this->inferred = $inferred;
                    }
                }',
        );

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->addFilesToAnalyze([$file_path => $file_path]);
        $codebase->scanFiles();

        $parent_storage = $codebase->classlike_storage_provider->get('parentclass');
        $child_storage = $codebase->classlike_storage_provider->get('childclass');

        $populate_child_again = static function () use ($codebase, $child_storage): array {
            $child_storage->populated = false;
            $codebase->classlike_storage_provider->makeNew('childclass');
            $codebase->populator->populateCodebase();

            return [
                $child_storage->properties['inferred']->type?->getId(),
                $child_storage->properties['promoted']->type?->getId(),
                $child_storage->methods['__construct']->params[1]->type?->getId(),
            ];
        };

        self::assertSame(['non-empty-string', 'non-empty-string', 'non-empty-string'], $populate_child_again());

        // the parent's docblock types are gone: the child has its own types back
        foreach ($parent_storage->properties as $property_storage) {
            $property_storage->type = $property_storage->signature_type;
            $property_storage->has_docblock_type = false;
        }

        self::assertSame(['string', 'string', 'string'], $populate_child_again());
    }
}
