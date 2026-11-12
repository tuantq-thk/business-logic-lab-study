<?php

namespace App\Services;

use App\Models\CatalogSetting;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class LabCatalog
{
    /**
     * @return array<string, LabDefinition>
     */
    public function registry(): array
    {
        $definitions = [];

        foreach (config('labs.registry', []) as $key => $attributes) {
            if (! is_array($attributes)) {
                throw new RuntimeException("Lab registry entry [{$key}] must be an array.");
            }

            $definitions[$key] = LabDefinition::fromConfig($attributes);
        }

        return $definitions;
    }

    public function find(string $key): ?LabDefinition
    {
        return $this->registry()[$key] ?? null;
    }

    public function catalogSetting(): CatalogSetting
    {
        return CatalogSetting::query()
            ->where('key', CatalogSetting::CATALOG_KEY)
            ->firstOrFail();
    }

    public function isCatalogEnabled(): bool
    {
        return $this->catalogSetting()->isEnabled();
    }

    public function entry(string $key): ?LabCatalogEntry
    {
        $definition = $this->find($key);

        if ($definition === null) {
            return null;
        }

        return new LabCatalogEntry($definition, $this->setting($key));
    }

    /**
     * @return list<LabCatalogEntry>
     */
    public function entries(): array
    {
        $definitions = $this->registry();
        $settings = $this->settings(array_keys($definitions));

        return array_values(array_map(
            fn (string $key): LabCatalogEntry => new LabCatalogEntry($definitions[$key], $settings[$key]),
            array_keys($definitions),
        ));
    }

    /**
     * @return list<LabDefinition>
     */
    public function visible(): array
    {
        $catalogEnabled = $this->isCatalogEnabled();

        return array_values(array_map(
            fn (LabCatalogEntry $entry): LabDefinition => $entry->definition,
            array_filter(
                $this->entries(),
                fn (LabCatalogEntry $entry): bool => $entry->isAvailable($catalogEnabled),
            ),
        ));
    }

    public function isAvailable(string $key): bool
    {
        $entry = $this->entry($key);

        return $entry !== null && $entry->isAvailable($this->isCatalogEnabled());
    }

    private function setting(string $key): CatalogSetting
    {
        return CatalogSetting::query()
            ->where('key', $key)
            ->firstOrFail();
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, CatalogSetting>
     */
    private function settings(array $keys): array
    {
        /** @var Collection<string, CatalogSetting> $settings */
        $settings = CatalogSetting::query()
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');

        $missingKeys = array_values(array_diff($keys, $settings->keys()->all()));

        if ($missingKeys !== []) {
            throw new RuntimeException('Missing catalog settings for lab keys: '.implode(', ', $missingKeys));
        }

        return $settings->all();
    }
}
