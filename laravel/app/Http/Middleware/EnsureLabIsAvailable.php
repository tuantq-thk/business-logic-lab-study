<?php

namespace App\Http\Middleware;

use App\Services\LabCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLabIsAvailable
{
    public function __construct(private readonly LabCatalog $catalog) {}

    public function handle(Request $request, Closure $next): Response
    {
        $labKey = $request->route('labKey');

        abort_unless(is_string($labKey) && $this->catalog->isAvailable($labKey), 404);

        return $next($request);
    }
}
