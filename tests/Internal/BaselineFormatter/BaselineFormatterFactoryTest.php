<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\BaselineFormatter;

use Exception;
use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Psalm\Internal\BaselineFormatter\BaselineFormatterFactory;
use Psalm\Internal\BaselineFormatter\JsonBaselineFormatter;
use Psalm\Internal\BaselineFormatter\XmlBaselineFormatter;

class BaselineFormatterFactoryTest extends TestCase
{
    /**
     * @dataProvider provideForTestFromKey
     * @param class-string|null $expectedClass
     */
    public function testFromKey(string $key, ?string $expectedClass): void
    {
        $sut = new BaselineFormatterFactory();
        if ($expectedClass === null) {
            $this->expectException(Exception::class);
            $sut->fromKey($key);
        } else {
            $this->assertInstanceOf($expectedClass, $sut->fromKey($key));
        }
    }

    /**
     * @return iterable<int, list{string, class-string|null}>
     */
    public function provideForTestFromKey(): iterable
    {
        yield ['xml', XmlBaselineFormatter::class];
        yield ['json', JsonBaselineFormatter::class];
        yield ['bogus', null];
    }

    /**
     * @dataProvider provideForTestFromOptionsAndConfig
     * @param class-string $expectedClass
     */
    public function testFromOptionsAndConfig(array $options, Config $config, string $expectedClass): void
    {
        $sut = new BaselineFormatterFactory();
        $this->assertInstanceOf($expectedClass, $sut->fromOptionsAndConfig($options, $config));
    }

    /**
     * @return iterable<int, list{array, Config, class-string}>
     */
    public function provideForTestFromOptionsAndConfig(): iterable
    {
        yield [
            ['baseline-formatter' => 'json'],
            Config::loadFromXML(__DIR__, '<?xml version="1.0"?><psalm/>'),
            JsonBaselineFormatter::class,
        ];
        yield [
            ['baseline-formatter' => 'xml'],
            Config::loadFromXML(__DIR__, '<?xml version="1.0"?><psalm/>'),
            XmlBaselineFormatter::class,
        ];
        yield [
            ['set-baseline' => 'psalm-baseline.json'],
            Config::loadFromXML(__DIR__, '<?xml version="1.0"?><psalm/>'),
            JsonBaselineFormatter::class,
        ];
        yield [
            ['set-baseline' => 'psalm-baseline.xml'],
            Config::loadFromXML(__DIR__, '<?xml version="1.0"?><psalm/>'),
            XmlBaselineFormatter::class,
        ];
        yield [
            [],
            Config::loadFromXML(__DIR__, '<?xml version="1.0"?><psalm errorBaseline="psalm-baseline.json"/>'),
            JsonBaselineFormatter::class,
        ];
        yield [
            [],
            Config::loadFromXML(__DIR__, '<?xml version="1.0"?><psalm errorBaseline="psalm-baseline.xml"/>'),
            XmlBaselineFormatter::class,
        ];
        yield [
            [],
            Config::loadFromXML(__DIR__, '<?xml version="1.0"?><psalm/>'),
            XmlBaselineFormatter::class,
        ];
    }
}
