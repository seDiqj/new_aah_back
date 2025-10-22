<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;

    // protected $guard_name = 'web';

        /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'title',
        'email',
        'password',
        'email_verified_at',
        "photo_path",
        "department_id",
        "status",
        "created_by",
        "updated_by",
        "deleted_by",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at'
    ];



    protected $appends = ["created_date", "updated_date"];

    public function getCreatedDateAttribute() {

        return $this->created_at->format("Y-m-d");

    }

    public function getUpdatedDateAttribute() {

        return $this->updated_at->format("Y-m-d");
        
    }

    protected function runSoftDelete()
    {
        $this->forceFill([
            $this->getDeletedAtColumn() => $this->freshTimestamp(),
            "deleted_by" => Auth::id()
        ]);

        $this->save();

        $this->fireModelEvent("trashed", false);
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
        ];
    }

    public function rolesRelation()
    {
        return $this->roles(); 
    }

    public function permissionsRelation()
    {
        return $this->permissions();
    }

}
