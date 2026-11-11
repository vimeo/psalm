<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;

use function dirname;

final class IssueHandlerTest extends TestCase
{
    public function testFunctionIssueHandlerSuppressionsAreCounted(): void
    {
        $config = Config::loadFromXML(
            dirname(__DIR__),
            '<?xml version="1.0"?>
            <psalm>
                <projectFiles>
                    <directory name="src" />
                </projectFiles>

                <issueHandlers>
                    <UndefinedFunction>
                        <errorLevel type="suppress">
                            <referencedFunction name="emit_css_tag" />
                        </errorLevel>
                    </UndefinedFunction>
                </issueHandlers>
            </psalm>',
        );

        $this->assertSame(
            [['type' => 'UndefinedFunction', 'index' => 0, 'count' => 0]],
            $config->getIssueHandlerSuppressions(),
        );

        $this->assertSame('suppress', $config->getReportingLevelForFunction('UndefinedFunction', 'emit_css_tag'));
        $this->assertSame('suppress', $config->getReportingLevelForFunction('UndefinedFunction', 'Ns\\emit_css_tag'));
        $this->assertNull($config->getReportingLevelForFunction('UndefinedFunction', 'other'));

        $this->assertSame(
            [['type' => 'UndefinedFunction', 'index' => 0, 'count' => 2]],
            $config->getIssueHandlerSuppressions(),
        );
    }
}
