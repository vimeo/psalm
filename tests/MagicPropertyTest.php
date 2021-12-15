<?php

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class MagicPropertyTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testPhpDocPropertyWithoutGet(): void
    {
        Config::getInstance()->use_phpdoc_property_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @property string $hello
                 */
                class Child {}

                $child = new Child();

                $a = $child->hello;'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'propertyDocblock' => [
                '<?php
                    namespace Bar;

                    /**
                     * @property string $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = "hello";',
            ],
            'propertyOfTypeClassDocblock' => [
                '<?php
                    namespace Bar;

                    class PropertyType {}

                    /**
                     * @property PropertyType $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = new PropertyType();',
            ],
            'propertySealedDocblockDefinedPropertyFetch' => [
                '<?php
                    namespace Bar;
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    echo $a->foo;',
            ],
            /**
             * With a magic setter and no annotations specifying properties or types, we can
             * set anything we want on any variable name. The magic setter is trusted to figure
             * it out.
             */
            'magicSetterUndefinedPropertyNoAnnotation' => [
                '<?php
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function goodSet(): void {
                            $this->__set("foo", new stdClass());
                        }
                    }',
            ],
            /**
             * With a magic getter and no annotations specifying properties or types, we can
             * get anything we want with any variable name. The magic getter is trusted to figure
             * it out.
             */
            'magicGetterUndefinedPropertyNoAnnotation' => [
                '<?php
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function goodGet(): void {
                            echo $this->__get("foo");
                        }
                    }',
            ],
            /**
             * The property $foo is defined as a string with the `@property` annotation. We
             * use the magic setter to set it to a string, so everything is cool.
             */
            'magicSetterValidAssignmentType' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function goodSet(): void {
                            $this->__set("foo", "value");
                        }
                    }',
            ],
            'propertyDocblockAssignmentToMixed' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    /** @param mixed $b */
                    function foo($b) : void {
                        $a = new A();
                        $a->__set("foo", $b);
                    }',
                'assertions' => [],
                'error_level' => ['MixedAssignment', 'MixedPropertyTypeCoercion'],
            ],
            'namedPropertyByVariable' => [
                '<?php
                    class A {
                        /** @var string|null */
                        public $foo;

                        public function __get(string $var_name) : ?string {
                            if ($var_name === "foo") {
                                return $this->$var_name;
                            }

                            return null;
                        }
                    }',
            ],
            'getPropertyExplicitCall' => [
                '<?php
                    class A {
                        public function __get(string $name) {}

                        /**
                         * @param mixed $value
                         */
                        public function __set(string $name, $value) {}
                    }

                    /**
                     * @property string $test
                     */
                    class B extends A {
                        public function test(): string {
                            return $this->__get("test");
                        }
                    }',
            ],
            'inheritedGetPropertyExplicitCall' => [
                '<?php
                    /**
                     * @property string $test
                     */
                    class A {
                        public function __get(string $name) {}

                        /**
                         * @param mixed $value
                         */
                        public function __set(string $name, $value) {}
                    }

                    class B extends A {
                        public function test(): string {
                            return $this->__get("test");
                        }
                    }',
            ],
            'undefinedThisPropertyFetchWithMagic' => [
                '<?php
                    /**
                     * @property-read string $name
                     * @property string $otherName
                     */
                    class A {
                        public function __get(string $name): void {
                        }

                        public function goodGet(): void {
                            echo $this->name;
                        }
                        public function goodGet2(): void {
                            echo $this->otherName;
                        }
                    }
                    $a = new A();
                    echo $a->name;
                    echo $a->otherName;',
            ],
            'psalmUndefinedThisPropertyFetchWithMagic' => [
                '<?php
                    /**
                     * @psalm-property-read string $name
                     * @property string $otherName
                     */
                    class A {
                        public function __get(string $name): void {
                        }

                        public function goodGet(): void {
                            echo $this->name;
                        }
                        public function goodGet2(): void {
                            echo $this->otherName;
                        }
                    }
                    $a = new A();
                    echo $a->name;
                    echo $a->otherName;',
            ],
            'directFetchForMagicProperty' => [
                '<?php
                    /**
                     * @property string $test
                     */
                    class C {
                        public function __get(string $name)
                        {
                        }

                        /**
                         * @param mixed $value
                         */
                        public function __set(string $name, $value)
                        {
                        }

                        public function test(): string
                        {
                            return $this->test;
                        }
                    }',
            ],
            'magicPropertyFetchOnProtected' => [
                '<?php
                    class C {
                        /** @var string */
                        protected $foo = "foo";

                        public function __get(string $name) {}

                        /**
                         * @param mixed $value
                         */
                        public function __set(string $name, $value)
                        {
                        }
                    }

                    $c = new C();
                    $c->foo = "bar";
                    echo $c->foo;',
                'assertions' => [],
                'error_level' => ['MixedArgument'],
            ],
            'dontAssumeNonNullAfterPossibleMagicFetch' => [
                '<?php
                    class C {
                        public function __get(string $name) : string {
                            return "hello";
                        }
                    }

                    function foo(?C $c) : void {
                        echo $c->foo;

                        if ($c) {}
                    }',
                'assertions' => [],
                'error_level' => ['PossiblyNullPropertyFetch'],
            ],
            'accessInMagicGet' => [
                '<?php
                    class X {
                        public function __get(string $name) : string {
                            switch ($name) {
                                case "a":
                                    return $this->other;
                                case "other":
                                    return "foo";
                            }
                            return "default";
                        }
                    }',
                'assertions' => [],
                'error_level' => ['MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'overrideInheritedProperty' => [
                '<?php
                    interface ServiceInterface {}

                    class ConcreteService implements ServiceInterface {
                        public function getById(int $i) : void {}
                    }

                    class Foo
                    {
                        /** @var ServiceInterface */
                        protected $service;

                        public function __construct(ServiceInterface $service)
                        {
                            $this->service = $service;
                        }
                    }

                    /** @property ConcreteService $service */
                    class FooBar extends Foo
                    {
                        public function __construct(ConcreteService $concreteService)
                        {
                            parent::__construct($concreteService);
                        }

                        public function doSomething(): void
                        {
                            $this->service->getById(123);
                        }
                    }',
            ],
            'magicInterfacePropertyRead' => [
                '<?php
                    /**
                     * @property-read string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : string {
                        return $o->foo;
                    }',
            ],
            'phanMagicInterfacePropertyRead' => [
                '<?php
                    /**
                     * @psalm-property-read string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : string {
                        return $o->foo;
                    }',
            ],
            'magicInterfacePropertyWrite' => [
                '<?php
                    /**
                     * @property-write string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : void {
                        $o->foo = "hello";
                    }',
            ],
            'psalmMagicInterfacePropertyWrite' => [
                '<?php
                    /**
                     * @psalm-property-write string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : void {
                        $o->foo = "hello";
                    }',
            ],
            'psalmPropertyDocblock' => [
                '<?php
                    namespace Bar;

                    /**
                     * @psalm-property string $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = "hello";',
            ],
            'overridePropertyAnnotations' => [
                '<?php
                    namespace Bar;

                    /**
                     * @property int $foo
                     * @psalm-property string $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = "hello";',
            ],
            'overrideWithReadWritePropertyAnnotations' => [
                '<?php
                    namespace Bar;

                    /**
                     * @psalm-property int $foo
                     * @property-read string $foo
                     * @property-write array $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }

                        public function takesString(string $s): void {}
                    }

                    $a = new A();
                    $a->foo = [];

                    $a = new A();
                    $a->takesString($a->foo);',
            ],
            'removeAssertionsAfterCall' => [
                '<?php
                    class C {
                        /**
                         * @return mixed
                         */
                        public function __get(string $name) {
                            return rand();
                        }

                        /**
                         * @param mixed $value
                         * @return void
                         */
                        public function __set(string $name, $value) {}

                        public function main() : void {
                            if (!isset($this->a)) {
                                $this->a = ["something"];

                                /**
                                 * @psalm-suppress MixedArrayAccess
                                 * @psalm-suppress MixedArgument
                                 */
                                echo $this->a[0];
                            }
                        }
                    }'
            ],
            'magicPropertyDefinedOnTrait' => [
                '<?php
                    class UserRecord
                    {
                        use UserFields;

                        private array $props = [];

                        public function __get(string $field)
                        {
                            return $this->props[$field];
                        }

                        /**
                         * @param mixed $value
                         */
                        public function __set(string $field, $value) : void
                        {
                            $this->props[$field] = $value;
                        }
                    }

                    /**
                     * @property mixed $email
                     * @property mixed $password
                     * @property mixed $last_login_at
                     */
                    trait UserFields {}

                    $record = new UserRecord();
                    $record->email;
                    $record->password;
                    $record->last_login_at = new DateTimeImmutable("now");'
            ],
            'reconcileMagicProperties' => [
                '<?php
                    /**
                     * @property string|null $a A
                     * @property string|null $b B
                     */
                    class Foo
                    {
                        private array $props = [];

                        public function __construct() {
                            $this->props["a"] = "hello";
                            $this->props["b"] = "goodbye";
                        }

                        /**
                         * @psalm-mutation-free
                         */
                        public function __get(string $prop){
                            return $this->props[$prop] ?? null;
                        }

                        /** @param mixed $b */
                        public function __set(string $a, $b){
                            $this->props[$a] = $b;
                        }

                        public function bar(): string {
                            if (is_null($this->a) || is_null($this->b)) {

                            } else {
                                return $this->b;
                            }

                            return "hello";
                        }
                    }'
            ],
            'propertyReadIsExpanded' => [
                '<?php
                    /** @property self::TYPE_* $type */
                    class A {
                        public const TYPE_A = 1;
                        public const TYPE_B = 2;

                        public function __get(string $_prop) {}
                        /** @param mixed $_value */
                        public function __set(string $_prop, $_value) {}
                    }
                    $a = (new A)->type;
                ',
                'assertions' => [
                    '$a===' => '1|2',
                ],
            ],
            'propertyWriteIsExpanded' => [
                '<?php
                    /** @property self::TYPE_* $type */
                    class A {
                        public const TYPE_A = 1;
                        public const TYPE_B = 2;

                        public function __get(string $_prop) {}
                        /** @param mixed $_value */
                        public function __set(string $_prop, $_value) {}
                    }
                    $a = (new A);
                    $a->type = A::TYPE_B;
                ',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'annotationWithoutGetter' => [
                '<?php
                    /**
                     * @property bool $is_protected
                     */
                    final class Page {
                        public function isProtected(): bool
                        {
                            return $this->is_protected;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'propertyDocblockInvalidAssignment' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertyInvalidClassAssignment' => [
                '<?php
                    namespace Bar;

                    class PropertyType {}
                    class SomeOtherPropertyType {}

                    /**
                     * @property PropertyType $foo
                     */
                    class A {
                        /** @param string $name */
                        public function __get($name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /**
                         * @param string $name
                         * @param mixed $value
                         */
                        public function __set($name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = new SomeOtherPropertyType();',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:29:31 - $a->foo with declared type'
                    . ' \'Bar\PropertyType\' cannot',
            ],
            'propertyWriteDocblockInvalidAssignment' => [
                '<?php
                    /**
                     * @property-write string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'psalmPropertyWriteDocblockInvalidAssignment' => [
                '<?php
                    /**
                     * @psalm-property-write string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertySealedDocblockUndefinedPropertyAssignment' => [
                '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->bar = 5;',
                'error_message' => 'UndefinedMagicPropertyAssignment',
            ],
            'propertySealedDocblockDefinedPropertyAssignment' => [
                '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    $a->foo = 5;',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertyReadInvalidFetch' => [
                '<?php
                    /**
                     * @property-read string $foo
                     */
                    class A {
                        /** @return mixed */
                        public function __get(string $name) {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }
                    }

                    $a = new A();
                    echo count($a->foo);',
                'error_message' => 'InvalidArgument',
            ],
            'psalmPropertyReadInvalidFetch' => [
                '<?php
                    /**
                     * @psalm-property-read string $foo
                     */
                    class A {
                        /** @return mixed */
                        public function __get(string $name) {
                            if ($name === "foo") {
                                return "hello";
                            }
                        }
                    }

                    $a = new A();
                    echo count($a->foo);',
                'error_message' => 'InvalidArgument',
            ],
            'propertySealedDocblockUndefinedPropertyFetch' => [
                '<?php
                    /**
                     * @property string $foo
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    $a = new A();
                    echo $a->bar;',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            /**
             * The property $foo is not defined on the object, but accessed with the magic setter.
             * This is an error because `@psalm-seal-properties` is specified on the class block.
             */
            'magicSetterUndefinedProperty' => [
                '<?php
                    /**
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function badSet(): void {
                            $this->__set("foo", "value");
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyAssignment',
            ],
            /**
             * The property $foo is not defined on the object, but accessed with the magic getter.
             * This is an error because `@psalm-seal-properties` is specified on the class block.
             */
            'magicGetterUndefinedProperty' => [
                '<?php
                    /**
                     * @psalm-seal-properties
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function badGet(): void {
                            $this->__get("foo");
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            /**
             * The property $foo is defined as a string with the `@property` annotation, but
             * the magic setter is used to set it to an object.
             */
            'magicSetterInvalidAssignmentType' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }

                        public function badSet(): void {
                            $this->__set("foo", new stdClass());
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertyDocblockAssignmentToMixed' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {
                        public function __get(string $name): ?string {
                            if ($name === "foo") {
                                return "hello";
                            }

                            return null;
                        }

                        /** @param mixed $value */
                        public function __set(string $name, $value): void {
                        }
                    }

                    /** @param mixed $b */
                    function foo($b) : void {
                        $a = new A();
                        $a->__set("foo", $b);
                    }',
                'error_message' => 'MixedPropertyTypeCoercion',
                'error_levels' => ['MixedAssignment'],
            ],
            'magicInterfacePropertyWrongProperty' => [
                '<?php
                    /**
                     * @property-read string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getBar(GetterSetter $o) : string {
                        return $o->bar;
                    }',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'psalmMagicInterfacePropertyWrongProperty' => [
                '<?php
                    /**
                     * @psalm-property-read string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getBar(GetterSetter $o) : string {
                        return $o->bar;
                    }',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'magicInterfaceWrongPropertyWrite' => [
                '<?php
                    /**
                     * @property-write string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : void {
                        $o->bar = "hello";
                    }',
                'error_message' => 'UndefinedMagicPropertyAssignment',
            ],
            'psalmMagicInterfaceWrongPropertyWrite' => [
                '<?php
                    /**
                     * @psalm-property-write string $foo
                     * @psalm-seal-properties
                     */
                    interface GetterSetter {
                        /** @return mixed */
                        public function __get(string $key);
                        /** @param mixed $value */
                        public function __set(string $key, $value) : void;
                    }

                    /** @psalm-suppress NoInterfaceProperties */
                    function getFoo(GetterSetter $o) : void {
                        $o->bar = "hello";
                    }',
                'error_message' => 'UndefinedMagicPropertyAssignment',
            ],
            'propertyDocblockOnProperty' => [
                '<?php
                    class A {
                       /** @property string[] */
                      public array $arr;
                    }',
                'error_message' => 'InvalidDocblock'
            ],
        ];
    }
}
