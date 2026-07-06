<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\ExhibitorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Exhibitor extends Model
{
    /** @use HasFactory<ExhibitorFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'event_id',
        'name',
        'description',
        'logo_path',
        'website_url',
        'contact_email',
        'materials',
    ];

    protected function casts(): array
    {
        return [
            'materials' => 'array',
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

    public function booth(): HasOne
    {
        return $this->hasOne(Booth::class);
    }

    public function booths(): HasMany
    {
        return $this->hasMany(Booth::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ExhibitorContact::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(ExhibitorLead::class);
    }
}
