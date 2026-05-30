<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_id',
        'email',
        'registered_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }
}
