<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'is_active',
        'system_reserve',
        'remember_token',
        'is_verified',
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
        'id',
    ];

    protected $guard_name = 'api';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });

        static::deleting(function ($model) {
            if ($model->hasRole(\App\Enums\UserRole::OWNER->value)) {
                throw new \Exception('Akun Owner adalah System Reserved dan tidak dapat dihapus.');
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function employee()
    {
        return $this->hasOne(Employee::class)->withTrashed();
    }

    public function team()
    {
        return $this->hasOneThrough(
            Team::class,
            Employee::class,
            'user_id',   // FK di employees
            'id',        // PK di teams
            'id',        // PK di users
            'team_id'    // FK di employees ke teams
        );
    }

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
            'is_verified' => 'boolean',
        ];
    }
}
