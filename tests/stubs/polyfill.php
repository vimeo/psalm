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
