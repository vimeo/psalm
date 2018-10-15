<?php
namespace Psalm\Fork;

/**
 * Adapted with relatively few changes from
 * https://github.com/etsy/phan/blob/1ccbe7a43a6151ca7c0759d6c53e2c3686994e53/src/Phan/ForkPool.php
 *
 * Authors: https://github.com/morria, https://github.com/TysonAndre
 *
 * Fork off to n-processes and divide up tasks between
 * each process.
 */
class Pool
{
    const EXIT_SUCCESS = 1;
    const EXIT_FAILURE = 0;

    /** @var int[] */
    private $child_pid_list = [];

    /** @var resource[] */
    private $read_streams = [];

    /** @var bool */
    private $did_have_error = false;

    /**
     * @param array[] $process_task_data_iterator
     * An array of task data items to be divided up among the
     * workers. The size of this is the number of forked processes.
     * @param \Closure $startup_closure
     * A closure to execute upon starting a child
     * @param \Closure $task_closure
     * A method to execute on each task data.
     * This closure must return an array (to be gathered).
     * @param \Closure $shutdown_closure
     * A closure to execute upon shutting down a child
     *
     * @psalm-suppress MixedAssignment
     */
    public function __construct(
        array $process_task_data_iterator,
        \Closure $startup_closure,
        \Closure $task_closure,
        \Closure $shutdown_closure
    ) {
        $pool_size = count($process_task_data_iterator);

        \assert(
            $pool_size > 1,
            'The pool size must be >= 2 to use the fork pool.'
        );

        \assert(
            extension_loaded('pcntl'),
            'The pcntl extension must be loaded in order for Psalm to be able to fork.'
        );

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
        foreach ($task_data_iterator as $i => $task_data) {
            $task_closure($i, $task_data);
        }

        // Execute each child's shutdown closure before
        // exiting the process
        $results = $shutdown_closure();

        // Serialize this child's produced results and send them to the parent.
        fwrite($write_stream, serialize($results ?: []));

        fclose($write_stream);

        // Children exit after completing their work
        exit(self::EXIT_SUCCESS);
    }

    /**
     * Prepare the socket pair to be used in a parent process and
     * return the stream the parent will use to read results.
     *
     * @param resource[] $sockets the socket pair for IPC
     *
     * @return resource
     */
    private static function streamForParent(array $sockets)
    {
        list($for_read, $for_write) = $sockets;

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
     *
     * @return resource
     */
    private static function streamForChild(array $sockets)
    {
        list($for_read, $for_write) = $sockets;

        // The while will not use the read channel, so it must
        // be closed to prevent deadlock.
        fclose($for_read);

        return $for_write;
    }

    /**
     * Read the results that each child process has serialized on their write streams.
     * The results are returned in an array, one for each worker. The order of the results
     * is not maintained.
     *
     * @return array
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     */
    private function readResultsFromChildren()
    {
        // Create an array of all active streams, indexed by
        // resource id.
        $streams = [];
        foreach ($this->read_streams as $stream) {
            $streams[intval($stream)] = $stream;
        }

        // Create an array for the content received on each stream,
        // indexed by resource id.
        $content = array_fill_keys(array_keys($streams), '');

        // Read the data off of all the stream.
        while (count($streams) > 0) {
            $needs_read = array_values($streams);
            $needs_write = null;
            $needs_except = null;

            // Wait for data on at least one stream.
            $num = stream_select($needs_read, $needs_write, $needs_except, null /* no timeout */);
            if ($num === false) {
                error_log('unable to select on read stream');
                exit(self::EXIT_FAILURE);
            }

            // For each stream that was ready, read the content.
            foreach ($needs_read as $file) {
                $buffer = fread($file, 1024);
                if ($buffer) {
                    $content[intval($file)] .= $buffer;
                }

                // If the stream has closed, stop trying to select on it.
                if (feof($file)) {
                    fclose($file);
                    unset($streams[intval($file)]);
                }
            }
        }

        // Unmarshal the content into its original form.
        return array_values(
            array_map(
                /**
                 * @param string $data
                 *
                 * @return array
                 */
                function ($data) {
                    /** @var array */
                    $result = unserialize($data);
                    /** @psalm-suppress RedundantConditionGivenDocblockType */
                    if (!\is_array($result)) {
                        error_log(
                            'Child terminated without returning a serialized array - response type=' . gettype($result)
                        );
                        $this->did_have_error = true;
                    }

                    return $result;
                },
                $content
            )
        );
    }

    /**
     * Wait for all child processes to complete
     *
     * @return array
     */
    public function wait()
    {

        // Read all the streams from child processes into an array.
        $content = $this->readResultsFromChildren();

        // Wait for all children to return
        foreach ($this->child_pid_list as $child_pid) {
            posix_kill($child_pid, SIGALRM);
            if (pcntl_waitpid($child_pid, $status) < 0) {
                error_log(posix_strerror(posix_get_last_error()));
            }

            // Check to see if the child died a graceful death
            if (pcntl_wifsignaled($status)) {
                $return_code = pcntl_wexitstatus($status);
                $term_sig = pcntl_wtermsig($status);

                if ($term_sig !== SIGALRM) {
                    $this->did_have_error = true;
                    error_log("Child terminated with return code $return_code and signal $term_sig");
                }
            }
        }

        return $content;
    }

    /**
     * Returns true if this had an error, e.g. due to memory limits or due to a child process crashing.
     *
     * @return  bool
     *
     * @psalm-suppress PossiblyUnusedMethod because we may in the future
     */
    public function didHaveError()
    {
        return $this->did_have_error;
    }
}
