<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Get the appointments for the user.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get normalized allowlisted admin emails configured via auth.admin_emails.
     *
     * @return list<string>
     */
    public static function configuredAdminEmails(): array
    {
        $configured = config('auth.admin_emails', []);

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        if (! is_array($configured)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $email): string => mb_strtolower(trim((string) $email)),
            $configured
        ), static fn (string $email): bool => $email !== '')));
    }

    /**
     * Scope query to Showroom Admin accounts.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeAdmins(Builder $query): Builder
    {
        $extraEmails = static::configuredAdminEmails();

        return $query->where(function (Builder $builder) use ($extraEmails): void {
            $builder->whereRaw('LOWER(email) LIKE ?', ['%@bmw.com']);

            if ($extraEmails !== []) {
                $builder->orWhereIn(DB::raw('LOWER(email)'), $extraEmails);
            }
        });
    }

    public function isAdmin(): bool
    {
        $email = mb_strtolower(trim((string) $this->email));

        if ($email === '') {
            return false;
        }

        return str_ends_with($email, '@bmw.com')
            || in_array($email, static::configuredAdminEmails(), true);
    }
}
