<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ThisOutTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'changeInterface' => [
                'code' => '<?php
                      interface Foo {
                          /**
                           * @return void
                           */
                          public function far() {
                          }
                      }
                      class Bar {
                          /**
                           * @psalm-this-out Foo
                           * @return void
                           */
                          public function baz() {
                          }
                      }
                      $bar = new Bar();
                      $bar->baz();
                      $bar->far();
                ',
            ],
            'changeTemplateArguments' => [
                'code' => '<?php
                    /**
                     * @template-covariant T as int
                     */
                    class container {
                        /** @var list<T> */
                        public array $data;
                        /**
                         * @param T $data
                         */
                        public function __construct($data) { $this->data = [$data]; }
                        /**
                         * @template NewT as int
                         * @param NewT $data
                         *
                         * @psalm-this-out self<NewT>
                         */
                        public function setData($data): void {
                            /** @psalm-suppress InvalidPropertyAssignmentValue */
                            $this->data = [$data];
                        }
                        /**
                         * @template NewT as int
                         * @param NewT $data
                         *
                         * @psalm-this-out self<T|NewT>
                         */
                        public function addData($data): void {
                            /** @psalm-suppress InvalidPropertyAssignmentValue */
                            $this->data []= $data;
                        }
                        /**
                         * @return list<T>
                         */
                        public function getData(): array { return $this->data; }
                    }

                    $a = new container(1);
                    $data1 = $a->getData();
                    $a->setData(2);
                    $data2 = $a->getData();
                    $a->addData(3);
                    $data3 = $a->getData();
                ',
                'assertions' => [
                    '$data1===' => 'list<1>',
                    '$data2===' => 'list<2>',
                    '$data3===' => 'list<2|3>',
                ],
            ],
        ];
    }
}
