<?php
if (!function_exists("random_bytes")) {
    /**
     * @param int $bytes
     * @return void
     */
    function random_bytes($bytes)
    {
        throw new \Exception("bad");
    }
}

if (!function_exists("new_random_bytes")) {
    /**
     * @param int $bytes
     * @return int
     */
    function new_random_bytes($bytes)
    {
        return 5;
    }
}
