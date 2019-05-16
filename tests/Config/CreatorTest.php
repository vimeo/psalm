<?php
namespace Psalm\Tests\Config;

use Psalm\Config\Creator;

class CreatorTest extends \Psalm\Tests\TestCase
{
    public static function setUpBeforeClass() : void
    {
    }

    public function setUp() : void
    {
    }

    /**
     * @return void
     */
    public function testDiscoverLibDirectory()
    {
        $lib_contents = Creator::getContents(
            dirname(__DIR__, 1)
                . DIRECTORY_SEPARATOR . 'fixtures'
                . DIRECTORY_SEPARATOR . 'config_discovery'
                . DIRECTORY_SEPARATOR . 'files_in_lib',
            null,
            1
        );

        $this->assertSame('<?xml version="1.0"?>
<psalm
    totallyTyped="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
>
    <projectFiles>
        <directory name="lib" />
        <ignoreFiles>
            <directory name="vendor" />
        </ignoreFiles>
    </projectFiles>

    <issueHandlers>
        <LessSpecificReturnType errorLevel="info" />
    </issueHandlers>
</psalm>
', $lib_contents);
    }
}
