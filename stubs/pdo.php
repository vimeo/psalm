<?php

class PdoStatement {
    /**
     * @psalm-taint-sink callable $class
     *
     * @template T
     * @param class-string<T> $class
     * @return false|T
     */
    public function fetchObject($class = "stdclass") {}
}
