<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Models\Kingdom;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use DateTime;

class SettingsService
{
    private const HANDLE_CHANGE_COST = 1000000;

    public function updateIdentity(int $userId, string $newHandle, string $newEmail): array
    {
        $user = User::findOrFail($userId);
        $kingdom = $user->kingdom;

        // Validate Handle Unique
        if ($user->username !== $newHandle) {
            if (User::where('username', $newHandle)->exists()) {
                throw new Exception("Identity handle '{$newHandle}' is already claimed.");
            }
            
            if ($kingdom->gold < self::HANDLE_CHANGE_COST) {
                throw new Exception("Insufficient credits for handle re-assignment. Required: 1,000,000 CP.");
            }

            $kingdom->gold -= self::HANDLE_CHANGE_COST;
            $user->username = $newHandle;
            $user->handle_last_changed = new DateTime();
        }

        // Validate Email Unique
        if ($user->email !== $newEmail) {
            if (User::where('email', $newEmail)->exists()) {
                throw new Exception("Comms frequency '{$newEmail}' is already monitored.");
            }
            $user->email = $newEmail;
        }

        Capsule::transaction(function() use ($user, $kingdom) {
            $user->save();
            $kingdom->save();
        });

        return ['success' => true, 'message' => "Identity profiles synchronized."];
    }

    public function updateCipher(int $userId, string $current, string $new, string $confirm): array
    {
        $user = User::findOrFail($userId);

        if (!password_verify($current, $user->password)) {
            throw new Exception("Current authorization cipher is incorrect.");
        }

        if ($new !== $confirm) {
            throw new Exception("New cipher verification mismatch.");
        }

        if (strlen($new) < 8) {
            throw new Exception("Cipher complexity insufficient. Minimum 8 characters.");
        }

        $user->password = $new; // Mutator handles hashing
        $user->save();

        return ['success' => true, 'message' => "Encryption ciphers rotated successfully."];
    }

    public function updateAvatar(int $userId, string $path): array
    {
        $user = User::findOrFail($userId);
        $user->avatar_path = filter_var($path, FILTER_SANITIZE_URL);
        $user->save();

        return ['success' => true, 'message' => "Avatar uplink established."];
    }

    public function toggleStasis(int $userId): array
    {
        $user = User::findOrFail($userId);
        $now = new DateTime();

        if ($user->stasis_until && new DateTime($user->stasis_until->format('Y-m-d H:i:s')) > $now) {
            // Cancel Stasis
            $user->stasis_until = null;
            $user->save();
            return ['success' => true, 'message' => "Stasis interrupted. Life support at 100%."];
        }

        // Engage Stasis (14 days)
        $future = (new DateTime())->modify('+14 days');
        $user->stasis_until = $future;
        $user->save();

        return ['success' => true, 'message' => "Stasis engaged until " . $future->format('Y-m-d H:i')];
    }
}