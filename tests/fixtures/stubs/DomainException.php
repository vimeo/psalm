<?php

class DomainException extends LogicException
{
    /**
     * Construct the exception. Note: The message is NOT binary safe.
     *
     * @see https://php.net/manual/en/exception.construct.php
     *
     * @param int $message [optional] The Exception message to throw
     * @param int $code [optional] The Exception code
     * @param throwable $previous [optional] The previous throwable used for the exception chaining
     *
     * @since 5.1.0
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
    }
}
