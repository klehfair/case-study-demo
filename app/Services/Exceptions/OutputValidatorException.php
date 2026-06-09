<?php

namespace App\Services\Exceptions;

class OutputValidatorException extends \RuntimeException
{
    public function __construct(string $intent, array $missing)
    {
        parent::__construct(
            "Agent output for intent '{$intent}' missing required fields: " . implode(', ', $missing)
        );
    }
}
