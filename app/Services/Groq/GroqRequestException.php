<?php

namespace App\Services\Groq;

use RuntimeException;

class GroqRequestException extends RuntimeException
{
    public function __construct(string $message, public readonly int $statusCode)
    {
        parent::__construct($message);
    }
}
