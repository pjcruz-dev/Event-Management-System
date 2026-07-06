<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\RegistrationFormFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationForm extends Model
{
    /** @use HasFactory<RegistrationFormFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'organization_id',
        'event_id',
        'fields',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
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
