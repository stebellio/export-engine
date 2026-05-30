<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_id',
        'player_id',
        'name',
        'value',
        'occurred_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
