<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Psr\Http\Message\UploadedFileInterface;
use Stringable;

class UserProfileService
{
    /**
     * Create the profile service with its persistence and image dependencies.
     *
     * @param \App\Model\Table\UsersTable $usersTable The users table.
     * @param \App\Service\ImageStorageService $imageStorageService The image storage service.
     */
    public function __construct(
        private UsersTable $usersTable,
        private ImageStorageService $imageStorageService,
    ) {
    }

    /**
     * Find an active user by username.
     *
     * @param string $username The username to find.
     * @return \App\Model\Entity\User
     */
    public function findActiveByUsername(string $username): User
    {
        $user = $this->usersTable->find('active')
            ->where(['Users.username' => $username])
            ->first();
        if (!$user instanceof User) {
            throw new RecordNotFoundException('User profile not found.');
        }

        return $user;
    }

    /**
     * Find a user by ID.
     *
     * @param int $id The user ID.
     * @return \App\Model\Entity\User
     */
    public function getById(int $id): User
    {
        return $this->usersTable->get($id);
    }

    /**
     * Patch editable profile fields and normalize social links for JSON storage.
     *
     * @param \App\Model\Entity\User $user The user being edited.
     * @param array<string, mixed> $data
     * @param list<string> $allowedFields
     * @return \App\Model\Entity\User
     */
    public function patchProfile(User $user, array $data, array $allowedFields): User
    {
        if (array_key_exists('social_links', $data)) {
            $data['social_links'] = $this->normalizeSocialLinks($data['social_links']);
        }

        return $this->usersTable->patchEntity($user, $data, [
            'fields' => $allowedFields,
        ]);
    }

    /**
     * Normalize either newline-delimited form input or legacy JSON text.
     *
     * @param mixed $value The submitted value.
     * @return string|null
     */
    public function normalizeSocialLinks(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $values = $decoded;
            } else {
                $values = preg_split("/\r\n|\n|\r/", $value) ?: [];
            }
        } elseif (is_array($value)) {
            $values = $value;
        } else {
            return null;
        }

        $links = [];
        foreach ($values as $item) {
            if (!is_scalar($item) && !$item instanceof Stringable) {
                continue;
            }

            $link = trim((string)$item);
            if ($link !== '') {
                $links[] = $link;
            }
        }

        return $links === []
            ? null
            : json_encode($links, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Upload and attach a profile image, returning a user-facing error on failure.
     *
     * @param \App\Model\Entity\User $user The user receiving the image.
     * @param \Psr\Http\Message\UploadedFileInterface $file The uploaded image.
     * @param int|null $ownerId The owner ID used for upload authorization.
     * @return string|null The user-facing error, if the upload failed.
     */
    public function uploadProfileImage(User $user, UploadedFileInterface $file, ?int $ownerId): ?string
    {
        $result = $this->imageStorageService->upload($file, [], [], $ownerId);
        $imageId = $result['image']['id'] ?? null;
        if (is_numeric($imageId) && (int)$imageId > 0) {
            $user->profile_image_id = (int)$imageId;

            return null;
        }

        return $this->imageStorageService->getLastError() ?? 'Failed to process avatar image.';
    }

    /**
     * Save the user's profile.
     *
     * @param \App\Model\Entity\User $user The user to save.
     * @return \App\Model\Entity\User|false
     */
    public function saveProfile(User $user): User|false
    {
        $saved = $this->usersTable->save($user);

        return $saved instanceof User ? $saved : false;
    }
}
