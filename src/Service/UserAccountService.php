<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;

class UserAccountService
{
    /**
     * Create the user account service.
     *
     * @param \App\Model\Table\UsersTable $usersTable The users table.
     */
    public function __construct(private UsersTable $usersTable)
    {
    }

    /**
     * Create a user account from submitted data.
     *
     * @param array<string,mixed> $data The account data.
     * @return array{user:\App\Model\Entity\User,saved:bool}
     */
    public function create(array $data): array
    {
        $user = $this->usersTable->newEmptyEntity();
        $user = $this->usersTable->patchEntity($user, $data);
        $saved = $this->usersTable->save($user);

        return ['user' => $user, 'saved' => $saved instanceof User];
    }

    /**
     * Update an existing user account.
     *
     * @param string|int $id The user ID.
     * @param array<string,mixed> $data The account data.
     * @return array{user:\App\Model\Entity\User,saved:bool}
     */
    public function update(string|int $id, array $data): array
    {
        $user = $this->usersTable->get($id);
        $user = $this->usersTable->patchEntity($user, $data);
        $saved = $this->usersTable->save($user);

        return ['user' => $user, 'saved' => $saved instanceof User];
    }

    /**
     * Activate the specified user.
     *
     * @param string|int $id The user ID.
     * @return bool
     */
    public function approve(string|int $id): bool
    {
        $user = $this->usersTable->get($id);
        $user->status = 'active';

        return $this->usersTable->save($user) instanceof User;
    }

    /**
     * Delete the specified user.
     *
     * @param string|int $id The user ID.
     * @return bool
     */
    public function delete(string|int $id): bool
    {
        $user = $this->usersTable->get($id);

        return $this->usersTable->delete($user);
    }

    /**
     * @param array<int|string,mixed> $ids
     */
    public function bulkActivate(array $ids): int
    {
        return $this->usersTable->updateAll(['status' => 'active'], ['id IN' => $ids]);
    }

    /**
     * @param array<int|string,mixed> $ids
     */
    public function bulkDelete(array $ids): int
    {
        return $this->usersTable->deleteAll(['id IN' => $ids]);
    }
}
