<?php

namespace App\Http\Middleware;

use App\Services\Installer\InstallationStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfInstalled
{
    public function __construct(private readonly InstallationStatus $installationStatus)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installationStatus->isInstalled()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
