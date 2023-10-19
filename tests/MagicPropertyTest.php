<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
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

                $a = $child->hello;',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'propertyDocblock' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'ignored_issues' => ['MixedAssignment', 'MixedPropertyTypeCoercion'],
            ],
            'namedPropertyByVariable' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'ignored_issues' => ['MixedArgument'],
            ],
            'dontAssumeNonNullAfterPossibleMagicFetch' => [
                'code' => '<?php
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
                'ignored_issues' => ['PossiblyNullPropertyFetch'],
            ],
            'accessInMagicGet' => [
                'code' => '<?php
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
                'ignored_issues' => ['MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'overrideInheritedProperty' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'magicPropertyDefinedOnTrait' => [
                'code' => '<?php
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
                    $record->last_login_at = new DateTimeImmutable("now");',
            ],
            'reconcileMagicProperties' => [
                'code' => '<?php
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
                    }',
            ],
            'propertyReadIsExpanded' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'impureMethodTest' => [
                'code' => '<?php
                    /**
                     * @property array<string, string> $errors
                     *
                     * @psalm-seal-properties
                     */
                    final class OrganizationObject {

                        public function __get(string $key)
                        {
                            return [];
                        }

                        /**
                         * @param mixed $a
                         */
                        public function __set(string $key, $a): void
                        {
                        }

                        public function updateErrors(): void {
                            /** @var array<string, string> */
                            $errors = [];
                            $this->errors = $errors;
                        }
                        /** @return array<string, string> */
                        public function updateStatus(): array {
                            $_ = $this->errors;
                            $this->updateErrors();
                            $errors = $this->errors;
                            return $errors;
                        }
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'annotationWithoutGetter' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'ignored_issues' => ['MixedAssignment'],
            ],
            'magicInterfacePropertyWrongProperty' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                       /** @property string[] */
                      public array $arr;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
        ];
    }

    public function testSealAllMethodsWithoutFoo(): void
    {
        Config::getInstance()->seal_all_properties = true;

        $this->addFile(
            'somefile.php',
            '<?php
              class A {
                public function __get(string $name) {}
              }

              class B extends A {}

              $b = new B();
              $result = $b->foo;
              ',
        );

        $error_message = 'UndefinedMagicPropertyFetch';
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage($error_message);
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoSealAllProperties(): void
    {
        Config::getInstance()->seal_all_properties = true;
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              /** @psalm-no-seal-properties */
              class A {
                public function __get(string $name) {}
              }

              class B extends A {}

              $b = new B();
              /** @var string $result */
              $result = $b->foo;
              ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
