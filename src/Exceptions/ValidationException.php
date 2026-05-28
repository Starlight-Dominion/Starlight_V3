<?php

declare(strict_types=1);

namespace sdo\Exceptions;

use Exception;

class ValidationException extends Exception
{
    public function __construct(
        protected array $errors,
        string $message = 'Validation failed.'
    ) {
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
