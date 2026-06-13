<?php

namespace App\Models;

use App\Enums\Gender;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasUuids;
    protected $fillable = [
        'clinic_id',
        'name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'gender' => Gender::class, // Using the Enum defined in your structure
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
