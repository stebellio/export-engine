<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Version extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
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

    public function exports(): HasMany
    {
        return $this->hasMany(Export::class);
    }
}
