<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectInstallerAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((bool) $request->session()->get('installer_access_granted', false)) {
            return $next($request);
        }

        $configuredKey = trim((string) config('crm.installer_key', ''));

        if ($configuredKey === '') {
            abort(403, 'El instalador esta protegido. Configura INSTALLER_KEY para continuar.');
        }

        $providedKey = trim((string) ($request->query('installer_key') ?? $request->input('installer_key', '')));

        if ($providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            abort(404);
        }

        $request->session()->put('installer_access_granted', true);

        return $next($request);
    }
}
