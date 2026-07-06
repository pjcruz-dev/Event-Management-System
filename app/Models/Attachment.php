<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'attachable_type',
        'attachable_id',
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
