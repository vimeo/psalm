<?php
declare(strict_types = 1);

namespace Psalm\Internal\LanguageServer;

use AdvancedJsonRpc;
use Amp\Deferred;
use Amp\Promise;
use function Amp\call;

/**
 * @internal
 */
class ClientHandler
{
    /**
     * @var ProtocolReader
     */
    public $protocolReader;

    /**
     * @var ProtocolWriter
     */
    public $protocolWriter;

    /**
     * @var IdGenerator
     */
    public $idGenerator;

    public function __construct(ProtocolReader $protocolReader, ProtocolWriter $protocolWriter)
    {
        $this->protocolReader = $protocolReader;
        $this->protocolWriter = $protocolWriter;
        $this->idGenerator = new IdGenerator;
    }

    /**
     * Sends a request to the client and returns a promise that is resolved with the result or rejected with the error
     *
     * @param string $method The method to call
     * @param array|object $params The method parameters
     * @return Promise<mixed> Resolved with the result of the request or rejected with an error
     */
    public function request(string $method, $params): Promise
    {
        $id = $this->idGenerator->generate();

        return call(
            /**
             * @return \Generator<int, Promise, mixed, mixed>
             */
            function () use ($id, $method, $params) {
                yield $this->protocolWriter->write(
                    new Message(
                        new AdvancedJsonRpc\Request($id, $method, (object) $params)
                    )
                );

                $deferred = new Deferred();

                $listener =
                    /**
                     * @param callable $listener
                     * @return void
                     */
                    function (Message $msg) use ($id, $deferred, &$listener) {
                        error_log('request handler');
                        /**
                         * @psalm-suppress UndefinedPropertyFetch
                         * @psalm-suppress MixedArgument
                         */
                        if ($msg->body
                            && AdvancedJsonRpc\Response::isResponse($msg->body)
                            && $msg->body->id === $id
                        ) {
                            // Received a response
                            $this->protocolReader->removeListener('message', $listener);
                            if (AdvancedJsonRpc\SuccessResponse::isSuccessResponse($msg->body)) {
                                $deferred->resolve($msg->body->result);
                            } else {
                                $deferred->fail($msg->body->error);
                            }
                        }
                    };
                $this->protocolReader->on('message', $listener);
                return $deferred->promise();
            }
        );
    }

    /**
     * Sends a notification to the client
     *
     * @param string $method The method to call
     * @param array|object $params The method parameters
     * @return Promise <null> Will be resolved as soon as the notification has been sent
     */
    public function notify(string $method, $params): Promise
    {
        return $this->protocolWriter->write(
            new Message(
                new AdvancedJsonRpc\Notification($method, (object)$params)
            )
        );
    }
}
