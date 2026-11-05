<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function dirname;
use function extension_loaded;

final class ExtImagickStubTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];
    }

    public function testClassesAreUndefinedWhenExtensionIsDisabled(): void
    {
        if (extension_loaded('imagick')) {
            self::markTestSkipped('A loaded imagick extension defines the classes through reflection');
        }

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/UndefinedClass.*Imagick/');

        $this->project_analyzer = new ProjectAnalyzer(
            TestConfig::loadFromXML(
                dirname(__DIR__),
                '<?xml version="1.0"?>
                <psalm>
                    <disableExtensions>
                        <extension name="imagick"/>
                    </disableExtensions>
                </psalm>',
            ),
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );
        $this->project_analyzer->setPhpVersion('8.3', 'tests');

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->enterServerMode();
        $codebase->config->visitPreloadedStubFiles($codebase);

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, '<?php
            function f(): Imagick {
                return new Imagick("image.png");
            }
        ');
        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'image methods return their CallMap types' => [
                'code' => <<<'PHP'
                    <?php
                    function f(Imagick $im): void {
                        $read = $im->readImage('photo.png');
                        $width = $im->getImageWidth();
                        $blob = $im->getImageBlob();
                        $copy = $im->clone();
                        $pixel = $im->getImagePixelColor(0, 0);
                        $extrema = $im->getImageExtrema();
                        /** @psalm-check-type-exact $read = bool */
                        /** @psalm-check-type-exact $width = int */
                        /** @psalm-check-type-exact $blob = string */
                        /** @psalm-check-type-exact $copy = Imagick */
                        /** @psalm-check-type-exact $pixel = ImagickPixel */
                        /** @psalm-check-type-exact $extrema = array{max: int, min: int} */
                    }
                    PHP,
            ],
            'optional parameters and defaults' => [
                'code' => <<<'PHP'
                    <?php
                    function f(Imagick $im, ImagickPixel $background): void {
                        $im->newImage(10, 10, $background);
                        $im->newImage(10, 10, 'white', 'png');
                        $im->resizeImage(10, 10, Imagick::FILTER_LANCZOS, 1.0);
                        $im->resizeImage(10, 10, Imagick::FILTER_LANCZOS, 1.0, true);
                        $im->setImageBackgroundColor('red');
                        $im->setImageBackgroundColor($background);
                    }
                    PHP,
            ],
            'by-reference parameters are written' => [
                'code' => <<<'PHP'
                    <?php
                    function f(Imagick $im, Imagick $reference): void {
                        $im->subImageMatch($reference, $offset, $similarity);
                        /** @psalm-check-type $offset = mixed */
                        /** @psalm-check-type $similarity = mixed */
                    }
                    PHP,
            ],
            'static factory methods return their class' => [
                'code' => <<<'PHP'
                    <?php
                    $kernel = ImagickKernel::fromBuiltin(Imagick::KERNEL_GAUSSIAN, '5,2');
                    $other = ImagickKernel::fromMatrix([[1, 0], [0, 1]]);
                    /** @psalm-check-type-exact $kernel = ImagickKernel */
                    /** @psalm-check-type-exact $other = ImagickKernel */
                    PHP,
            ],
            'draw and pixel usage' => [
                'code' => <<<'PHP'
                    <?php
                    $draw = new ImagickDraw();
                    $color = new ImagickPixel('red');
                    $draw->setFillColor($color);
                    $draw->setFillColor('blue');
                    $fill = $draw->getFillColor();
                    $text = $color->getColorAsString();
                    /** @psalm-check-type-exact $fill = ImagickPixel */
                    /** @psalm-check-type-exact $text = string */
                    PHP,
            ],
            'ImagickPixel getColor keeps its return type provider' => [
                'code' => <<<'PHP'
                    <?php
                    $color = new ImagickPixel('red');
                    $rgba = $color->getColor();
                    /** @psalm-check-type-exact $rgba = array{a: int<0, 1>, b: int<0, 255>, g: int<0, 255>, r: int<0, 255>} */
                    PHP,
            ],
            'constants have ImageMagick 7 values' => [
                'code' => <<<'PHP'
                    <?php
                    $filter = Imagick::FILTER_LANCZOS;
                    $channel = Imagick::CHANNEL_DEFAULT;
                    $format = Imagick::COMPRESSION_JPEG;
                    /** @psalm-check-type-exact $filter = 22 */
                    /** @psalm-check-type-exact $channel = 134217727 */
                    /** @psalm-check-type-exact $format = 12 */
                    PHP,
            ],
            'exception hierarchy' => [
                'code' => <<<'PHP'
                    <?php
                    function f(
                        ImagickException $a,
                        ImagickDrawException $b,
                        ImagickPixelException $c,
                        ImagickPixelIteratorException $d,
                        ImagickKernelException $e,
                    ): void {
                        $exceptions = [$a, $b, $c, $d, $e];
                        foreach ($exceptions as $exception) {
                            $message = $exception->getMessage();
                            /** @psalm-check-type-exact $message = string */
                        }
                    }
                    function g(): void {
                        try {
                            new Imagick('missing.png');
                        } catch (ImagickException $e) {
                            throw new RuntimeException($e->getMessage(), 0, $e);
                        }
                    }
                    PHP,
            ],
            'foreach over Imagick yields frames' => [
                'code' => <<<'PHP'
                    <?php
                    function f(Imagick $im): void {
                        foreach ($im as $index => $frame) {
                            /** @psalm-check-type-exact $index = int */
                            /** @psalm-check-type-exact $frame = Imagick */
                        }
                    }
                    PHP,
            ],
            'Imagick is Countable' => [
                'code' => <<<'PHP'
                    <?php
                    function f(Imagick $im): int {
                        return count($im);
                    }
                    PHP,
            ],
            'pixel iterator rows' => [
                'code' => <<<'PHP'
                    <?php
                    function f(Imagick $im): void {
                        $iterator = new ImagickPixelIterator($im);
                        foreach ($iterator as $y => $row) {
                            /** @psalm-check-type-exact $y = int */
                            /** @psalm-check-type-exact $row = array<array-key, mixed>|null */
                        }
                    }
                    PHP,
            ],
            'subclass of Imagick' => [
                'code' => <<<'PHP'
                    <?php
                    final class Thumbnail extends Imagick {
                        public function __construct(string $path) {
                            parent::__construct($path);
                            $this->thumbnailImage(100, 100);
                        }

                        public function width(): int {
                            return $this->getImageWidth();
                        }
                    }
                    PHP,
            ],
        ];
    }
}
