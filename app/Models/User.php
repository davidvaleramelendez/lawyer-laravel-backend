<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Yadahan\AuthenticationLog\AuthenticationLogable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, AuthenticationLogable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    public $timestamps = true;

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role_id' => 'integer',
    ];

    /*this is for rolebase*/
    public function role()
    {
        return $this->hasOne('App\Models\Role', 'role_id', 'role_id');
    }

    /*this is for rolebase*/
    public function permission()
    {
        return $this->hasMany(Permissions::class, 'user_id', 'id');
    }

    public function hasRole($roles)
    {
        $this->have_role = $this->getUserRole();

        if (empty($this->have_role->RoleName)) {
            return false;
        }
        // Check if the user is a root account
        if ($this->have_role->RoleName == 'Root') {
            return true;
        }

        if (is_array($roles)) {
            foreach ($roles as $need_role) {
                if ($this->checkIfUserHasRole($need_role)) {
                    return true;
                }
            }
        } else {
            return $this->checkIfUserHasRole($roles);
        }
        return false;
    }

    private function getUserRole()
    {
        return $this->role()->getResults();
    }

    private function checkIfUserHasRole($need_role)
    {
        return (strtolower($need_role) == strtolower($this->have_role->RoleName)) ? true : false;
    }

    public function notifications()
    {
        return $this->hasMany(CustomNotification::class, 'notifiable_id', 'id');
    }

    public function imap()
    {
        return $this->hasOne(ContactImap::class);
    }

    /*end role check*/

    // ...

    public function googleAccounts()
    {
        return $this->hasMany(GoogleAccount::class);
    }

    public function events()
    {
        // Or use: https://github.com/staudenmeir/eloquent-has-many-deep
        return Event::whereHas('calendar', function ($calendarQuery) {
            $calendarQuery->whereHas('googleAccount', function ($accountQuery) {
                $accountQuery->whereHas('user', function ($userQuery) {
                    $userQuery->where('id', $this->id);
                });
            });
        });
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function scopeOfSearch($query, $q)
    {
        if ($q) {
            $query->orWhere('name', 'LIKE', '%' . $q . '%');
        }
        return $query;
    }

    public function scopeOfSort($query, $sort = [])
    {
        if (!empty($sort)) {
            foreach ($sort as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        } else {
            $query->orderBy('id');
        }

        return $query;
    }
}
