<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function byEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function byGithubId(string $id): ?User
    {
        return User::query()->where('github_id', $id)->first();
    }

    public function link(int $userId, string $githubId): User
    {
        $user = User::query()->lockForUpdate()->findOrFail($userId);
        if ($user->github_id !== null && (string) $user->github_id !== $githubId) {
            throw new \DomainException('identity_in_use');
        }
        $user->update(['github_id' => $githubId]);

        return $user;
    }
}
