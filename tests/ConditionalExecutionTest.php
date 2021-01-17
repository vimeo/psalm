<?php
namespace Psalm\Tests;

use UnexpectedValueException;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider;
use function strpos;
use function substr;

class ConditionalExecutionTest extends TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    public function setUp() : void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            new TestConfig(),
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );
    }

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $code
     * @param array<string> $error_levels
     */
    public function testValidCode($code, array $error_levels = []): void
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $version_position = strpos($test_name, 'PHP-');
        if ($version_position === false) {
            throw new UnexpectedValueException('No PHPVersion in test name');
        }

        $version = substr($test_name, $version_position + 4, 3);
        $this->project_analyzer->setPhpVersion($version);

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $code
        );

        foreach ($error_levels as $error_level) {
            $this->project_analyzer->getCodebase()->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return array<string, array{string,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'PHP-7.2-hrtime' => [
                '<?php
                    $_startTime = \PHP_VERSION_ID >= 70300 ? \hrtime(false)[0] : \time();'
            ],
            'PHP-7.4-DateTimeCreateFromInterface' => [
                '<?php
                    namespace FooBar;

                    class Datetime extends \DateTime
                    {
                        /**
                         * @return \DateTime
                         */
                        public static function createFromInterface(\DatetimeInterface $datetime)
                        {
                            if (\PHP_VERSION_ID >= 80000) {
                                return parent::createFromInterface($datetime);
                            }
                        }
                    }'
            ],
        ];
    }
}
