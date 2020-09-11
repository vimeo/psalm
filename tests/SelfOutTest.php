<?php
namespace Psalm\Tests;

class SelfOutTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'changeInterface' => [
                '<?php
                      interface Foo {
                          /**
                           * @return void
                           */
                          public function far() {
                          }
                      }
                      class Bar {
                          /**
                           * @psalm-self-out Foo
                           * @return void
                           */
                          public function baz() {
                          }
                      }
                      $bar = new Bar();
                      $bar->baz();
                      $bar->far();
                '
            ]
        ];
    }
}
