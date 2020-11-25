<?php

class PdoStatement {
    /**
     * @template T
     * @param class-string<T> $class
     * @return false|T
     */
    public function fetchObject($class = "stdclass") {}
}
