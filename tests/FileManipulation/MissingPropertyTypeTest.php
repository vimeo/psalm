<?php

namespace Psalm\Tests\FileManipulation;

class MissingPropertyTypeTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool,5?:bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'addMissingUnionType56' => [
                '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = "hello";
                            }
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @var int|string
                         *
                         * @psalm-var \'hello\'|4
                         */
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = "hello";
                            }
                        }
                    }',
                '5.6',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingNullableType56' => [
                '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @var int|null
                         *
                         * @psalm-var 4|null
                         */
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '5.6',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingNullableTypeNoDefault74' => [
                '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @var int|null
                         *
                         * @psalm-var 4|null
                         */
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingNullableTypeWithDefault74' => [
                '<?php
                    class A {
                        public $v = null;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '<?php
                    class A {
                        public ?int $v = null;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingUnionTypeSetInBranches74' => [
                '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = "hello";
                            }
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @var int|string
                         *
                         * @psalm-var \'hello\'|4
                         */
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = "hello";
                            }
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingIntTypeSetInBranches74' => [
                '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = 20;
                            }
                        }
                    }',
                '<?php
                    class A {
                        public int $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            } else {
                                $this->v = 20;
                            }
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingDocblockTypesSpacedProperly' => [
                '<?php
                    class A {
                        public $u;
                        public $v;

                        public function __construct(int $i, int $j) {
                            $this->u = $i;
                            $this->v = $j;
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @var int
                         */
                        public $u;

                        /**
                         * @var int
                         */
                        public $v;

                        public function __construct(int $i, int $j) {
                            $this->u = $i;
                            $this->v = $j;
                        }
                    }',
                '7.1',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingTypehintsSpacedProperly' => [
                '<?php
                    class A {
                        public $u;
                        public $v;

                        public function __construct(int $i, int $j) {
                            $this->u = $i;
                            $this->v = $j;
                        }
                    }',
                '<?php
                    class A {
                        public int $u;
                        public int $v;

                        public function __construct(int $i, int $j) {
                            $this->u = $i;
                            $this->v = $j;
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
            'addMissingTypehintWithDefault' => [
                '<?php
                    class A {
                        public $u = false;

                        public function bar() {
                            $this->u = true;
                        }
                    }',
                '<?php
                    class A {
                        public bool $u = false;

                        public function bar() {
                            $this->u = true;
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
            'dontAddMissingPropertyTypeInTrait' => [
                '<?php
                    trait T {
                        public $u;
                    }
                    class A {
                        use T;

                        public function bar() {
                            $this->u = 5;
                        }
                    }',
                '<?php
                    trait T {
                        public $u;
                    }
                    class A {
                        use T;

                        public function bar() {
                            $this->u = 5;
                        }
                    }',
                '7.4',
                ['MissingPropertyType'],
                true,
            ],
        ];
    }
}
