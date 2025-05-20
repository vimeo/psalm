<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Codebase;

use Override;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function array_map;

/**
 * Fat tests for method `getCompletionItemsForClassishThing` of class `Psalm\Codebase`.
 */
final class MethodGetCompletionItemsForClassishThingTest extends TestCase
{
    private Codebase $codebase;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->file_provider = new FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new ParserCacheProvider($config, '', true),
            null,
            null,
            new FileReferenceCacheProvider($config, '', true),
            new ProjectCacheProvider(),
        );

        $this->codebase = new Codebase($config, $providers);

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            null,
            [],
            1,
            1,
            null,
            $this->codebase,
        );

        $this->project_analyzer->setPhpVersion('7.3', 'tests');
        $this->project_analyzer->getCodebase()->store_node_types = true;

        $this->codebase->config->throw_exception = false;
    }

    /**
     * @return list<string>
     */
    protected function getCompletionLabels(string $content, string $class_name, string $gap): array
    {
        $this->addFile('somefile.php', $content);

        $this->analyzeFile('somefile.php', new Context());

        $items = $this->codebase->getCompletionItemsForClassishThing($class_name, $gap, true);

        return array_map(fn($item) => $item->label, $items);
    }

    /**
     * @return iterable<array-key, array{0: string}>
     */
    public function providerGaps(): iterable
    {
        return [
            'object-gap' => ['->'],
            'static-gap' => ['::'],
        ];
    }

    /**
     * @dataProvider providerGaps
     */
    public function testSimpleOnceClass(string $gap): void
    {
        $content = <<<'EOF'
            <?php
            namespace B;

            /**
             * @property int $magicObjProp1
             * @property-read string $magicObjProp2
             * @method int magicObjMethod()
             * @method static string magicStaticMethod()
             */
            class A {
                public      $publicObjProp;
                protected   $protectedObjProp;
                private     $privateObjProp;

                public      static  $publicStaticProp;
                protected   static  $protectedStaticProp;
                private     static  $privateStaticProp;

                public      function    publicObjMethod() {}
                protected   function    protectedObjMethod() {}
                private     function    privateObjMethod() {}

                public      static  function    publicStaticMethod() {}
                protected   static  function    protectedStaticMethod() {}
                private     static  function    privateStaticMethod() {}
            }
        EOF;

        $actual_labels = $this->getCompletionLabels($content, 'B\A', $gap);

        $expected_labels = [
            '->' => [
                'magicObjProp1',
                'magicObjProp2',
    
                'magicObjMethod',
    
                'publicObjProp',
                'protectedObjProp',
                'privateObjProp',

                'publicObjMethod',
                'protectedObjMethod',
                'privateObjMethod',
                
                'publicStaticMethod',
                'protectedStaticMethod',
                'privateStaticMethod',
            ],
            '::' => [
                'magicStaticMethod',

                'publicStaticProp',
                'protectedStaticProp',
                'privateStaticProp',
    
                'publicStaticMethod',
                'protectedStaticMethod',
                'privateStaticMethod',
            ],
        ];

        $this->assertEqualsCanonicalizing($expected_labels[$gap], $actual_labels);
    }

    /**
     * @dataProvider providerGaps
     */
    public function testAbstractClass(string $gap): void
    {
        $content = <<<'EOF'
            <?php
            namespace B;

            /**
            * @property int $magicObjProp1
            * @property-read string $magicObjProp2
            * @method int magicObjMethod()
            * @method static string magicStaticMethod()
            */
            abstract class A {
                public      $publicObjProp;
                protected   $protectedObjProp;
                private     $privateObjProp;

                public      static  $publicStaticProp;
                protected   static  $protectedStaticProp;
                private     static  $privateStaticProp;

                abstract    public      function    abstractPublicMethod();
                abstract    protected   function    abstractProtectedMethod();

                public      function    publicObjMethod() {}
                protected   function    protectedObjMethod() {}
                private     function    privateObjMethod() {}

                public      static  function    publicStaticMethod() {}
                protected   static  function    protectedStaticMethod() {}
                private     static  function    privateStaticMethod() {}
            }
        EOF;

        $actual_labels = $this->getCompletionLabels($content, 'B\A', $gap);

        $expected_labels = [
            '->' => [
                'magicObjProp1',
                'magicObjProp2',
    
                'magicObjMethod',
    
                'publicObjProp',
                'protectedObjProp',
                'privateObjProp',
    
                'abstractPublicMethod',
                'abstractProtectedMethod',

                'publicObjMethod',
                'protectedObjMethod',
                'privateObjMethod',
                
                'publicStaticMethod',
                'protectedStaticMethod',
                'privateStaticMethod',
            ],
            '::' => [
                'magicStaticMethod',

                'publicStaticProp',
                'protectedStaticProp',
                'privateStaticProp',
    
                'publicStaticMethod',
                'protectedStaticMethod',
                'privateStaticMethod',
            ],
        ];

        $this->assertEqualsCanonicalizing($expected_labels[$gap], $actual_labels);
    }

    /**
     * @dataProvider providerGaps
     */
    public function testUseTrait(string $gap): void
    {
        $content = <<<'EOF'
            <?php
            namespace B;

            /**
             * @property int $magicObjProp1
             * @property-read string $magicObjProp2
             * @method int magicObjMethod()
             * @method static string magicStaticMethod()
             */
            trait C {
                public      $publicObjProp;
                protected   $protectedObjProp;
                private     $privateObjProp;

                public      static  $publicStaticProp;
                protected   static  $protectedStaticProp;
                private     static  $privateStaticProp;

                abstract    public      function    abstractPublicMethod();
                abstract    protected   function    abstractProtectedMethod();

                public      function    publicObjMethod() {}
                protected   function    protectedObjMethod() {}
                private     function    privateObjMethod() {}

                public      static  function    publicStaticMethod() {}
                protected   static  function    protectedStaticMethod() {}
                private     static  function    privateStaticMethod() {}
            }

            class A {
                use C;
            }
        EOF;

        $actual_labels = $this->getCompletionLabels($content, 'B\A', $gap);

        $expected_labels = [
            '->' => [
                'magicObjProp1',
                'magicObjProp2',
    
                'magicObjMethod',
                //'magicStaticMethod',
    
                'publicObjProp',
                'protectedObjProp',
                'privateObjProp',

                'abstractPublicMethod',
                'abstractProtectedMethod',

                'publicObjMethod',
                'protectedObjMethod',
                'privateObjMethod',

                'publicStaticMethod',
                'protectedStaticMethod',
                'privateStaticMethod',
            ],
            '::' => [
                'magicStaticMethod',
                'publicStaticProp',
                'protectedStaticProp',
                'privateStaticProp',

                'publicStaticMethod',
                'protectedStaticMethod',
                'privateStaticMethod',
            ],
        ];

        $this->assertEqualsCanonicalizing($expected_labels[$gap], $actual_labels);
    }

    /**
     * @dataProvider providerGaps
     */
    public function testUseTraitWithAbstractClass(string $gap): void
    {
        $content = <<<'EOF'
            <?php
            namespace B;

            /**
             * @property int $magicObjProp1
             * @property-read string $magicObjProp2
             * @method int magicObjMethod()
             * @method static string magicStaticMethod()
             */
            trait C {
                public      $publicObjProp;
                protected   $protectedObjProp;
                private     $privateObjProp;

                public      static  $publicStaticProp;
                protected   static  $protectedStaticProp;
                private     static  $privateStaticProp;

                abstract    public      function    abstractPublicMethod();
                abstract    protected   function    abstractProtectedMethod();

                public      function    publicObjMethod() {}
                protected   function    protectedObjMethod() {}
                private     function    privateObjMethod() {}

                public      static  function    publicStaticMethod() {}
                protected   static  function    protectedStaticMethod() {}
                private     static  function    privateStaticMethod() {}
            }

            abstract class A {
                use C;
            }
        EOF;

        $actual_labels = $this->getCompletionLabels($content, 'B\A', $gap);

        $expected_labels = [
            '->' => [
                'magicObjProp1',
                'magicObjProp2',
    
                'magicObjMethod',
                //'magicStaticMethod',
    
                'publicObjProp',
                'protectedObjProp',
                'privateObjProp',

                'abstractPublicMethod',
                'abstractProtectedMethod',

                'publicObjMethod',
                'protectedObjMethod',
                'privateObjMethod',

                'publicStaticMethod',
                'protectedStaticMethod',
                'privateStaticMethod',
            ],
            '::' => [
                'magicStaticMethod',
                'publicStaticProp',
                'protectedStaticProp',
                'privateStaticProp',

                'publicStaticMethod',
                'protectedStaticMethod',
                'privateStaticMethod',
            ],
        ];

        $this->assertEqualsCanonicalizing($expected_labels[$gap], $actual_labels);
    }

    /**
     * @dataProvider providerGaps
     */
    public function testClassWithExtends(string $gap): void
    {
        $content = <<<'EOF'
            <?php
            namespace B;

            /**
             * @property int $magicObjProp1
             * @property-read string $magicObjProp2
             * @method int magicObjMethod()
             * @method static string magicStaticMethod()
             */
            class C {
                public      $publicObjProp;
                protected   $protectedObjProp;
                private     $privateObjProp;

                public      static  $publicStaticProp;
                protected   static  $protectedStaticProp;
                private     static  $privateStaticProp;

                public      function    publicObjMethod() {}
                protected   function    protectedObjMethod() {}
                private     function    privateObjMethod() {}

                public      static  function    publicStaticMethod() {}
                protected   static  function    protectedStaticMethod() {}
                private     static  function    privateStaticMethod() {}
            }

            class A extends C {
                
            }
        EOF;

        $actual_labels = $this->getCompletionLabels($content, 'B\A', $gap);

        $expected_labels = [
            '->' => [
                'magicObjProp1',
                'magicObjProp2',
    
                'magicObjMethod',
                //'magicStaticMethod',
    
                'publicObjProp',
                'protectedObjProp',

                'publicObjMethod',
                'protectedObjMethod',

                'publicStaticMethod',
                'protectedStaticMethod',
            ],
            '::' => [
                'magicStaticMethod',
                'publicStaticProp',
                'protectedStaticProp',

                'publicStaticMethod',
                'protectedStaticMethod',
            ],
        ];

        $this->assertEqualsCanonicalizing($expected_labels[$gap], $actual_labels);
    }

    /**
     * @dataProvider providerGaps
     */
    public function testAstractClassWithInterface(string $gap): void
    {
        $content = <<<'EOF'
            <?php
            namespace B;

            interface C {
                public      function    publicObjMethod();
                protected   function    protectedObjMethod();
            }

            abstract class A implements C {
                abstract    public      function    publicObjMethod();
                abstract    protected   function    protectedObjMethod();
            }
        EOF;

        $actual_labels = $this->getCompletionLabels($content, 'B\A', $gap);

        $expected_labels = [
            '->' => [
                'publicObjMethod',
                'protectedObjMethod',
            ],
            '::' => [],
        ];

        $this->assertEqualsCanonicalizing($expected_labels[$gap], $actual_labels);
    }

    /**
     * @dataProvider providerGaps
     */
    public function testClassWithAnnotationMixin(string $gap): void
    {
        $content = <<<'EOF'
            <?php
            namespace B;

            /**
             * @property int $magicObjProp1
             * @property-read string $magicObjProp2
             * @method int magicObjMethod()
             * @method static string magicStaticMethod()
             */
            class C {
                public      $publicObjProp;
                protected   $protectedObjProp;
                private     $privateObjProp;

                public      static  $publicStaticProp;
                protected   static  $protectedStaticProp;
                private     static  $privateStaticProp;

                public      function    publicObjMethod() {}
                protected   function    protectedObjMethod() {}
                private     function    privateObjMethod() {}

                public      static  function    publicStaticMethod() {}
                protected   static  function    protectedStaticMethod() {}
                private     static  function    privateStaticMethod() {}
            }

            /**
             * @mixin C
             */
            class A {
                
            }
        EOF;

        $actual_labels = $this->getCompletionLabels($content, 'B\A', $gap);

        $expected_labels = [
            '->' => [
                'magicObjProp1',
                'magicObjProp2',
                'magicObjMethod',

                'publicObjProp',

                'publicObjMethod',

                'publicStaticMethod',
            ],
            '::' => [],
        ];

        $this->assertEqualsCanonicalizing($expected_labels[$gap], $actual_labels);
    }

    public function testResolveCollisionWithMixin(): void
    {
        $content = <<<'EOF'
            <?php
            namespace B;

            /** @mixin A */
            class C {
                public $myObjProp;
            }

            /** @mixin C */
            class A {}
        EOF;

        $actual_labels = $this->getCompletionLabels($content, 'B\A', '->');

        $expected_labels = [
            'myObjProp',
        ];

        $this->assertEqualsCanonicalizing($expected_labels, $actual_labels);
    }
}
