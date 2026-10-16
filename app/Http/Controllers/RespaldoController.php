<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use ZipArchive;

class RespaldoController extends Controller
{
    private const TABLAS = [
        'roles',
        'permisos_rol',
        'comunidades',
        'tipos_apoyo',
        'niveles_gamificacion',
        'insignias',
        'tipos_referencia_notif',
        'tipos_bien',
        'usuarios',
        'historial_accesos',
        'recuperacion_contrasena',
        'donadores',
        'donadores_fisicos',
        'donadores_morales',
        'donadores_grupos',
        'donador_insignias',
        'campanas',
        'avances_campana',
        'reconocimientos',
        'donaciones',
        'donaciones_especie',
        'donaciones_economicas',
        'inventario',
        'movimientos_inventario',
        'beneficiarios',
        'beneficiarios_fisicos',
        'beneficiarios_morales',
        'beneficiario_tipos_apoyo',
        'entregas',
        'voluntarios',
        'asignaciones_voluntario',
        'actividades',
        'asistencia_voluntarios',
        'notificaciones',
        'respaldos',
    ];

    public function index(): View
    {
        $historial = DB::table('respaldos as r')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'r.usuario_id')
            ->orderByDesc('r.fechayhora')
            ->limit(50)
            ->get(['r.*', 'u.nombre as nombre_usuario']);

        return view('respaldos', compact('historial'));
    }

    public function generar(Request $request)
    {
        $this->asegurarAcceso($request);
        $this->asegurarMySql();
        $this->asegurarZip();
        $this->validarTablas();

        $directorio = Storage::disk('local')->path('respaldos');
        Storage::disk('local')->makeDirectory('respaldos');
        $temporal = tempnam($directorio, 'respaldo-');
        if ($temporal === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del respaldo.');
        }
        $archivo = $directorio.DIRECTORY_SEPARATOR.'respaldo_'.now()->format('Ymd_His').'_'.bin2hex(random_bytes(4)).'.zip';
        $json = fopen($temporal, 'wb');
        if ($json === false) {
            @unlink($temporal);
            throw new RuntimeException('No se pudo escribir el contenido del respaldo.');
        }

        try {
            $database = (string) config('database.connections.mysql.database');
            $this->escribir($json, '{"version":1,"database":'.json_encode($database, JSON_THROW_ON_ERROR).',"tables":{');
            $columnas = $this->columnasEscribibles();
            foreach (self::TABLAS as $indice => $tabla) {
                if ($indice > 0) {
                    $this->escribir($json, ',');
                }
                $this->escribir($json, json_encode($tabla, JSON_THROW_ON_ERROR).':[');
                $primero = true;
                foreach (DB::table($tabla)->select($columnas[$tabla])->cursor() as $fila) {
                    if (!$primero) {
                        $this->escribir($json, ',');
                    }
                    $this->escribir($json, json_encode((array) $fila, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
                    $primero = false;
                }
                $this->escribir($json, ']');
            }
            $this->escribir($json, '}}');
            fclose($json);
            $json = false;

            $hash = hash_file('sha256', $temporal);
            if ($hash === false) {
                throw new RuntimeException('No se pudo verificar el contenido del respaldo.');
            }
            $manifest = $this->manifest($database, $hash);
            $zip = new ZipArchive();
            if ($zip->open($archivo, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new RuntimeException('No se pudo crear el archivo ZIP del respaldo.');
            }
            $zip->addFile($temporal, 'data.json');
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
            if (!$zip->close()) {
                throw new RuntimeException('No se pudo finalizar el archivo ZIP del respaldo.');
            }

            $bytes = filesize($archivo);
            if ($bytes === false || $bytes === 0) {
                throw new RuntimeException('El archivo de respaldo quedó vacío.');
            }
            $this->registrarOperacion('EXPORTACION', basename($archivo), $bytes, $request);
            $this->notificarRespaldoCompletado($request, basename($archivo));

            return response()->download($archivo)->deleteFileAfterSend(false);
        } finally {
            if (is_resource($json)) {
                fclose($json);
            }
            if (is_file($temporal)) {
                unlink($temporal);
            }
        }
    }

    public function importar(Request $request): RedirectResponse
    {
        $this->asegurarAcceso($request);
        $this->asegurarMySql();
        $this->asegurarZip();
        $request->validate(['backup_file' => ['required', 'file', 'max:102400']]);

        $subida = $request->file('backup_file');
        if (strtolower($subida->getClientOriginalExtension()) !== 'zip') {
            throw ValidationException::withMessages(['backup_file' => 'El respaldo debe ser un archivo ZIP válido.']);
        }

        $zip = new ZipArchive();
        if ($zip->open($subida->getRealPath()) !== true) {
            throw ValidationException::withMessages(['backup_file' => 'No se pudo abrir el archivo ZIP.']);
        }
        try {
            if ($zip->numFiles !== 2 || $zip->locateName('data.json') === false || $zip->locateName('manifest.json') === false) {
                throw ValidationException::withMessages(['backup_file' => 'El ZIP debe contener únicamente el respaldo generado por esta aplicación.']);
            }
            $dataStat = $zip->statName('data.json');
            $manifestStat = $zip->statName('manifest.json');
            if (!$dataStat || !$manifestStat || $dataStat['size'] > 268435456 || $manifestStat['size'] > 16384) {
                throw ValidationException::withMessages(['backup_file' => 'El contenido descomprimido excede el límite permitido.']);
            }
            $contenido = $zip->getFromName('data.json');
            $manifiestoJson = $zip->getFromName('manifest.json');
        } finally {
            $zip->close();
        }

        if (!is_string($contenido) || !is_string($manifiestoJson)) {
            throw ValidationException::withMessages(['backup_file' => 'No se pudo leer el contenido del respaldo.']);
        }
        $database = (string) config('database.connections.mysql.database');
        try {
            $manifiesto = json_decode($manifiestoJson, true, 16, JSON_THROW_ON_ERROR);
            $respaldo = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['backup_file' => 'El contenido del respaldo no es JSON válido.']);
        }

        $hash = hash('sha256', $contenido);
        $esperado = $this->manifest($database, $hash);
        if (!is_array($manifiesto)
            || ($manifiesto['version'] ?? null) !== 1
            || !hash_equals($esperado['signature'], (string) ($manifiesto['signature'] ?? ''))
            || ($manifiesto['database'] ?? null) !== $database
            || ($manifiesto['sha256'] ?? null) !== $hash) {
            throw ValidationException::withMessages(['backup_file' => 'La firma del respaldo no es válida o pertenece a otra base de datos.']);
        }
        if (($respaldo['version'] ?? null) !== 1 || ($respaldo['database'] ?? null) !== $database || !is_array($respaldo['tables'] ?? null)) {
            throw ValidationException::withMessages(['backup_file' => 'La estructura del respaldo no es compatible.']);
        }
        $this->validarTablas();
        $columnas = $this->columnasEscribibles();
        if (array_keys($respaldo['tables']) !== self::TABLAS) {
            throw ValidationException::withMessages(['backup_file' => 'El respaldo no contiene exactamente las tablas esperadas.']);
        }
        foreach (self::TABLAS as $tabla) {
            if (!is_array($respaldo['tables'][$tabla])) {
                throw ValidationException::withMessages(['backup_file' => "El contenido de {$tabla} no es válido."]);
            }
            foreach ($respaldo['tables'][$tabla] as $fila) {
                if (!is_array($fila) || array_diff(array_keys($fila), $columnas[$tabla]) !== []) {
                    throw ValidationException::withMessages(['backup_file' => "El contenido de {$tabla} incluye columnas no permitidas."]);
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        try {
            DB::transaction(function () use ($respaldo, $columnas, $request, $database): void {
                foreach (array_reverse(self::TABLAS) as $tabla) {
                    DB::table($tabla)->delete();
                }
                foreach (self::TABLAS as $tabla) {
                    foreach (array_chunk($respaldo['tables'][$tabla], 500) as $lote) {
                        if ($lote !== []) {
                            DB::table($tabla)->insert($lote);
                        }
                    }
                }

                $usuarioId = DB::table('usuarios')->where('id_usuario', $request->user()->id_usuario)->exists()
                    ? $request->user()->id_usuario
                    : null;
                DB::table('respaldos')->insert([
                    'tipo_operacion' => 'IMPORTACION',
                    'nombre_archivo' => $request->file('backup_file')->getClientOriginalName(),
                    'formato' => 'ZIP',
                    'nombre_bd' => $database,
                    'tamanio_bytes' => $request->file('backup_file')->getSize(),
                    'usuario_id' => $usuarioId,
                    'observaciones' => 'Importación de respaldo verificado.',
                ]);
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        return redirect()->route('respaldos.index')->with('success', 'El respaldo se importó correctamente.');
    }

    private function asegurarAcceso(Request $request): void
    {
        $rol = DB::table('roles')->where('id_rol', $request->user()->id_rol)->value('nombre');
        abort_unless(in_array($rol, ['Administrador', 'Auditor'], true), 403);
    }

    private function asegurarMySql(): void
    {
        abort_unless(DB::connection()->getDriverName() === 'mysql', 503, 'Los respaldos requieren una conexión MySQL.');
    }

    private function asegurarZip(): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extensión ZIP de PHP es necesaria para administrar respaldos.');
        }
    }

    private function validarTablas(): void
    {
        foreach (self::TABLAS as $tabla) {
            if (!Schema::hasTable($tabla)) {
                throw new RuntimeException("No existe la tabla requerida para respaldar: {$tabla}.");
            }
        }
    }

    private function columnasEscribibles(): array
    {
        $database = (string) config('database.connections.mysql.database');
        $generadas = [];
        foreach (DB::select(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND EXTRA LIKE ?',
            [$database, '%GENERATED%']
        ) as $columna) {
            $generadas[$columna->TABLE_NAME][] = $columna->COLUMN_NAME;
        }

        $columnas = [];
        foreach (self::TABLAS as $tabla) {
            $columnas[$tabla] = array_values(array_diff(
                Schema::getColumnListing($tabla),
                $generadas[$tabla] ?? []
            ));
        }

        return $columnas;
    }

    private function manifest(string $database, string $hash): array
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }
        if ($key === '') {
            throw new RuntimeException('No hay una clave de aplicación disponible para firmar el respaldo.');
        }

        return [
            'version' => 1,
            'database' => $database,
            'sha256' => $hash,
            'signature' => hash_hmac('sha256', $database."\0".$hash, $key),
        ];
    }

    private function registrarOperacion(string $tipo, string $archivo, int $bytes, Request $request): void
    {
        DB::table('respaldos')->insert([
            'tipo_operacion' => $tipo,
            'nombre_archivo' => $archivo,
            'formato' => 'ZIP',
            'nombre_bd' => (string) config('database.connections.mysql.database'),
            'tamanio_bytes' => $bytes,
            'usuario_id' => $request->user()->id_usuario,
            'observaciones' => 'Respaldo manual generado desde el panel.',
        ]);
    }

    private function notificarRespaldoCompletado(Request $request, string $archivo): void
    {
        try {
            DB::table('notificaciones')->insert([
                'id_usuario' => $request->user()->id_usuario,
                'tipo' => 'sistema',
                'canal' => 'web',
                'asunto' => 'Respaldo completado',
                'mensaje' => 'Se generó correctamente el archivo '.$archivo.'.',
            ]);
        } catch (\Throwable $error) {
            Log::error('No se pudo notificar el respaldo generado.', [
                'archivo' => $archivo,
                'id_usuario' => $request->user()->id_usuario,
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function escribir($flujo, string $contenido): void
    {
        $escritos = 0;
        $longitud = strlen($contenido);
        while ($escritos < $longitud) {
            $resultado = fwrite($flujo, substr($contenido, $escritos));
            if ($resultado === false || $resultado === 0) {
                throw new RuntimeException('No se pudo escribir completamente el archivo de respaldo.');
            }
            $escritos += $resultado;
        }
    }
}
