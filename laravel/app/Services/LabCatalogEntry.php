<?php

namespace App\Services;

use App\Models\CatalogSetting;

final readonly class LabCatalogEntry
{
    public function __construct(
        public LabDefinition $definition,
        public CatalogSetting $setting,
    ) {}

    public function isEnabled(): bool
    {
        return $this->setting->isEnabled();
    }

    public function isAvailable(bool $catalogEnabled): bool
    {
        return $catalogEnabled
            && $this->definition->implemented
            && $this->isEnabled();
    }
}
