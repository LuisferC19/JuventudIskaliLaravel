<?php

namespace App\Services;

use App\Contracts\InventarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use RuntimeException;

class DonacionWorkflowService
{
    public function crear(array $datos, int $idUsuario): int
    {
        return DB::transaction(function () use ($datos, $idUsuario): int {
            $donador = DB::table('donadores')
                ->where('id_donador', $datos['id_donador'])
                ->where('activo', true)
                ->lockForUpdate()
                ->first();
            $campana = DB::table('campanas')
                ->where('id_campana', $datos['id_campana'])
                ->where('estado', 'activa')
                ->lockForUpdate()
                ->first();

            if (!$donador || !$campana) {
                throw new LogicException('El donador o la campaña ya no están disponibles.');
            }

            $id = DB::table('donaciones')->insertGetId([
                'id_donador' => $datos['id_donador'],
                'id_campana' => $datos['id_campana'],
                'id_usuario_registrador' => $idUsuario,
                'fecha_recepcion' => $datos['fecha_recepcion'],
                'estado' => 'pendiente',
                'evidencia_url' => $datos['evidencia_url'] ?? null,
            ], 'id_donacion');

            if ($datos['tipo'] === 'especie') {
                $bien = DB::table('tipos_bien')
                    ->where('id_tipo_bien', $datos['id_tipo_bien'])
                    ->where('activo', true)
                    ->lockForUpdate()
                    ->first();
                if (!$bien) {
                    throw new LogicException('El tipo de bien seleccionado no está activo.');
                }

                DB::table('donaciones_especie')->insert([
                    'id_donacion' => $id,
                    'id_tipo_bien' => $bien->id_tipo_bien,
                    'descripcion' => $datos['descripcion'] ?? null,
                    'cantidad' => $datos['cantidad'],
                    'unidad_medida' => $bien->unidad_medida,
                ]);
            } else {
                DB::table('donaciones_economicas')->insert([
                    'id_donacion' => $id,
                    'monto' => $datos['monto'],
                    'moneda' => $datos['moneda'] ?? 'MXN',
                    'metodo_pago' => $datos['metodo_pago'],
                    'referencia_pago' => $datos['referencia_pago'] ?? null,
                ]);
            }

            return (int) $id;
        }, 3);
    }

    public function actualizar(int $id, array $datos): void
    {
        DB::transaction(function () use ($id, $datos): void {
            $donacion = DB::table('donaciones')->where('id_donacion', $id)->lockForUpdate()->first();
            if (!$donacion || $donacion->estado !== 'pendiente') {
                throw new LogicException('Solo se pueden editar donaciones pendientes.');
            }

            $campana = DB::table('campanas')
                ->where('id_campana', $datos['id_campana'])
                ->lockForUpdate()
                ->first();
            if (!$campana) {
                throw new LogicException('La campaña seleccionada no existe.');
            }

            DB::table('donaciones')->where('id_donacion', $id)->update([
                'id_campana' => $datos['id_campana'],
                'fecha_recepcion' => $datos['fecha_recepcion'],
                'evidencia_url' => $datos['evidencia_url'] ?? null,
            ]);

            $especie = DB::table('donaciones_especie')
                ->where('id_donacion', $id)
                ->lockForUpdate()
                ->first();
            $economica = DB::table('donaciones_economicas')
                ->where('id_donacion', $id)
                ->lockForUpdate()
                ->first();

            if ($especie) {
                $bien = DB::table('tipos_bien')
                    ->where('id_tipo_bien', $datos['id_tipo_bien'])
                    ->where('activo', true)
                    ->lockForUpdate()
                    ->first();
                if (!$bien) {
                    throw new LogicException('El tipo de bien seleccionado no está activo.');
                }
                DB::table('donaciones_especie')->where('id_donacion', $id)->update([
                    'id_tipo_bien' => $bien->id_tipo_bien,
                    'descripcion' => $datos['descripcion'] ?? null,
                    'cantidad' => $datos['cantidad'],
                    'unidad_medida' => $bien->unidad_medida,
                ]);
            } elseif ($economica) {
                DB::table('donaciones_economicas')->where('id_donacion', $id)->update([
                    'monto' => $datos['monto'],
                    'moneda' => $datos['moneda'] ?? $economica->moneda,
                    'metodo_pago' => $datos['metodo_pago'],
                    'referencia_pago' => $datos['referencia_pago'] ?? null,
                ]);
            } else {
                throw new LogicException('La donación no tiene un detalle válido.');
            }
        }, 3);
    }

    public function eliminar(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $donacion = DB::table('donaciones')->where('id_donacion', $id)->lockForUpdate()->first();
            if (!$donacion || $donacion->estado !== 'pendiente') {
                throw new LogicException('Solo se pueden eliminar donaciones pendientes.');
            }

            DB::table('donaciones_especie')->where('id_donacion', $id)->lockForUpdate()->first();
            DB::table('donaciones_economicas')->where('id_donacion', $id)->lockForUpdate()->first();
            DB::table('donaciones_especie')->where('id_donacion', $id)->delete();
            DB::table('donaciones_economicas')->where('id_donacion', $id)->delete();
            DB::table('donaciones')->where('id_donacion', $id)->delete();
        }, 3);
    }

    public function cambiarEstado(int $id, string $nuevoEstado, int $idUsuario): void
    {
        DB::transaction(function () use ($id, $nuevoEstado, $idUsuario): void {
            $donacion = DB::table('donaciones')->where('id_donacion', $id)->lockForUpdate()->first();
            $transiciones = [
                'pendiente' => ['recibida'],
                'recibida' => ['verificada', 'rechazada'],
                'verificada' => [],
                'rechazada' => [],
            ];
            if (!$donacion || !in_array($nuevoEstado, $transiciones[$donacion->estado] ?? [], true)) {
                throw new LogicException('La transición de estado no está permitida.');
            }

            if ($nuevoEstado === 'verificada') {
                $especie = DB::table('donaciones_especie')
                    ->where('id_donacion', $id)
                    ->lockForUpdate()
                    ->first();
                if ($especie) {
                    $inventario = app(InventarioService::class);
                    if (!$inventario->registrarEntrada(
                        (int) $especie->id_tipo_bien,
                        (float) $especie->cantidad,
                        $idUsuario,
                        'donacion',
                        $id
                    )) {
                        throw new RuntimeException('No se pudo registrar la entrada de inventario.');
                    }
                }
            }

            DB::table('donaciones')->where('id_donacion', $id)->update(['estado' => $nuevoEstado]);
        }, 3);
    }

    public function notificarNueva(int $idDonacion): void
    {
        try {
            $detalle = DB::table('donaciones as d')
                ->join('campanas as c', 'c.id_campana', '=', 'd.id_campana')
                ->join('donadores as dn', 'dn.id_donador', '=', 'd.id_donador')
                ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'dn.id_donador')
                ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'dn.id_donador')
                ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'dn.id_donador')
                ->where('d.id_donacion', $idDonacion)
                ->selectRaw("COALESCE(dm.razon_social, CONCAT(COALESCE(df.nombre, ''), ' ', COALESCE(df.apellido, '')), dg.nombre_grupo, 'Anónimo') AS donador, c.nombre AS campana")
                ->first();
            if (!$detalle) {
                return;
            }

            app(NotificacionService::class)->donacionNueva(
                $idDonacion,
                (string) $detalle->donador,
                (string) $detalle->campana
            );
        } catch (\Throwable $error) {
            Log::error('No se pudo notificar nueva donación.', ['id_donacion' => $idDonacion, 'error' => $error->getMessage()]);
        }
    }

    public function notificarVerificada(int $idDonacion): void
    {
        try {
            $detalle = DB::table('donaciones as d')
                ->join('campanas as c', 'c.id_campana', '=', 'd.id_campana')
                ->where('d.id_donacion', $idDonacion)
                ->select('d.id_usuario_registrador', 'c.nombre as campana')
                ->first();
            if (!$detalle || !$detalle->id_usuario_registrador) {
                return;
            }
            app(NotificacionService::class)->donacionVerificada(
                (int) $detalle->id_usuario_registrador,
                $idDonacion,
                (string) $detalle->campana
            );
        } catch (\Throwable $error) {
            Log::error('No se pudo notificar donación verificada.', ['id_donacion' => $idDonacion, 'error' => $error->getMessage()]);
        }
    }

}
