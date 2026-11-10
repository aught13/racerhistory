<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Model\Entity\User;
use App\Service\UserProfileService;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Psr\Http\Message\UploadedFileInterface;

class UserProfileComponent extends Component
{
    /**
     * Create the component with its profile service.
     *
     * @param \Cake\Controller\ComponentRegistry $registry The component registry.
     * @param \App\Service\UserProfileService $userProfileService The user profile service.
     * @param array<string,mixed> $config The component configuration.
     */
    public function __construct(
        ComponentRegistry $registry,
        private UserProfileService $userProfileService,
        array $config = [],
    ) {
        parent::__construct($registry, $config);
    }

    /**
     * Find an active user by username.
     *
     * @param string $username The username to find.
     * @return \App\Model\Entity\User
     */
    public function findActiveByUsername(string $username): User
    {
        return $this->userProfileService->findActiveByUsername($username);
    }

    /**
     * Find a user by ID.
     *
     * @param int $id The user ID.
     * @return \App\Model\Entity\User
     */
    public function getById(int $id): User
    {
        return $this->userProfileService->getById($id);
    }

    /**
     * Patch the editable profile fields.
     *
     * @param \App\Model\Entity\User $user The user being edited.
     * @param array<string,mixed> $data The submitted profile data.
     * @param list<string> $allowedFields The fields that may be changed.
     * @return \App\Model\Entity\User
     */
    public function patchProfile(User $user, array $data, array $allowedFields): User
    {
        return $this->userProfileService->patchProfile($user, $data, $allowedFields);
    }

    /**
     * Normalize social-link input for storage.
     *
     * @param mixed $value The submitted social-link value.
     * @return string|null
     */
    public function normalizeSocialLinks(mixed $value): ?string
    {
        return $this->userProfileService->normalizeSocialLinks($value);
    }

    /**
     * Upload an image and attach it to the profile.
     *
     * @param \App\Model\Entity\User $user The user receiving the image.
     * @param \Psr\Http\Message\UploadedFileInterface $file The uploaded image.
     * @param int|null $ownerId The owner ID used for upload authorization.
     * @return string|null The user-facing error, if the upload failed.
     */
    public function uploadProfileImage(User $user, UploadedFileInterface $file, ?int $ownerId): ?string
    {
        return $this->userProfileService->uploadProfileImage($user, $file, $ownerId);
    }

    /**
     * Save the user's profile.
     *
     * @param \App\Model\Entity\User $user The user to save.
     * @return \App\Model\Entity\User|false
     */
    public function saveProfile(User $user): User|false
    {
        return $this->userProfileService->saveProfile($user);
    }
}
