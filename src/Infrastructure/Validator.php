<?php

namespace sdo\Infrastructure;

class Validator
{
    protected array $data;
    protected array $rules;
    protected array $errors = [];
    protected array $customMessages = [];

    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }

    public static function make(array $data, array $rules, array $customMessages = []): static
    {
        return new static($data, $rules, $customMessages);
    }

    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleSet) {
            $ruleArray = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;

            foreach ($ruleArray as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return !$this->validate();
    }

    public function passes(): bool
    {
        return $this->validate();
    }

    public function validated(): array
    {
        if ($this->fails()) {
            throw new \RuntimeException('Validation failed. Check errors() for details.');
        }
        return array_intersect_key($this->data, $this->rules);
    }

    protected function applyRule(string $field, string $rule): void
    {
        $value = $this->getValue($field);

        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $this->addError($field, "The {$field} must be at least {$min} characters.");
            }
            return;
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                $this->addError($field, "The {$field} must not exceed {$max} characters.");
            }
            return;
        }

        if (str_starts_with($rule, 'in:')) {
            $allowed = explode(',', substr($rule, 3));
            if (!in_array($value, $allowed, true)) {
                $this->addError($field, "The {$field} must be one of: " . implode(', ', $allowed) . ".");
            }
            return;
        }

        if (str_starts_with($rule, 'numeric:')) {
            $parts = explode(',', substr($rule, 8));
            $min = (int) ($parts[0] ?? PHP_INT_MIN);
            $max = (int) ($parts[1] ?? PHP_INT_MAX);
            if ($value < $min || $value > $max) {
                $this->addError($field, "The {$field} must be between {$min} and {$max}.");
            }
            return;
        }

        match ($rule) {
            'required' => $this->requireField($field),
            'email' => $this->validateEmail($field, $value),
            'numeric' => $this->validateNumeric($field, $value),
            'alpha' => $this->validateAlpha($field, $value),
            'alpha_num' => $this->validateAlphaNum($field, $value),
            'confirmed' => $this->validateConfirmed($field, $value),
            default => null,
        };
    }

    protected function requireField(string $field): void
    {
        $value = $this->getValue($field);
        if ($value === null || $value === '') {
            $this->addError($field, "The {$field} field is required.");
        }
    }

    protected function validateEmail(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$field} must be a valid email address.");
        }
    }

    protected function validateNumeric(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, "The {$field} must be a number.");
        }
    }

    protected function validateAlpha(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !ctype_alpha($value)) {
            $this->addError($field, "The {$field} must contain only letters.");
        }
    }

    protected function validateAlphaNum(string $field, mixed $value): void
    {
        if ($value !== null && $value !== '' && !ctype_alnum($value)) {
            $this->addError($field, "The {$field} must contain only letters and numbers.");
        }
    }

    protected function validateConfirmed(string $field, mixed $value): void
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->getValue($confirmationField);
        if ($value !== $confirmationValue) {
            $this->addError($field, "The {$field} confirmation does not match.");
        }
    }

    protected function getValue(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $this->customMessages[$field] ?? $message;
    }
}
