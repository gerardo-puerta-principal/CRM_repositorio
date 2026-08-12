<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadImportConfirmRequest;
use App\Http\Requests\LeadImportPreviewRequest;
use App\Services\LeadImport\LeadImportService;
use RuntimeException;

class LeadImportController extends Controller
{
    public function create()
    {
        return view('leads.import', [
            'preview' => session('lead_import_preview'),
            'targetFields' => config('crm.import_target_fields', []),
            'maxRows' => (int) config('crm.import_max_rows', 5000),
        ]);
    }

    public function preview(LeadImportPreviewRequest $request, LeadImportService $leadImportService)
    {
        $leadImportService->clearPreview(session('lead_import_preview'));

        try {
            $preview = $leadImportService->preview($request->file('file'));
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['file' => $exception->getMessage()]);
        }

        session(['lead_import_preview' => $preview]);

        return redirect()
            ->route('leads.import.create')
            ->with('status', 'Archivo analizado correctamente. Revisa el mapeo sugerido y confirma la importación.');
    }

    public function store(LeadImportConfirmRequest $request, LeadImportService $leadImportService)
    {
        $preview = session('lead_import_preview');

        if (! is_array($preview)) {
            return redirect()
                ->route('leads.import.create')
                ->withErrors(['file' => 'No hay una importación pendiente para confirmar.']);
        }

        try {
            $result = $leadImportService->import(
                $preview,
                $request->validated('mapping', []),
                $request->user()->id,
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('leads.import.create')
                ->withErrors(['file' => $exception->getMessage()]);
        }

        $request->session()->forget('lead_import_preview');

        return redirect()
            ->route('leads.index')
            ->with('status', 'Importación completada. Importados: '.$result['imported'].' | Omitidos: '.$result['skipped'].'.');
    }
}
