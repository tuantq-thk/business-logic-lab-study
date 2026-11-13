<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCatalogSettingRequest;
use App\Models\CatalogSetting;
use App\Services\LabCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(LabCatalog $catalog): View
    {
        $catalogSetting = CatalogSetting::query()
            ->where('key', CatalogSetting::CATALOG_KEY)
            ->with('changedBy')
            ->firstOrFail();
        $labs = $catalog->entries();

        return view('admin.catalog.index', compact('catalogSetting', 'labs'));
    }

    public function update(UpdateCatalogSettingRequest $request): RedirectResponse
    {
        $catalogSetting = CatalogSetting::query()
            ->where('key', CatalogSetting::CATALOG_KEY)
            ->firstOrFail();

        $catalogSetting->update([
            'enabled' => $request->boolean('enabled'),
            'last_changed_by' => $request->user()->id,
            'last_changed_at' => now(),
            'reason' => $request->string('reason')->trim()->toString() ?: null,
        ]);

        return to_route('admin.catalog.index')->with('status', __('catalog.flash.catalog_updated'));
    }

    public function updateLab(UpdateCatalogSettingRequest $request, string $labKey, LabCatalog $catalog): RedirectResponse
    {
        $entry = $catalog->entry($labKey);

        abort_unless($entry?->definition->implemented && $entry->definition->toggleable, 404);

        $entry->setting->update([
            'enabled' => $request->boolean('enabled'),
            'last_changed_by' => $request->user()->id,
            'last_changed_at' => now(),
            'reason' => $request->string('reason')->trim()->toString() ?: null,
        ]);

        return to_route('admin.catalog.index')->with('status', __('catalog.flash.lab_updated', ['key' => $labKey]));
    }
}
