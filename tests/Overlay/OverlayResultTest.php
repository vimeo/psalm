<?php

declare(strict_types=1);

namespace Psalm\Tests\Overlay;

use PHPUnit\Framework\TestCase;
use Psalm\Overlay\OverlayResult;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class OverlayResultTest extends TestCase
{
    public function testKeysOfALaterProtocolAreIgnored(): void
    {
        $issue = [
            'link' => 'https://psalm.dev/003', 'severity' => 'error', 'line_from' => 8, 'line_to' => 8,
            'type' => 'InaccessibleMethod', 'message' => 'Cannot access private method App\A::foo from context App\B',
            'file_name' => 'src/B.php', 'file_path' => '/p/src/B.php', 'snippet' => '$a->foo();',
            'selected_text' => 'foo', 'from' => 99, 'to' => 102, 'snippet_from' => 80, 'snippet_to' => 105,
            'column_from' => 20, 'column_to' => 23, 'shortcode' => 3, 'error_level' => -1, 'taint_trace' => null,
            'other_references' => null, 'is_security' => false,
        ];
        $envelope = [
            'protocol' => 1,
            'new_issues' => [$issue],
            'analyzed_files' => ['src/A.php', 'src/B.php'],
            'not_checked' => ['unused_code'],
        ];

        $result = OverlayResult::fromEnvelope(
            ['later' => true, 'new_issues' => [$issue + ['later' => 1]]] + $envelope,
        );

        $this->assertTrue($result->hasNewErrors());
        $this->assertSame('InaccessibleMethod', $result->new_issues[0]->type);
        $this->assertSame(
            $envelope,
            json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR),
        );
    }
}
