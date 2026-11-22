<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * A temporary signed link (7 days) on which this user sets a new password.
     *
     * Relative signature (the route uses `signed:relative`) so the link works on
     * any host/port the app is reached through. It also carries a fingerprint of
     * the current password hash, which makes the link single-use: once a
     * password is set through it — or changed any other way — the fingerprint
     * no longer matches and the link is dead, even inside its 7 days. Without
     * that, a link left in a chat or browser history stays a working
     * admin-password reset for a week.
     */
    public function setupLink(): string
    {
        return url(URL::temporarySignedRoute(
            'admin.setup',
            now()->addDays(7),
            ['user' => $this->id, 'v' => $this->setupFingerprint()],
            absolute: false,
        ));
    }

    /** Changes whenever the password does; never reveals the hash itself. */
    public function setupFingerprint(): string
    {
        return substr(hash_hmac('sha256', 'admin-setup|'.$this->getKey().'|'.$this->getAuthPassword(), (string) config('app.key')), 0, 32);
    }
}
