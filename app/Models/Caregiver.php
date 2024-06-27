<?php

namespace App\Models;

use App\Http\Resources\Caregiver\CaregiverResource;
use App\Rules\GenderValidateRule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Caregiver extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [
        "id",
        "created_at",
        "updated_at",
    ];

    protected $hidden = [
        "password",
        "remember_token",
        "role",
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function patients()
    {
        return $this->belongsToMany(User::class); // A caregiver has many patients
        /**
         * How to use this relationship:
         * 
         * What is generated sql raw for this relation?
         * > select * from `users` inner join `caregiver_user` on `users`.`id` = `caregiver_user`.`user_id` where `caregiver_user`.`caregiver_id` = ?
         * 
         * What is the name of the pivot table?
         * > caregiver_user
         * 
         * What are the columns in the pivot table?
         * > caregiver_id, user_id
         */
    }

    public function getRoleAttribute()
    {
        return "caregiver";
    }

    public function toArray()
    {
        return new CaregiverResource($this);
    }

    // The Validators for the Caregiver
    public static function validators()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:caregivers,email',
            // checks if the password contains at least one lowercase letter, one uppercase letter, and one number
            'password' => 'required|string|big_password|min:8',
            "date_of_birth" => "sometimes|required|date",
            'phone' => 'required|string|regex:/^01[0-2]{1}[0-9]{8}$/',
            "gender" => ["required", new GenderValidateRule],
            "country" => "sometimes|required|string|max:255",
            'address' => 'sometimes|required|string|max:255',
            'photo' => 'sometimes|required|file',
        ];
    }

    public static function updateValidators()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:caregivers,email',
            // checks if the password contains at least one lowercase letter, one uppercase letter, and one number
            'password' => 'sometimes|required|string|big_password|min:8',
            "date_of_birth" => "sometimes|required|date",
            'phone' => 'sometimes|required|string|regex:/^01[0-2]{1}[0-9]{8}$/',
            "gender" => ["sometimes", 'required', new GenderValidateRule],
            "country" => "sometimes|required|string|max:255",
            'address' => 'sometimes|required|string|max:255',
            'photo' => 'sometimes|required|file',
        ];
    }

    // public function sentMessages()
    // {
    //     return $this->morphMany(Message::class, 'sender');
    // }

    // public function receivedMessages()
    // {
    //     return $this->morphMany(Message::class, 'receiver');
    // }
}
