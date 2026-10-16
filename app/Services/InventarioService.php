<?php

namespace App\Services;

use App\Contracts\InventarioService as InventarioServiceContract;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventarioService implements InventarioServiceContract
{
    private const REFERENCIAS_VALIDAS = ['donacion', 'entrega', 'ajuste_manual'];

    public function registrarEntrada(
        int $id_tipo_bien,
        float $cantidad,
        int $id_usuario,
        string $tipo_referencia,
        ?int $id_referencia,
        ?string $motivo = null
    ): bool {
        if (! $this->movimientoValido($id_tipo_bien, $cantidad, $id_usuario, $tipo_referencia)) {
            return false;
        }

        return DB::transaction(function () use (
            $id_tipo_bien,
            $cantidad,
            $id_usuario,
            $tipo_referencia,
            $id_referencia,
            $motivo
        ): bool {
            $inventario = $this->obtenerOFilaInventario($id_tipo_bien, $id_usuario);
            $anterior = (float) $inventario->cantidad_total_recibida;
            $posterior = $anterior + $cantidad;

            DB::table('inventario')
                ->where('id_inventario', $inventario->id_inventario)
                ->update([
                    'cantidad_total_recibida' => $posterior,
                    'id_usuario_actualizador' => $id_usuario,
                ]);

            $this->insertarMovimiento(
                (int) $inventario->id_inventario,
                $id_usuario,
                'entrada',
                $cantidad,
                $anterior,
                $posterior,
                $tipo_referencia,
                $id_referencia,
                $motivo
            );

            return true;
        });
    }

    public function registrarSalida(
        int $id_tipo_bien,
        float $cantidad,
        int $id_usuario,
        string $tipo_referencia,
        ?int $id_referencia,
        ?string $motivo = null
    ): bool {
        if (! $this->movimientoValido($id_tipo_bien, $cantidad, $id_usuario, $tipo_referencia)) {
            return false;
        }

        return DB::transaction(function () use (
            $id_tipo_bien,
            $cantidad,
            $id_usuario,
            $tipo_referencia,
            $id_referencia,
            $motivo
        ): bool {
            $inventario = DB::table('inventario')
                ->where('id_tipo_bien', $id_tipo_bien)
                ->lockForUpdate()
                ->first();
            if (! $inventario) {
                return false;
            }
            if ((float) $inventario->cantidad_disponible < $cantidad) {
                return false;
            }

            $anterior = (float) $inventario->cantidad_total_entregada;
            $posterior = $anterior + $cantidad;

            DB::table('inventario')
                ->where('id_inventario', $inventario->id_inventario)
                ->update([
                    'cantidad_total_entregada' => $posterior,
                    'id_usuario_actualizador' => $id_usuario,
                ]);

            $this->insertarMovimiento(
                (int) $inventario->id_inventario,
                $id_usuario,
                'salida',
                $cantidad,
                $anterior,
                $posterior,
                $tipo_referencia,
                $id_referencia,
                $motivo
            );

            return true;
        });
    }

    public function registrarAjusteManual(
        int $id_tipo_bien,
        float $nuevaCantidadRecibida,
        float $nuevaCantidadEntregada,
        int $id_usuario,
        string $motivo
    ): bool {
        $motivo = trim($motivo);
        if (
            $id_tipo_bien <= 0
            || $id_usuario <= 0
            || ! is_finite($nuevaCantidadRecibida)
            || ! is_finite($nuevaCantidadEntregada)
            || $nuevaCantidadRecibida < 0
            || $nuevaCantidadEntregada < 0
            || $nuevaCantidadEntregada > $nuevaCantidadRecibida
            || $motivo === ''
        ) {
            return false;
        }

        return DB::transaction(function () use (
            $id_tipo_bien,
            $nuevaCantidadRecibida,
            $nuevaCantidadEntregada,
            $id_usuario,
            $motivo
        ): bool {
            $inventario = $this->obtenerOFilaInventario($id_tipo_bien, $id_usuario);
            $disponibleAnterior = (float) $inventario->cantidad_disponible;
            $disponiblePosterior = $nuevaCantidadRecibida - $nuevaCantidadEntregada;
            DB::table('inventario')
                ->where('id_inventario', $inventario->id_inventario)
                ->update([
                    'cantidad_total_recibida' => $nuevaCantidadRecibida,
                    'cantidad_total_entregada' => $nuevaCantidadEntregada,
                    'id_usuario_actualizador' => $id_usuario,
                ]);

            $this->insertarMovimiento(
                (int) $inventario->id_inventario,
                $id_usuario,
                'ajuste',
                abs($disponiblePosterior - $disponibleAnterior),
                $disponibleAnterior,
                $disponiblePosterior,
                'ajuste_manual',
                null,
                $motivo
            );

            return true;
        });
    }

    private function movimientoValido(
        int $id_tipo_bien,
        float $cantidad,
        int $id_usuario,
        string $tipo_referencia
    ): bool {
        return $id_tipo_bien > 0
            && $id_usuario > 0
            && is_finite($cantidad)
            && $cantidad > 0
            && in_array($tipo_referencia, self::REFERENCIAS_VALIDAS, true);
    }

    private function obtenerOFilaInventario(int $id_tipo_bien, int $id_usuario): object
    {
        if (! DB::table('tipos_bien')->where('id_tipo_bien', $id_tipo_bien)->exists()) {
            throw new RuntimeException('El tipo de bien no existe.');
        }

        DB::table('inventario')->insertOrIgnore([
            'id_tipo_bien' => $id_tipo_bien,
            'cantidad_total_recibida' => 0,
            'cantidad_total_entregada' => 0,
            'id_usuario_actualizador' => $id_usuario,
        ]);

        $inventario = DB::table('inventario')
            ->where('id_tipo_bien', $id_tipo_bien)
            ->lockForUpdate()
            ->first();

        if (! $inventario) {
            throw new RuntimeException('No se pudo obtener la fila de inventario.');
        }

        return $inventario;
    }

    private function insertarMovimiento(
        int $id_inventario,
        int $id_usuario,
        string $tipo_movimiento,
        float $cantidad,
        float $anterior,
        float $posterior,
        string $tipo_referencia,
        ?int $id_referencia,
        ?string $motivo
    ): void {
        DB::table('movimientos_inventario')->insert([
            'id_inventario' => $id_inventario,
            'id_usuario' => $id_usuario,
            'tipo_movimiento' => $tipo_movimiento,
            'cantidad' => $cantidad,
            'cantidad_anterior' => $anterior,
            'cantidad_posterior' => $posterior,
            'tipo_referencia' => $tipo_referencia,
            'id_referencia' => $id_referencia,
            'motivo' => $motivo,
        ]);
    }

}
