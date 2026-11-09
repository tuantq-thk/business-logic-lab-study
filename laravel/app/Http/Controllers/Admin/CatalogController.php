<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCatalogSettingRequest;
use App\Models\CatalogSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        $catalogSetting = CatalogSetting::query()
            ->where('key', CatalogSetting::CATALOG_KEY)
            ->with('changedBy')
            ->firstOrFail();

        return view('admin.catalog.index', compact('catalogSetting'));
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

        return to_route('admin.catalog.index')->with('status', 'Catalog state đã được cập nhật.');
    }
}
