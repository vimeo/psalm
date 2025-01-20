<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Closure;
use Exception;
use Psalm\Config;
use Throwable;

use function array_fill_keys;
use function array_keys;
use function array_map;
use function array_pop;
use function array_values;
use function assert;
use function base64_decode;
use function base64_encode;
use function count;
use function error_get_last;
use function error_log;
use function explode;
use function extension_loaded;
use function fclose;
use function feof;
use function fread;
use function fwrite;
use function gettype;
use function igbinary_serialize;
use function igbinary_unserialize;
use function in_array;
use function ini_get;
use function pcntl_fork;
use function pcntl_waitpid;
use function pcntl_wexitstatus;
use function pcntl_wifsignaled;
use function pcntl_wtermsig;
use function posix_get_last_error;
use function posix_kill;
use function posix_strerror;
use function serialize;
use function str_contains;
use function stream_select;
use function stream_set_blocking;
use function stream_socket_pair;
use function stripos;
use function strlen;
use function substr;
use function unserialize;
use function usleep;

use const PHP_EOL;
use const SIGALRM;
use const SIGTERM;
use const STREAM_IPPROTO_IP;
use const STREAM_PF_UNIX;
use const STREAM_SOCK_STREAM;

/**
 * Adapted with relatively few changes from
 * https://github.com/etsy/phan/blob/1ccbe7a43a6151ca7c0759d6c53e2c3686994e53/src/Phan/ForkPool.php
 *
 * Authors: https://github.com/morria, https://github.com/TysonAndre
 *
 * Fork off to n-processes and divide up tasks between
 * each process.
 *
 * @internal
 */
final class Pool
{
    private const EXIT_SUCCESS = 0;
    private const EXIT_FAILURE = 1;

    /** @var int[] */
    private array $child_pid_list = [];

    /** @var resource[] */
    private array $read_streams = [];

    /**
     * @param array<int, array<int, mixed>> $process_task_data_iterator
     * An array of task data items to be divided up among the
     * workers. The size of this is the number of forked processes.
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     * @param Closure $startup_closure
     * A closure to execute upon starting a child
     * @param Closure(int, mixed):mixed $task_closure
     * A method to execute on each task data.
     * This closure must return an array (to be gathered).
     * @param Closure():mixed $shutdown_closure
     * A closure to execute upon shutting down a child
     * @param Closure(mixed $data):void $task_done_closure
     * A closure to execute when a task is done
     * @psalm-suppress MixedAssignment
     */
    public function __construct(
        private readonly Config $config,
        array $process_task_data_iterator,
        Closure $startup_closure,
        Closure $task_closure,
        Closure $shutdown_closure,
        private readonly ?Closure $task_done_closure = null,
    ) {
        $pool_size = count($process_task_data_iterator);

        assert(
            $pool_size > 1,
            'The pool size must be >= 2 to use the fork pool.',
        );

        if (!extension_loaded('pcntl') || !extension_loaded('posix')) {
            echo
                'The pcntl & posix extensions must be loaded in order for Psalm to be able to use multiple processes.'
                . PHP_EOL;
            exit(1);
        }

        $disabled_functions = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (in_array('pcntl_fork', $disabled_functions)) {
            echo "pcntl_fork() is disabled by php configuration (disable_functions directive).\n"
                . "Please enable it or run Psalm single-threaded with --threads=1 cli switch.\n";
            exit(1);
        }

        // We'll keep track of if this is the parent process
        // so that we can tell who will be doing the waiting
        $is_parent = false;

        $sockets = [];

        // Fork as many times as requested to get the given
        // pool size
        for ($proc_id = 0; $proc_id < $pool_size; ++$proc_id) {
            // Create an IPC socket pair.
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            if (!$sockets) {
                error_log('unable to create stream socket pair');
                exit(self::EXIT_FAILURE);
            }

            // Fork
            if (($pid = pcntl_fork()) < 0) {
                error_log(posix_strerror(posix_get_last_error()));
                exit(self::EXIT_FAILURE);
            }

            // Parent
            if ($pid > 0) {
                $is_parent = true;
                $this->child_pid_list[] = $pid;
                $this->read_streams[] = self::streamForParent($sockets);
                continue;
            }

            // Child
            if ($pid === 0) {
                $is_parent = false;
                break;
            }
        }

        // If we're the parent, return
        if ($is_parent) {
            return;
        }

        // Get the write stream for the child.
        $write_stream = self::streamForChild($sockets);

        // Execute anything the children wanted to execute upon
        // starting up
        $startup_closure();

        // Get the work for this process
        $task_data_iterator = array_values($process_task_data_iterator)[$proc_id];

        $task_done_buffer = '';

        try {
            foreach ($task_data_iterator as $i => $task_data) {
                $task_result = $task_closure($i, $task_data);

                $task_done_message = new ForkTaskDoneMessage($task_result);
                if ($this->config->use_igbinary) {
                    $encoded_message = base64_encode((string) igbinary_serialize($task_done_message));
                } else {
                    $encoded_message = base64_encode(serialize($task_done_message));
                }
                $serialized_message = $task_done_buffer . $encoded_message . "\n";

                if (strlen($serialized_message) > 200) {
                    $bytes_written = (int) @fwrite($write_stream, $serialized_message);

                    if (strlen($serialized_message) !== $bytes_written) {
                        $task_done_buffer = substr($serialized_message, $bytes_written);
                    } else {
                        $task_done_buffer = '';
                    }
                } else {
                    $task_done_buffer = $serialized_message;
                }
            }

            // Execute each child's shutdown closure before
            // exiting the process
            $results = $shutdown_closure();

            // Serialize this child's produced results and send them to the parent.
            $process_done_message = new ForkProcessDoneMessage($results ?: []);
        } catch (Throwable $t) {
            // This can happen when developing Psalm from source without running `composer update`,
            // or because of rare bugs in Psalm.
            $process_done_message = new ForkProcessErrorMessage(
                $t::class . ' ' . $t->getMessage() . "\n" .
                "Emitted in " . $t->getFile() . ":" . $t->getLine() . "\n" .
                "Stack trace in the forked worker:\n" .
                $t->getTraceAsString(),
            );
        }

        if ($this->config->use_igbinary) {
            $encoded_message = base64_encode((string) igbinary_serialize($process_done_message));
        } else {
            $encoded_message = base64_encode(serialize($process_done_message));
        }
        $serialized_message = $task_done_buffer . $encoded_message . "\n";

        $bytes_to_write = strlen($serialized_message);
        $bytes_written = 0;

        while ($bytes_written < $bytes_to_write && !feof($write_stream)) {
            // attempt to write the remaining unsent part
            $bytes_written += (int) @fwrite($write_stream, substr($serialized_message, $bytes_written));

            if ($bytes_written < $bytes_to_write) {
                // wait a bit
                usleep(500_000);
            }
        }

        fclose($write_stream);

        // Children exit after completing their work
        exit(self::EXIT_SUCCESS);
    }

    /**
     * Prepare the socket pair to be used in a parent process and
     * return the stream the parent will use to read results.
     *
     * @param resource[] $sockets the socket pair for IPC
     * @return resource
     */
    private static function streamForParent(array $sockets)
    {
        [$for_read, $for_write] = $sockets;

        // The parent will not use the write channel, so it
        // must be closed to prevent deadlock.
        fclose($for_write);

        // stream_select will be used to read multiple streams, so these
        // must be set to non-blocking mode.
        if (!stream_set_blocking($for_read, false)) {
            error_log('unable to set read stream to non-blocking');
            exit(self::EXIT_FAILURE);
        }

        return $for_read;
    }

    /**
     * Prepare the socket pair to be used in a child process and return
     * the stream the child will use to write results.
     *
     * @param resource[] $sockets the socket pair for IPC
     * @return resource
     */
    private static function streamForChild(array $sockets)
    {
        [$for_read, $for_write] = $sockets;

        // The while will not use the read channel, so it must
        // be closed to prevent deadlock.
        fclose($for_read);

        return $for_write;
    }

    private function killAllChildren(): void
    {
        foreach ($this->child_pid_list as $child_pid) {
            /**
             * SIGTERM does not exist on windows
             *
             * @psalm-suppress UnusedPsalmSuppress
             * @psalm-suppress UndefinedConstant
             * @psalm-suppress MixedArgument
             */
            posix_kill($child_pid, SIGTERM);
        }
    }

    /**
     * Read the results that each child process has serialized on their write streams.
     * The results are returned in an array, one for each worker. The order of the results
     * is not maintained.
     *
     * @psalm-suppress MixedAssignment
     * @return list<mixed>
     */
    private function readResultsFromChildren(): array
    {
        // Create an array of all active streams, indexed by
        // resource id.
        $streams = [];
        foreach ($this->read_streams as $stream) {
            $streams[(int)$stream] = $stream;
        }

        // Create an array for the content received on each stream,
        // indexed by resource id.
        $content = array_fill_keys(array_keys($streams), '');

        $terminationMessages = [];
        $done = [];

        // Read the data off of all the stream.
        while (count($streams) > 0) {
            $needs_read = array_values($streams);
            $needs_write = null;
            $needs_except = null;

            // Wait for data on at least one stream.
            $num = @stream_select($needs_read, $needs_write, $needs_except, null /* no timeout */);
            if ($num === false) {
                $err = error_get_last();

                // stream_select returns false when the `select` system call is interrupted by an incoming signal
                if (isset($err['message']) && stripos($err['message'], 'interrupted system call') === false) {
                    error_log('unable to select on read stream');
                    exit(self::EXIT_FAILURE);
                }

                continue;
            }

            // For each stream that was ready, read the content.
            foreach ($needs_read as $file) {
                $buffer = fread($file, 1_024);
                if ($buffer !== false) {
                    $content[(int)$file] .= $buffer;
                }

                if (str_contains($buffer, "\n")) {
                    $serialized_messages = explode("\n", $content[(int)$file]);
                    $content[(int)$file] = array_pop($serialized_messages);

                    foreach ($serialized_messages as $serialized_message) {
                        if ($this->config->use_igbinary) {
                            $message = igbinary_unserialize((string) base64_decode($serialized_message, true));
                        } else {
                            $message = unserialize((string) base64_decode($serialized_message, true));
                        }

                        if ($message instanceof ForkProcessDoneMessage) {
                            $terminationMessages[] = $message->data;
                        } elseif ($message instanceof ForkTaskDoneMessage) {
                            $done[(int)$file] = true;
                            if ($this->task_done_closure !== null) {
                                ($this->task_done_closure)($message->data);
                            }
                        } elseif ($message instanceof ForkProcessErrorMessage) {
                            $this->killAllChildren();
                            throw new Exception($message->message);
                        } else {
                            $this->killAllChildren();
                            throw new Exception('Child should return ForkMessage - response type=' . gettype($message));
                        }
                    }
                }

                // If the stream has closed, stop trying to select on it.
                if (feof($file)) {
                    if ($content[(int)$file] !== '' || !isset($done[(int)$file])) {
                        $this->killAllChildren();
                        throw new Exception('Child did not send full message before closing the connection');
                    }

                    fclose($file);
                    unset($streams[(int)$file]);
                }
            }
        }

        return $terminationMessages;
    }

    /**
     * Wait for all child processes to complete
     *
     * @return list<mixed>
     */
    public function wait(): array
    {
        $ignore_return_code = false;
        try {
            // Read all the streams from child processes into an array.
            $content = $this->readResultsFromChildren();
        } catch (Throwable $e) {
            // If children were killed because one of them threw an exception we don't care about return codes.
            $ignore_return_code = true;
            // PHP guarantees finally is run even after throwing
            throw $e;
        } finally {
            // Wait for all children to return
            foreach ($this->child_pid_list as $child_pid) {
                $process_lookup = posix_kill($child_pid, 0);

                $status = 0;

                if ($process_lookup) {
                    /**
                     * SIGALRM does not exist on windows
                     *
                     * @psalm-suppress UnusedPsalmSuppress
                     * @psalm-suppress UndefinedConstant
                     * @psalm-suppress MixedArgument
                     */
                    posix_kill($child_pid, SIGALRM);

                    if (pcntl_waitpid($child_pid, $status) < 0) {
                        error_log(posix_strerror(posix_get_last_error()));
                    }
                }

                // Check to see if the child died a graceful death
                if (!$ignore_return_code && pcntl_wifsignaled($status)) {
                    $return_code = pcntl_wexitstatus($status);
                    $term_sig = pcntl_wtermsig($status);

                    /**
                     * SIGALRM does not exist on windows
                     *
                     * @psalm-suppress UnusedPsalmSuppress
                     * @psalm-suppress UndefinedConstant
                     */
                    if ($term_sig !== SIGALRM) {
                        $this->killAllChildren();
                        throw new Exception("Child terminated with return code $return_code and signal $term_sig");
                    }
                }
            }
        }

        return $content;
    }
}
