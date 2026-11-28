<?php

declare(strict_types=1);

namespace Psalm\Tests\LanguageServer;

use AdvancedJsonRpc\Notification;
use Amp\DeferredFuture;
use Amp\TimeoutCancellation;
use Psalm\Internal\LanguageServer\Message;
use Psalm\Internal\LanguageServer\ProtocolStreamReader;
use Psalm\Tests\AsyncTestCase;

use function Amp\delay;
use function fclose;
use function fwrite;
use function json_encode;
use function str_repeat;
use function stream_socket_pair;
use function strlen;
use function strpos;
use function substr;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;
use const PHP_OS_FAMILY;
use const STREAM_IPPROTO_IP;
use const STREAM_PF_INET;
use const STREAM_PF_UNIX;
use const STREAM_SOCK_STREAM;

final class ProtocolStreamReaderTest extends AsyncTestCase
{
    public function testTwoMessagesInOneRead(): void
    {
        $this->assertSame(['a', 'b'], $this->readTexts([self::frame('a') . self::frame('b')]));
    }

    public function testABodySplitAcrossReads(): void
    {
        $message = self::frame('some text');
        $split = strlen($message) - 5;

        $this->assertSame(['some text'], $this->readTexts([substr($message, 0, $split), substr($message, $split)]));
    }

    public function testAHeaderSplitAcrossReads(): void
    {
        $message = self::frame('some text');

        $this->assertSame(['some text'], $this->readTexts([substr($message, 0, 7), substr($message, 7)]));
    }

    public function testTheEndOfABodyAndTheNextMessageInOneRead(): void
    {
        $first = self::frame('first');
        $split = strlen($first) - 3;

        $this->assertSame(
            ['first', 'second'],
            $this->readTexts([substr($first, 0, $split), substr($first, $split) . self::frame('second')]),
        );
    }

    public function testAMultibyteBodySplitInsideACharacter(): void
    {
        $message = self::frame('é😀');
        $emoji = strpos($message, "\xF0");
        $this->assertNotFalse($emoji);
        $split = $emoji + 2;

        $this->assertSame(['é😀'], $this->readTexts([substr($message, 0, $split), substr($message, $split)]));
    }

    public function testABodyLargerThanARead(): void
    {
        $text = str_repeat('x', 200_000);

        $this->assertSame([$text], $this->readTexts([self::frame($text)]));
    }

    /**
     * Writes each chunk on its own to the stream the reader reads, and returns the text of each message it emits.
     *
     * @param list<string> $chunks
     * @return list<string>
     */
    private function readTexts(array $chunks): array
    {
        $pair = stream_socket_pair(
            PHP_OS_FAMILY === 'Windows' ? STREAM_PF_INET : STREAM_PF_UNIX,
            STREAM_SOCK_STREAM,
            STREAM_IPPROTO_IP,
        );
        $this->assertNotFalse($pair);
        [$input, $output] = $pair;

        $reader = new ProtocolStreamReader($input);
        $texts = [];
        $reader->on('message', function (Message $message) use (&$texts): void {
            $this->assertInstanceOf(Notification::class, $message->body);
            /** @var object{text: string} $params */
            $params = $message->body->params;
            $texts[] = $params->text;
        });
        $closed = new DeferredFuture;
        $reader->on('close', static fn() => $closed->complete(null));

        foreach ($chunks as $chunk) {
            fwrite($output, $chunk);
            delay(0.05); // let the reader read this chunk before the next one is written
        }
        fclose($output);

        $closed->getFuture()->await(new TimeoutCancellation(5));

        return $texts;
    }

    /**
     * @psalm-pure
     */
    private static function frame(string $text): string
    {
        $body = json_encode(
            ['jsonrpc' => '2.0', 'method' => 'textDocument/didChange', 'params' => ['text' => $text]],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );

        return 'Content-Length: ' . strlen($body) . "\r\n\r\n" . $body;
    }
}
