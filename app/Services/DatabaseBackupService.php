<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;
use ZipArchive;

class DatabaseBackupService
{
    private const MANIFEST = 'backup.json';
    private const MAX_ARCHIVE_BYTES = 52428800;
    private const MAX_MANIFEST_BYTES = 104857600;
    private const EXCLUDED_TABLES = [
        'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs',
        'migrations', 'password_reset_tokens', 'sessions',
    ];

    public function __construct(private readonly ?string $backupDirectory = null) {}

    public function generar(?int $usuarioId): string
    {
        $directory = $this->directorioRespaldos();
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extensión ZIP de PHP no está disponible.');
        }

        $tableNames = $this->tablasRespaldables();
        $tables = [];
        foreach ($tableNames as $table) {
            $tables[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }

        $contents = json_encode([
            'format' => 'juventud-iskali-backup',
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'tables' => $tables,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $filename = 'respaldo_'.now()->format('Ymd_His').'_'.bin2hex(random_bytes(4)).'.zip';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('No se pudo crear el archivo de respaldo.');
        }

        try {
            $zip->addFromString(self::MANIFEST, $contents);
        } finally {
            $zip->close();
        }

        try {
            $this->registrarOperacion('EXPORTACION', $filename, filesize($path), $usuarioId, 'Respaldo manual generado.');
        } catch (Throwable $exception) {
            @unlink($path);
            throw $exception;
        }

        return $path;
    }

    public function importar(UploadedFile $file, ?int $usuarioId): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extensión ZIP de PHP no está disponible.');
        }
        if ($file->getSize() > self::MAX_ARCHIVE_BYTES) {
            throw new RuntimeException('El archivo de respaldo supera el tamaño permitido.');
        }

        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('El archivo ZIP no es válido o está dañado.');
        }

        try {
            if ($zip->numFiles !== 1 || $zip->getNameIndex(0) !== self::MANIFEST) {
                throw new RuntimeException('El ZIP debe contener únicamente el manifiesto backup.json.');
            }
            $stat = $zip->statIndex(0);
            if (! is_array($stat) || $stat['size'] > self::MAX_MANIFEST_BYTES) {
                throw new RuntimeException('El manifiesto del respaldo supera el tamaño permitido.');
            }
            $json = $zip->getFromIndex(0);
        } finally {
            $zip->close();
        }

        try {
            $manifest = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException('El manifiesto del respaldo no contiene JSON válido.', previous: $exception);
        }
        $this->validarManifiesto($manifest);

        $directory = $this->directorioRespaldos();
        $filename = 'importacion_'.now()->format('Ymd_His').'_'.bin2hex(random_bytes(4)).'.zip';
        $storedPath = $directory.DIRECTORY_SEPARATOR.$filename;
        if (! copy($file->getRealPath(), $storedPath)) {
            throw new RuntimeException('No se pudo guardar el archivo importado en el almacenamiento privado.');
        }

        try {
            Schema::disableForeignKeyConstraints();
            DB::transaction(function () use ($manifest): void {
                foreach (array_keys($manifest['tables']) as $table) {
                    DB::table($table)->delete();
                }
                foreach ($manifest['tables'] as $table => $rows) {
                    $columns = Schema::getColumnListing($table);
                    foreach ($rows as $row) {
                        $record = [];
                        foreach ($row as $column => $value) {
                            if (! in_array($column, $columns, true)) {
                                throw new RuntimeException('El respaldo incluye columnas no reconocidas.');
                            }
                            $record[$column] = is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value;
                        }
                        if ($record !== []) {
                            DB::table($table)->insert($record);
                        }
                    }
                }
            });
        } catch (Throwable $exception) {
            @unlink($storedPath);
            throw $exception;
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        try {
            $this->registrarOperacion(
                'IMPORTACION',
                $filename,
                filesize($storedPath),
                $usuarioId,
                'Respaldo importado manualmente.'
            );
        } catch (Throwable $exception) {
            @unlink($storedPath);
            throw $exception;
        }

        return $storedPath;
    }

    public function historial(int $limite = 50): array
    {
        if (! Schema::hasTable('respaldos')) {
            return [];
        }

        return DB::table('respaldos as r')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'r.usuario_id')
            ->orderByDesc('r.fechayhora')
            ->limit($limite)
            ->select(['r.*', 'u.nombre as nombre_usuario', 'u.apellido as apellido_usuario'])
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }

    public function nombreBaseDatos(): string
    {
        return (string) config('database.connections.'.config('database.default').'.database', '');
    }

    private function validarManifiesto(mixed $manifest): void
    {
        if (! is_array($manifest)
            || ($manifest['format'] ?? null) !== 'juventud-iskali-backup'
            || ($manifest['version'] ?? null) !== 1
            || ! isset($manifest['tables'])
            || ! is_array($manifest['tables'])) {
            throw new RuntimeException('El archivo no corresponde a un respaldo compatible.');
        }

        $providedTables = array_keys($manifest['tables']);
        $allowedTables = $this->tablasRespaldables();
        sort($providedTables);
        sort($allowedTables);
        if ($providedTables !== $allowedTables) {
            throw new RuntimeException('El respaldo contiene tablas distintas a las de esta instalación.');
        }

        foreach ($manifest['tables'] as $table => $rows) {
            if (! is_string($table) || ! is_array($rows)) {
                throw new RuntimeException('La estructura de datos del respaldo no es válida.');
            }
            foreach ($rows as $row) {
                if (! is_array($row) || array_is_list($row)) {
                    throw new RuntimeException('El respaldo contiene un registro no válido.');
                }
            }
        }
    }

    private function tablasRespaldables(): array
    {
        return array_values(array_filter(
            Schema::getTableListing(),
            fn (string $table): bool => ! in_array($table, self::EXCLUDED_TABLES, true)
                && ! str_starts_with($table, 'sqlite_')
        ));
    }

    private function directorioRespaldos(): string
    {
        $root = realpath(storage_path('app'));
        if ($root === false) {
            throw new RuntimeException('No se encontró el almacenamiento privado de la aplicación.');
        }
        $directory = $this->backupDirectory ?? $root.DIRECTORY_SEPARATOR.'respaldos';
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el directorio privado de respaldos.');
        }
        if (is_link($directory)) {
            throw new RuntimeException('El directorio de respaldos no puede ser un enlace simbólico.');
        }
        $realDirectory = realpath($directory);
        if ($realDirectory === false || ! is_writable($realDirectory)) {
            throw new RuntimeException('El directorio privado de respaldos no es accesible.');
        }
        $rootPrefix = rtrim(str_replace('\\', '/', $root), '/').'/';
        $directoryPath = rtrim(str_replace('\\', '/', $realDirectory), '/').'/';
        if (! str_starts_with(strtolower($directoryPath), strtolower($rootPrefix))) {
            throw new RuntimeException('El directorio de respaldos debe permanecer dentro de storage/app.');
        }
        $publicRoot = realpath(public_path());
        if ($publicRoot !== false) {
            $publicPrefix = rtrim(str_replace('\\', '/', $publicRoot), '/').'/';
            if (str_starts_with(strtolower($directoryPath), strtolower($publicPrefix))) {
                throw new RuntimeException('Los respaldos no pueden guardarse en un directorio público.');
            }
        }

        return $realDirectory;
    }

    private function registrarOperacion(
        string $tipo,
        string $filename,
        int|false $bytes,
        ?int $usuarioId,
        string $observaciones
    ): void {
        if (! Schema::hasTable('respaldos')) {
            throw new RuntimeException('No existe la tabla de historial de respaldos.');
        }

        DB::table('respaldos')->insert([
            'tipo_operacion' => $tipo,
            'nombre_archivo' => $filename,
            'formato' => 'ZIP',
            'nombre_bd' => $this->nombreBaseDatos(),
            'tamanio_bytes' => max(0, (int) $bytes),
            'usuario_id' => $usuarioId,
            'observaciones' => $observaciones,
        ]);
    }
}
