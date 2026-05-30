<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'version_id',
        'status',
        'format',
        'config',
        'file_path',
        'error_message',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }
}
