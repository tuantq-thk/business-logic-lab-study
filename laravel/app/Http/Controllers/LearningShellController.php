<?php

namespace App\Http\Controllers;

use App\Services\LabCatalog;
use Illuminate\View\View;

class LearningShellController extends Controller
{
    public function index(LabCatalog $catalog): View
    {
        $catalogSetting = $catalog->catalogSetting();
        $labs = $catalog->visible();

        return view('home', compact('catalogSetting', 'labs'));
    }
}
