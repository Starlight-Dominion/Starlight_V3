<?php
declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Models\Dominion;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use DateTime;

class SettingsService
{
    private const HANDLE_CHANGE_COST = 1000000;
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_DIMENSION = 500;
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function updateIdentity(int $userId, string $newHandle, string $newEmail): array
    {
        $user = User::findOrFail($userId);
        $dominion = $user->dominion;

        if ($user->username !== $newHandle) {
            if (User::where('username', $newHandle)->exists()) {
                throw new Exception("Identity handle '{$newHandle}' is already claimed.");
            }
            
            if ($dominion->credits < self::HANDLE_CHANGE_COST) {
                throw new Exception("Insufficient credits for handle re-assignment. Required: 1,000,000 CP.");
            }

            $dominion->credits -= self::HANDLE_CHANGE_COST;
            $user->username = $newHandle;
            $user->handle_last_changed = new DateTime();
        }

        if ($user->email !== $newEmail) {
            if (User::where('email', $newEmail)->exists()) {
                throw new Exception("Comms frequency '{$newEmail}' is already monitored.");
            }
            $user->email = $newEmail;
        }

        Capsule::transaction(function() use ($user, $dominion) {
            $user->save();
            $dominion->save();
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

        $user->password = $new;
        $user->save();

        return ['success' => true, 'message' => "Encryption ciphers rotated successfully."];
    }

    /**
     * Process binary image upload with strict dimension and size constraints.
     */
    public function processAvatarUpload(int $userId, array $file): array
    {
        $user = User::findOrFail($userId);

        // 1. Basic Upload Validation
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Uplink failed: File transmission error.");
        }

        // 2. Size Constraint (10MB)
        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new Exception("Transmission rejected: File exceeds 10MB limit.");
        }

        // 3. Mime-Type Verification
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            throw new Exception("Incompatible format. Use JPEG, PNG, or WEBP.");
        }

        // 4. Dimension Constraint (500x500)
        $dimensions = getimagesize($file['tmp_name']);
        if (!$dimensions || $dimensions[0] > self::MAX_DIMENSION || $dimensions[1] > self::MAX_DIMENSION) {
            throw new Exception("Resolution error: Sigil must not exceed 500x500px.");
        }

        // 5. Secure File Persistence
        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'sigil_' . $userId . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("System error: Failed to persist sigil to sector storage.");
        }

        // 6. Cleanup previous avatar if exists
        if ($user->avatar_path && file_exists(__DIR__ . '/../../public' . $user->avatar_path)) {
            @unlink(__DIR__ . '/../../public' . $user->avatar_path);
        }

        // 7. Update User Profile
        $dbPath = '/uploads/avatars/' . $fileName;
        $user->avatar_path = $dbPath;
        $user->save();

        return [
            'success' => true, 
            'message' => "Visual sigil updated.",
            'path' => $dbPath
        ];
    }

    public function toggleStasis(int $userId): array
    {
        $user = User::findOrFail($userId);
        $now = new DateTime();

        if ($user->stasis_until && new DateTime($user->stasis_until->format('Y-m-d H:i:s')) > $now) {
            $user->stasis_until = null;
            $user->save();
            return ['success' => true, 'message' => "Stasis interrupted. Life support at 100%."];
        }

        $future = (new DateTime())->modify('+14 days');
        $user->stasis_until = $future;
        $user->save();

        return ['success' => true, 'message' => "Stasis engaged until " . $future->format('Y-m-d H:i')];
    }
}
