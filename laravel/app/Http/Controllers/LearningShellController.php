<?php

namespace App\Http\Controllers;

use App\Models\CatalogSetting;
use Illuminate\View\View;

class LearningShellController extends Controller
{
    public function index(): View
    {
        $catalogSetting = CatalogSetting::query()
            ->where('key', CatalogSetting::CATALOG_KEY)
            ->firstOrFail();

        return view('home', compact('catalogSetting'));
    }
}
