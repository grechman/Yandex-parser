<?php

namespace App\Exceptions;

use Exception;

class ParserException extends Exception
{
    public function __construct(string $message = "", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class ApiException extends ParserException
{
    public function __construct(string $message = "API error", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class StorageException extends ParserException
{
    public function __construct(string $message = "Storage error", int $code = 0, Exception $previous)
    {
        parent::__construct($message, $code, $previous);
    }
}

class DownloadException extends ParserException
{
    public function __construct(string $message = "Download error", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class ValidationException extends ParserException
{
    public function __construct(string $message = "Validation error", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}