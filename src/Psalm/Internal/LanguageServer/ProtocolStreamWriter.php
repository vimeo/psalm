<?php
declare(strict_types = 1);

namespace Psalm\Internal\LanguageServer;

use Psalm\Internal\LanguageServer\Message;
use Amp\{
    Deferred,
    Loop,
    Promise
};
use RuntimeException;

/**
 * @internal
 */
class ProtocolStreamWriter implements ProtocolWriter
{
    /**
     * @var resource $output
     */
    private $output;

    /**
     * @var ?string
     */
    private $output_watcher;

    /**
     * @var array<int, array{message: string, deferred: Deferred}> $messages
     */
    private $messages = [];

    /**
     * @param resource $output
     */
    public function __construct($output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function write(Message $msg): Promise
    {
        // if the message queue is currently empty, register a write handler.
        if (empty($this->messages)) {
            $this->output_watcher = Loop::onWritable(
                $this->output,
                /** @return void */
                function () {
                    $this->flush();
                }
            );
        }

        $deferred = new \Amp\Deferred();
        $this->messages[] = [
            'message' => (string)$msg,
            'deferred' => $deferred
        ];
        return $deferred->promise();
    }

    /**
     * Writes pending messages to the output stream.
     *
     * @return void
     */
    private function flush()
    {
        $keepWriting = true;
        while ($keepWriting) {
            $message = $this->messages[0]['message'];
            $deferred = $this->messages[0]['deferred'];

            $bytesWritten = @fwrite($this->output, $message);

            if ($bytesWritten > 0) {
                $message = substr($message, $bytesWritten);
            }

            // Determine if this message was completely sent
            if (strlen($message) === 0) {
                array_shift($this->messages);

                // This was the last message in the queue, remove the write handler.
                if (count($this->messages) === 0 && $this->output_watcher) {
                    Loop::cancel($this->output_watcher);
                    $keepWriting = false;
                }

                $deferred->resolve();
            } else {
                $this->messages[0]['message'] = $message;
                $keepWriting = false;
            }
        }
    }
}
