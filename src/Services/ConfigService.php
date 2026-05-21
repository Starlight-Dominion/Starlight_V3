<?php
declare(strict_types=1);

namespace sdo\Services;

use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class ConfigService
{
    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Capsule::table('game_settings')->where('setting_key', $key)->first();
        
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
        Capsule::table('game_settings')->updateOrInsert(
            ['setting_key' => $key],
            ['setting_value' => (string)$value, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }

    /**
     * Get all settings.
     */
    public function getAll(): array
    {
        return Capsule::table('game_settings')->get()->toArray();
    }
}
