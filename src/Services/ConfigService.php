<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Repositories\Interfaces\ConfigRepositoryInterface;

class ConfigService
{
    public function __construct(private ConfigRepositoryInterface $configRepository) {}

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->configRepository->find($key);
        
        if (!$setting) {
            return $default;
        }

        $value = $setting->setting_value;

        // Attempt to cast to numeric if applicable
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        return $value;
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, mixed $value): void
    {
        $this->configRepository->updateOrCreate($key, (string)$value);
    }

    /**
     * Get all settings.
     */
    public function getAll(): array
    {
        return $this->configRepository->all()->toArray();
    }
}
