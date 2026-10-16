<?php

namespace App\Services;

use App\Contracts\InventarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use RuntimeException;

class EntregaWorkflowService
{
    public function crear(array $datos, int $idUsuario): int
    {
        return DB::transaction(function () use ($datos, $idUsuario): int {
            $donacion = DB::table('donaciones')
                ->where('id_donacion', $datos['id_donacion'])
                ->where('estado', 'verificada')
                ->lockForUpdate()
                ->first();
            $beneficiario = DB::table('beneficiarios')
                ->where('id_beneficiario', $datos['id_beneficiario'])
                ->where('estado', 'activo')
                ->lockForUpdate()
                ->first();

            if (!$donacion || !$beneficiario) {
                throw new LogicException('La donación debe estar verificada y el beneficiario activo.');
            }

            return (int) DB::table('entregas')->insertGetId([
                'id_donacion' => $datos['id_donacion'],
                'id_beneficiario' => $datos['id_beneficiario'],
                'id_usuario_responsable' => $idUsuario,
                'cantidad_entregada' => $datos['cantidad_entregada'],
                'fecha_entrega' => $datos['fecha_entrega'],
                'evidencia_url' => $datos['evidencia_url'] ?? null,
                'estado' => 'programada',
                'observaciones' => $datos['observaciones'] ?? null,
            ], 'id_entrega');
        }, 3);
    }

    public function actualizar(int $id, array $datos): void
    {
        DB::transaction(function () use ($id, $datos): void {
            $entrega = DB::table('entregas')->where('id_entrega', $id)->lockForUpdate()->first();
            if (!$entrega || !in_array($entrega->estado, ['programada', 'en_proceso'], true)) {
                throw new LogicException('Solo se pueden editar entregas programadas o en proceso.');
            }
            $beneficiario = DB::table('beneficiarios')
                ->where('id_beneficiario', $datos['id_beneficiario'])
                ->lockForUpdate()
                ->first();
            if (!$beneficiario) {
                throw new LogicException('El beneficiario seleccionado no está activo.');
            }

            DB::table('entregas')->where('id_entrega', $id)->update([
                'id_beneficiario' => $datos['id_beneficiario'],
                'cantidad_entregada' => $datos['cantidad_entregada'],
                'fecha_entrega' => $datos['fecha_entrega'],
                'evidencia_url' => $datos['evidencia_url'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);
        }, 3);
    }

    public function eliminar(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $entrega = DB::table('entregas')->where('id_entrega', $id)->lockForUpdate()->first();
            if (!$entrega || !in_array($entrega->estado, ['programada', 'en_proceso'], true)) {
                throw new LogicException('Solo se pueden eliminar entregas programadas o en proceso.');
            }
            DB::table('entregas')->where('id_entrega', $id)->delete();
        }, 3);
    }

    public function cambiarEstado(int $id, string $nuevoEstado, int $idUsuario): void
    {
        DB::transaction(function () use ($id, $nuevoEstado, $idUsuario): void {
            $entrega = DB::table('entregas')->where('id_entrega', $id)->lockForUpdate()->first();
            $transiciones = [
                'programada' => ['en_proceso', 'completada', 'cancelada'],
                'en_proceso' => ['completada', 'cancelada'],
                'completada' => ['cancelada'],
                'cancelada' => [],
            ];
            if (!$entrega || !in_array($nuevoEstado, $transiciones[$entrega->estado] ?? [], true)) {
                throw new LogicException('La transición de estado no está permitida.');
            }

            if (in_array($nuevoEstado, ['completada', 'cancelada'], true) && $entrega->estado === 'completada') {
                $this->revertirSalidaInventario($entrega, $idUsuario);
            } elseif ($nuevoEstado === 'completada') {
                $this->registrarSalidaInventario($entrega, $idUsuario);
            }

            DB::table('entregas')->where('id_entrega', $id)->update(['estado' => $nuevoEstado]);
        }, 3);
    }

    private function registrarSalidaInventario(object $entrega, int $idUsuario): void
    {
        $donacion = DB::table('donaciones')->where('id_donacion', $entrega->id_donacion)->lockForUpdate()->first();
        if (!$donacion || $donacion->estado !== 'verificada') {
            throw new LogicException('La donación de la entrega ya no está verificada.');
        }
        $especie = DB::table('donaciones_especie')
            ->where('id_donacion', $entrega->id_donacion)
            ->lockForUpdate()
            ->first();
        if (!$especie) {
            return;
        }

        $inventario = app(InventarioService::class);
        if (!$inventario->registrarSalida(
            (int) $especie->id_tipo_bien,
            (float) $entrega->cantidad_entregada,
            $idUsuario,
            'entrega',
            (int) $entrega->id_entrega
        )) {
            throw new LogicException('No hay suficiente inventario para completar esta entrega.');
        }
    }

    private function revertirSalidaInventario(object $entrega, int $idUsuario): void
    {
        DB::table('donaciones')->where('id_donacion', $entrega->id_donacion)->lockForUpdate()->first();
        $especie = DB::table('donaciones_especie')
            ->where('id_donacion', $entrega->id_donacion)
            ->lockForUpdate()
            ->first();
        if (!$especie) {
            return;
        }

        $inventario = app(InventarioService::class);
        if (!$inventario->registrarEntrada(
            (int) $especie->id_tipo_bien,
            (float) $entrega->cantidad_entregada,
            $idUsuario,
            'entrega',
            (int) $entrega->id_entrega,
            'Reversión por cancelación de entrega completada'
        )) {
            throw new RuntimeException('No se pudo revertir el inventario; la entrega permanece completada.');
        }
    }

    public function notificarCompletada(int $idEntrega): void
    {
        try {
            $detalle = DB::table('entregas as e')
                ->join('beneficiarios as b', 'b.id_beneficiario', '=', 'e.id_beneficiario')
                ->leftJoin('beneficiarios_fisicos as bf', 'bf.id_beneficiario', '=', 'b.id_beneficiario')
                ->leftJoin('beneficiarios_morales as bm', 'bm.id_beneficiario', '=', 'b.id_beneficiario')
                ->where('e.id_entrega', $idEntrega)
                ->selectRaw("COALESCE(bm.razon_social, CONCAT(COALESCE(bf.nombre, ''), ' ', COALESCE(bf.apellido, ''))) AS beneficiario, e.cantidad_entregada")
                ->first();
            if (!$detalle) {
                return;
            }

            app(NotificacionService::class)->entregaCompletada(
                $idEntrega,
                (string) $detalle->beneficiario,
                (string) $detalle->cantidad_entregada
            );
        } catch (\Throwable $error) {
            Log::error('No se pudo preparar notificación de entrega completada.', ['id_entrega' => $idEntrega, 'error' => $error->getMessage()]);
        }
    }
}
