<?php

declare(strict_types=1);

namespace sdo\Infrastructure;

use sdo\Exceptions\ValidationException;
use ReflectionClass;
use ReflectionProperty;

abstract class BaseDto
{
    /**
     * @throws ValidationException
     */
    public function __construct(array $data)
    {
        $validator = Validator::make($data, $this->rules());

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $this->hydrate($validator->validated());
    }

    /**
     * Define validation rules for the DTO.
     */
    abstract protected function rules(): array;

    /**
     * Automatically hydrate public properties from validated data.
     */
    protected function hydrate(array $data): void
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();
            if (array_key_exists($name, $data)) {
                $property->setValue($this, $data[$name]);
            }
        }
    }
}
