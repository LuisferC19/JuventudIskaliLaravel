<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use App\Services\NotificacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private readonly DatabaseBackupService $backups) {}

    public function index(): View
    {
        return view('respaldos', [
            'historial' => $this->backups->historial(),
            'baseDatos' => $this->backups->nombreBaseDatos(),
        ]);
    }

    public function generar(Request $request): BinaryFileResponse
    {
        $path = $this->backups->generar($request->user()?->id_usuario);
        $idUsuario = $request->user()?->id_usuario;
        if ($idUsuario) {
            try {
                app(NotificacionService::class)->crear(
                    (int) $idUsuario,
                    'sistema',
                    'Respaldo completado',
                    'Se generó correctamente el archivo '.basename($path).'.'
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function importar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_file' => ['required', 'file', 'extensions:zip', 'max:51200'],
        ]);

        try {
            $this->backups->importar($validated['backup_file'], $request->user()?->id_usuario);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'backup_file' => 'No fue posible importar el respaldo. Verifica que el archivo sea válido y compatible.',
            ]);
        }

        return back()->with('success', 'El respaldo se importó correctamente.');
    }
}
