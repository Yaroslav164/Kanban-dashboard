<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\User as GithubUser;

class GithubService
{
    public function __construct(private UserRepository $users, private UnitOfWork $work) {}

    public function resolve(GithubUser $profile, ?int $linkTo): User
    {
        $id = (string) $profile->getId();
        if (! ctype_digit($id)) {
            throw new DomainException('provider_unavailable');
        }
        $existing = $this->users->byGithubId($id);
        if ($existing !== null) {
            if ($linkTo !== null && $linkTo !== $existing->id) {
                throw new DomainException('identity_in_use');
            }

            return $existing;
        }

        try {
            if ($linkTo !== null) {
                return $this->work->run(fn ($tx) => $tx->commit($this->users->link($linkTo, $id)));
            }

            $emails = Http::acceptJson()->withToken($profile->token)->timeout(8)
                ->get('https://api.github.com/user/emails')->throw()->json();
            foreach ($emails as $entry) {
                if (($entry['verified'] ?? false) === true && ($entry['primary'] ?? false) === true
                    && filter_var($entry['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
                    $email = mb_strtolower($entry['email']);

                    return $this->work->run(fn ($tx) => $tx->commit($this->users->create([
                        'name' => mb_substr($profile->getName() ?: ($profile->getNickname() ?: $email), 0, 255),
                        'email' => $email, 'github_id' => $id,
                    ])));
                }
            }
            throw new DomainException('email_unverified');
        } catch (UniqueConstraintViolationException) {
            $winner = $this->users->byGithubId($id);
            if ($winner !== null && ($linkTo === null || $winner->id === $linkTo)) {
                return $winner;
            }
            throw new DomainException($linkTo === null ? 'account_exists' : 'identity_in_use');
        }
    }
}
