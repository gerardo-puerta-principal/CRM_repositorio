<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstallApplicationRequest;
use App\Services\Installer\InstallerService;
use RuntimeException;

class InstallerController extends Controller
{
    public function create()
    {
        return view('installer.create');
    }

    public function store(InstallApplicationRequest $request, InstallerService $installerService)
    {
        try {
            $installerService->install($request->validated());
        } catch (RuntimeException $exception) {
            return back()
                ->withInput($request->except(['admin_password', 'admin_password_confirmation', 'db_password']))
                ->withErrors(['installer' => $exception->getMessage()]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Instalacion completada. Ya puedes iniciar sesion con tu cuenta de Super Admin.');
    }
}
