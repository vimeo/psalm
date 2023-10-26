<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class MissingPropertyTypeTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
            'addMissingUnionType56' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '5.6',
                'issues_to_fix' => ['MissingPropertyType'],
                'safe_types' => true,
            ],
            'addMissingNullableType56' => [
                'input' => '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                'output' => '<?php
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
                'php_version' => '5.6',
                'issues_to_fix' => ['MissingPropertyType'],
                'safe_types' => true,
            ],
            'addMissingNullableTypeNoDefault74' => [
                'input' => '<?php
                    class A {
                        public $v;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                'output' => '<?php
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
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPropertyType'],
                'safe_types' => true,
            ],
            'addMissingNullableTypeWithDefault74' => [
                'input' => '<?php
                    class A {
                        public $v = null;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                'output' => '<?php
                    class A {
                        public ?int $v = null;

                        public function __construct() {
                            if (rand(0, 1)) {
                                $this->v = 4;
                            }
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPropertyType'],
                'safe_types' => true,
            ],
            'addMissingUnionTypeSetInBranches74' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPropertyType'],
                'safe_types' => true,
            ],
            'addMissingIntTypeSetInBranches74' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPropertyType'],
                'safe_types' => true,
            ],
            'addMissingDocblockTypesSpacedProperly' => [
                'input' => '<?php
                    class A {
                        public $u;
                        public $v;

                        public function __construct(int $i, int $j) {
                            $this->u = $i;
                            $this->v = $j;
                        }
                    }',
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingPropertyType'],
                'safe_types' => true,
            ],
            'addMissingTypehintsSpacedProperly' => [
                'input' => '<?php
                    class A {
                        public $u;
                        public $v;

                        public function __construct(int $i, int $j) {
                            $this->u = $i;
                            $this->v = $j;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public int $u;
                        public int $v;

                        public function __construct(int $i, int $j) {
                            $this->u = $i;
                            $this->v = $j;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPropertyType'],
                'safe_types' => true,
            ],
            'addMissingTypehintWithDefault' => [
                'input' => '<?php
                    class A {
                        public $u = false;

                        public function bar() {
                            $this->u = true;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public bool $u = false;

                        public function bar() {
                            $this->u = true;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPropertyType'],
                'safe_types' => true,
            ],
            'dontAddMissingPropertyTypeInTrait' => [
                'input' => '<?php
                    trait T {
                        public $u;
                    }
                    class A {
                        use T;

                        public function bar() {
                            $this->u = 5;
                        }
                    }',
                'output' => '<?php
                    trait T {
                        public $u;
                    }
                    class A {
                        use T;

                        public function bar() {
                            $this->u = 5;
                        }
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingPropertyType'],
                'safe_types' => true,
            ],
        ];
    }
}
