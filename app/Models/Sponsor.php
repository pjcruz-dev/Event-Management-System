<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SponsorTier;
use App\Traits\BelongsToTenant;
use Database\Factories\SponsorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sponsor extends Model
{
    /** @use HasFactory<SponsorFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'event_id',
        'name',
        'logo_path',
        'website_url',
        'tier',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tier' => SponsorTier::class,
            'sort_order' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
