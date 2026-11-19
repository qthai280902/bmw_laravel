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

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SALES = 'sales';

    public const ROLE_MARKETING = 'marketing';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'email_verified_at',
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

    /**
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_ADMIN => 'Quản Lý',
            self::ROLE_SALES => 'Tư Vấn Bán Hàng',
            self::ROLE_MARKETING => 'Marketing',
        ];
    }

    /**
     * @return list<string>
     */
    public static function roleValues(): array
    {
        return array_keys(self::roles());
    }

    public function normalizedRole(): string
    {
        $role = mb_strtolower(trim((string) $this->role));

        return in_array($role, self::roleValues(), true) ? $role : self::ROLE_ADMIN;
    }

    public function roleLabel(): string
    {
        return self::roles()[$this->normalizedRole()] ?? 'Quản Lý';
    }

    /**
     * Scope query to Showroom Admin and Sales accounts for CRM alerts & HOT LEAD emails.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeSalesAndAdmins(Builder $query): Builder
    {
        return $query->admins()->whereIn('role', [self::ROLE_ADMIN, self::ROLE_SALES]);
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

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && $this->normalizedRole() === self::ROLE_ADMIN;
    }

    public function isSales(): bool
    {
        return $this->isAdmin() && $this->normalizedRole() === self::ROLE_SALES;
    }

    public function isMarketing(): bool
    {
        return $this->isAdmin() && $this->normalizedRole() === self::ROLE_MARKETING;
    }

    public function canAccessCrm(): bool
    {
        return $this->isSuperAdmin() || $this->isSales();
    }

    public function canAccessContent(): bool
    {
        return $this->isSuperAdmin() || $this->isMarketing();
    }

    public function canManageSystem(): bool
    {
        return $this->isSuperAdmin();
    }
}
