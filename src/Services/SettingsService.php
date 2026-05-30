<?php

declare(strict_types=1);

namespace sdo\Services;

use sdo\Models\User;
use sdo\Dto\Settings\UpdateIdentityRequest;
use sdo\Dto\Settings\UpdateCipherRequest;
use sdo\Repositories\Interfaces\UserRepositoryInterface;
use sdo\Repositories\Interfaces\DominionRepositoryInterface;
use sdo\Infrastructure\TransactionManager;
use Exception;
use DateTime;

class SettingsService
{
    private const HANDLE_CHANGE_COST = 1000000;
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_DIMENSION = 500;
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private DominionRepositoryInterface $dominionRepository,
        private TransactionManager $transactionManager
    ) {}

    public function updateIdentity(int $userId, UpdateIdentityRequest $request): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) throw new Exception("Commander not found.");

        $dominion = $this->dominionRepository->findByUserId($userId);
        if (!$dominion) throw new Exception("Dominion not found.");

        $userChanges = [];
        $dominionChanges = [];

        if ($user->username !== $request->username) {
            if ($this->userRepository->findByUsername($request->username)) {
                throw new Exception("Identity handle '{$request->username}' is already claimed.");
            }
            
            if ($dominion->credits < self::HANDLE_CHANGE_COST) {
                throw new Exception("Insufficient credits for handle re-assignment. Required: 1,000,000 CP.");
            }

            $dominionChanges['credits'] = $dominion->credits - self::HANDLE_CHANGE_COST;
            $userChanges['username'] = $request->username;
            $userChanges['handle_last_changed'] = new DateTime();
        }

        if ($user->email !== $request->email) {
            if ($this->userRepository->findByEmail($request->email)) {
                throw new Exception("Comms frequency '{$request->email}' is already monitored.");
            }
            $userChanges['email'] = $request->email;
        }

        if (!empty($userChanges) || !empty($dominionChanges)) {
            $this->transactionManager->transaction(function() use ($userId, $userChanges, $dominion, $dominionChanges) {
                if (!empty($userChanges)) {
                    $this->userRepository->update($userId, $userChanges);
                }
                if (!empty($dominionChanges)) {
                    $this->dominionRepository->update((int)$dominion->id, $dominionChanges);
                }
            });
        }

        return ['success' => true, 'message' => "Identity profiles synchronized."];
    }

    public function updateCipher(int $userId, UpdateCipherRequest $request): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) throw new Exception("Commander not found.");

        if (!password_verify($request->current_password, $user->password)) {
            throw new Exception("Current authorization cipher is incorrect.");
        }

        if ($request->new_password !== $request->confirm_password) {
            throw new Exception("New cipher verification mismatch.");
        }

        $this->userRepository->update($userId, ['password' => $request->new_password]);

        return ['success' => true, 'message' => "Encryption ciphers rotated successfully."];
    }

    /**
     * Process binary image upload with strict dimension and size constraints.
     */
    public function processAvatarUpload(int $userId, array $file): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) throw new Exception("Commander not found.");

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
        $this->userRepository->update($userId, ['avatar_path' => $dbPath]);

        return [
            'success' => true, 
            'message' => "Visual sigil updated.",
            'path' => $dbPath
        ];
    }

    public function toggleStasis(int $userId): array
    {
        $user = $this->userRepository->findById($userId);
        if (!$user) throw new Exception("Commander not found.");

        $now = new DateTime();

        if ($user->stasis_until && $user->stasis_until > $now) {
            $this->userRepository->update($userId, ['stasis_until' => null]);
            return ['success' => true, 'message' => "Stasis interrupted. Life support at 100%."];
        }

        $future = (new DateTime())->modify('+14 days');
        $this->userRepository->update($userId, ['stasis_until' => $future]);

        return ['success' => true, 'message' => "Stasis engaged until " . $future->format('Y-m-d H:i')];
    }
}
