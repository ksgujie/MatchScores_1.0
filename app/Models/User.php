<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
//use Illuminate\Database\Eloquent\Model
use App\Models\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
//    protected $fillable = ['*'];
    protected $guarded = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * 返回所有单位名称
     * @return array
     */
    public static function schools()
    {
        $users = self::all();
        $schools = [];
        foreach ($users as $user) {
            $schools[$user->单位]='';
        }
        return array_keys($schools);
    }

}
