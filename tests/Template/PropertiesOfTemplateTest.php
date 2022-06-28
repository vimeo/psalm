<?php

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class PropertiesOfTemplateTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'propertiesOfTemplateParam' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $a = $objAsArray["a"];
                    $aPlus2 = $a + 2;
                    $b = $objAsArray["b"];
                    if ($b === true) {
                        echo "True!";
                    }
                    $c = $objAsArray["c"];
                    $cConcat = $c . "foo";
                ',
            ],
            'privatePropertiesPicksPrivate' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return private-properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var private-properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $b = $objAsArray["b"];
                ',
            ],
            'protectedPropertiesPicksProtected' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return protected-properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var protected-properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $b = $objAsArray["c"];
                ',
            ],
            'publicPropertiesPicksPublic' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return public-properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var public-properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $a = $objAsArray["a"];
                ',
            ],
            'propertiesOfNestedTemplates' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @template TArray of array<array-key, T>
                     * @param TArray $array
                     * @return properties-of<T>
                     */
                    function asArray($array) {
                        /** @var properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray([$obj]);
                    $b = $objAsArray["c"];
                '
            ],
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'propertiesOfAllowsOnlyDefinedProperties' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $d = $objAsArray["d"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'privatePropertiesPicksNoPublic' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return private-properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var private-properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $b = $objAsArray["a"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'privatePropertiesPicksNoProtected' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return private-properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var private-properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $b = $objAsArray["c"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'protectedPropertiesPicksNoPublic' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return protected-properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var protected-properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $b = $objAsArray["a"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'protectedPropertiesPicksNoPrivate' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return protected-properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var protected-properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $b = $objAsArray["b"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'publicPropertiesPicksNoPrivate' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return public-properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var public-properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $a = $objAsArray["b"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'publicPropertiesPicksNoProtected' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $obj
                     * @return public-properties-of<T>
                     */
                    function asArray($obj) {
                        /** @var public-properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray($obj);
                    $a = $objAsArray["c"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
            'propertiesOfNestedTemplatesPickPublic' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @template TArray of array<array-key, T>
                     * @param TArray $array
                     * @return properties-of<T>
                     */
                    function asArray($array) {
                        /** @var properties-of<T> */
                        $properties = [];
                        return $properties;
                    }

                    class A {
                        /** @var int */
                        public $a = 42;
                        /** @var bool */
                        private $b = true;
                        /** @var string */
                        protected $c = "c";
                    }

                    $obj = new A();
                    $objAsArray = asArray([$obj]);
                    $b = $objAsArray["d"];
                ',
                'error_message' => 'InvalidArrayOffset'
            ],
        ];
    }
}
