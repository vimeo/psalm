<?php
declare(strict_types = 1);

namespace Psalm\Internal\LanguageServer;

use AdvancedJsonRpc\Message as MessageBody;
use Exception;
use Psalm\Internal\LanguageServer\Message;
use Amp\Loop;

/**
 * Source: https://github.com/felixfbecker/php-language-server/tree/master/src/ProtocolStreamReader.php
 */
class ProtocolStreamReader implements ProtocolReader
{
    use EmitterTrait;

    const PARSE_HEADERS = 1;
    const PARSE_BODY = 2;

    /** @var resource */
    private $input;

    /** @var string */
    private $read_watcher;
    /**
     * This is checked by ProtocolStreamReader so that it will stop reading from streams in the forked process.
     * There could be buffered bytes in stdin/over TCP, those would be processed by TCP if it were not for this check.
     * @var bool
     */
    private $is_accepting_new_requests = true;
    /** @var int */
    private $parsing_mode = self::PARSE_HEADERS;
    /** @var string */
    private $buffer = '';
    /** @var string[] */
    private $headers = [];
    /** @var ?int */
    private $content_length = null;
    /** @var bool */
    private $did_emit_close = false;

    /**
     * @param resource $input
     */
    public function __construct($input)
    {
        $this->input = $input;

        $read_watcher = Loop::onReadable(
            $this->input,
            /** @return void */
            function () {
                if (feof($this->input)) {
                    // If stream_select reported a status change for this stream,
                    // but the stream is EOF, it means it was closed.
                    $this->emitClose();
                    return;
                }
                if (!$this->is_accepting_new_requests) {
                    // If we fork, don't read any bytes in the input buffer from the worker process.
                    $this->emitClose();
                    return;
                }
                $emitted_messages = $this->readMessages();
                if ($emitted_messages > 0) {
                    $this->emit('readMessageGroup');
                }
            }
        );

        $this->read_watcher = $read_watcher;

        $this->on(
            'close',
            /** @return void */
            function () {
                Loop::cancel($this->read_watcher);
            }
        );
    }

    /**
     * @return int
     */
    private function readMessages() : int
    {
        $emitted_messages = 0;
        while (($c = fgetc($this->input)) !== false && $c !== '') {
            $this->buffer .= $c;
            switch ($this->parsing_mode) {
                case self::PARSE_HEADERS:
                    if ($this->buffer === "\r\n") {
                        $this->parsing_mode = self::PARSE_BODY;
                        $this->content_length = (int)$this->headers['Content-Length'];
                        $this->buffer = '';
                    } elseif (substr($this->buffer, -2) === "\r\n") {
                        $parts = explode(':', $this->buffer);
                        $this->headers[$parts[0]] = trim($parts[1]);
                        $this->buffer = '';
                    }
                    break;
                case self::PARSE_BODY:
                    if (strlen($this->buffer) === $this->content_length) {
                        if (!$this->is_accepting_new_requests) {
                            // If we fork, don't read any bytes in the input buffer from the worker process.
                            $this->emitClose();
                            return $emitted_messages;
                        }
                        // MessageBody::parse can throw an Error, maybe log an error?
                        try {
                            $msg = new Message(MessageBody::parse($this->buffer), $this->headers);
                        } catch (Exception $_) {
                            $msg = null;
                        }
                        if ($msg) {
                            $emitted_messages++;
                            $this->emit('message', [$msg]);
                            /** @psalm-suppress DocblockTypeContradiction */
                            if (!$this->is_accepting_new_requests) {
                                // If we fork, don't read any bytes in the input buffer from the worker process.
                                $this->emitClose();
                                return $emitted_messages;
                            }
                        }
                        $this->parsing_mode = self::PARSE_HEADERS;
                        $this->headers = [];
                        $this->buffer = '';
                    }
                    break;
            }
        }
        return $emitted_messages;
    }

    /**
     * @return void
     */
    private function emitClose()
    {
        if ($this->did_emit_close) {
            return;
        }
        $this->did_emit_close = true;
        $this->emit('close');
    }
}
