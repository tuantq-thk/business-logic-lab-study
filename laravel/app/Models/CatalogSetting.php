<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'enabled',
    'last_changed_by',
    'last_changed_at',
    'reason',
])]
class CatalogSetting extends Model
{
    public const CATALOG_KEY = 'lab.catalog';

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_changed_by');
    }

    public function isEnabled(): bool
    {
        return $this->enabled ?? $this->default_enabled;
    }

    public function stateSource(): string
    {
        return $this->enabled === null ? 'default' : 'admin override';
    }

    protected function casts(): array
    {
        return [
            'implemented' => 'boolean',
            'default_enabled' => 'boolean',
            'enabled' => 'boolean',
            'last_changed_at' => 'datetime',
        ];
    }
}
