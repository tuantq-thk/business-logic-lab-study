<?php

namespace App\Http\Controllers;

use App\Services\LabCatalog;
use Illuminate\View\View;

class LabController extends Controller
{
    public function show(string $labKey, LabCatalog $catalog): View
    {
        $lab = $catalog->find($labKey);

        abort_if($lab === null, 404);

        return view('labs.show', compact('lab'));
    }
}
