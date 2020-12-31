<?php

class PdoStatement {
    /**
     * @psalm-taint-sink callable $class
     *
     * @template T
     * @param class-string<T> $class
     * @param array $ctorArgs
     * @return false|T
     */
    public function fetchObject($class = "stdclass", array $ctorArgs = array()) {}
}
