<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use DomainException;

class PuntosService
{
    public function bolsaDe(int $idVoluntario): object
    {
        return DB::transaction(function () use ($idVoluntario): object {
            $bolsa = DB::table('bolsa_voluntario')
                ->where('id_voluntario', $idVoluntario)
                ->lockForUpdate()
                ->first();

            if (! $bolsa) {
                DB::table('bolsa_voluntario')->upsert([[
                    'id_voluntario' => $idVoluntario,
                    'puntos_canjeables' => 0,
                    'puntos_campamento' => 0,
                    'faltas_injustificadas' => 0,
                    'campamento_bloqueado' => false,
                ]], ['id_voluntario'], ['id_voluntario']);

                $bolsa = DB::table('bolsa_voluntario')
                    ->where('id_voluntario', $idVoluntario)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $bolsa) {
                throw new DomainException('No fue posible obtener la bolsa de puntos del voluntario.');
            }

            return $bolsa;
        });
    }

    public function otorgarPorAsistencia(int $idVoluntario, int $idActividad, ?int $idUsuario = null): bool
    {
        return DB::transaction(function () use ($idVoluntario, $idActividad, $idUsuario): bool {
            $bolsa = $this->bolsaDe($idVoluntario);
            $yaOtorgado = DB::table('movimientos_puntos')
                ->where('id_voluntario', $idVoluntario)
                ->where('id_actividad', $idActividad)
                ->where('tipo', 'asistencia')
                ->exists();
            if ($yaOtorgado) {
                return false;
            }

            $puntos = DB::table('actividades')
                ->where('id_actividad', $idActividad)
                ->value('puntos');
            if ($puntos === null) {
                throw new DomainException('La actividad indicada no existe.');
            }
            $puntos = (int) $puntos;

            DB::table('bolsa_voluntario')
                ->where('id_bolsa', $bolsa->id_bolsa)
                ->update([
                    'puntos_canjeables' => $bolsa->puntos_canjeables + $puntos,
                    'puntos_campamento' => $bolsa->puntos_campamento + $puntos,
                    'updated_at' => now(),
                ]);
            DB::table('movimientos_puntos')->insert([
                'id_voluntario' => $idVoluntario,
                'tipo' => 'asistencia',
                'puntos_canjeables' => $puntos,
                'puntos_campamento' => $puntos,
                'id_actividad' => $idActividad,
                'motivo' => 'Asistencia a actividad',
                'id_usuario' => $idUsuario,
                'fecha' => now(),
            ]);

            return true;
        });
    }

    public function canjear(int $idVoluntario, int $idPremio, ?int $idUsuario = null): int
    {
        return DB::transaction(function () use ($idVoluntario, $idPremio, $idUsuario): int {
            $premio = DB::table('cat_premios')
                ->where('id_premio', $idPremio)
                ->lockForUpdate()
                ->first();
            if (! $premio || ! $premio->activo) {
                throw new DomainException('El premio no existe o está inactivo.');
            }
            if ($premio->stock < 1) {
                throw new DomainException('El premio no tiene existencias disponibles.');
            }

            $bolsa = $this->bolsaDe($idVoluntario);
            $costo = (int) $premio->costo_puntos;
            if ($bolsa->puntos_canjeables < $costo) {
                throw new DomainException('El voluntario no tiene puntos canjeables suficientes para este premio.');
            }

            DB::table('bolsa_voluntario')
                ->where('id_bolsa', $bolsa->id_bolsa)
                ->update([
                    'puntos_canjeables' => $bolsa->puntos_canjeables - $costo,
                    'updated_at' => now(),
                ]);
            DB::table('cat_premios')
                ->where('id_premio', $idPremio)
                ->update(['stock' => $premio->stock - 1]);
            $idCanje = (int) DB::table('canjes_premios')->insertGetId([
                'id_voluntario' => $idVoluntario,
                'id_premio' => $idPremio,
                'puntos_usados' => $costo,
                'estado' => 'pendiente',
                'fecha_solicitud' => now(),
                'id_usuario_registra' => $idUsuario,
            ], 'id_canje');
            DB::table('movimientos_puntos')->insert([
                'id_voluntario' => $idVoluntario,
                'tipo' => 'canje',
                'puntos_canjeables' => -$costo,
                'puntos_campamento' => 0,
                'id_canje' => $idCanje,
                'motivo' => 'Canje: '.$premio->nombre,
                'id_usuario' => $idUsuario,
                'fecha' => now(),
            ]);

            return $idCanje;
        });
    }

    public function marcarEntregado(int $idCanje): void
    {
        DB::transaction(function () use ($idCanje): void {
            $canje = DB::table('canjes_premios')
                ->where('id_canje', $idCanje)
                ->lockForUpdate()
                ->first();
            if (! $canje || $canje->estado !== 'pendiente') {
                throw new DomainException('Solo se pueden entregar canjes pendientes.');
            }

            DB::table('canjes_premios')
                ->where('id_canje', $idCanje)
                ->update(['estado' => 'entregado', 'fecha_entrega' => now()]);
        });
    }

    public function cancelarCanje(int $idCanje, ?int $idUsuario = null): void
    {
        DB::transaction(function () use ($idCanje, $idUsuario): void {
            $canje = DB::table('canjes_premios')
                ->where('id_canje', $idCanje)
                ->lockForUpdate()
                ->first();
            if (! $canje || $canje->estado !== 'pendiente') {
                throw new DomainException('Solo se pueden cancelar canjes pendientes.');
            }

            $premio = DB::table('cat_premios')
                ->where('id_premio', $canje->id_premio)
                ->lockForUpdate()
                ->first();
            if (! $premio) {
                throw new DomainException('No se encontró el premio relacionado con el canje.');
            }
            $bolsa = $this->bolsaDe((int) $canje->id_voluntario);
            $costo = (int) $canje->puntos_usados;

            DB::table('canjes_premios')
                ->where('id_canje', $idCanje)
                ->update(['estado' => 'cancelado']);
            DB::table('bolsa_voluntario')
                ->where('id_bolsa', $bolsa->id_bolsa)
                ->update([
                    'puntos_canjeables' => $bolsa->puntos_canjeables + $costo,
                    'updated_at' => now(),
                ]);
            DB::table('cat_premios')
                ->where('id_premio', $premio->id_premio)
                ->update(['stock' => $premio->stock + 1]);
            DB::table('movimientos_puntos')->insert([
                'id_voluntario' => $canje->id_voluntario,
                'tipo' => 'ajuste',
                'puntos_canjeables' => $costo,
                'puntos_campamento' => 0,
                'id_canje' => $idCanje,
                'motivo' => 'Cancelación de canje',
                'id_usuario' => $idUsuario,
                'fecha' => now(),
            ]);
        });
    }

    public function calificaCampamento(int $idVoluntario): bool
    {
        $bolsa = DB::table('bolsa_voluntario')
            ->where('id_voluntario', $idVoluntario)
            ->first();

        return $bolsa !== null
            && ! (bool) $bolsa->campamento_bloqueado
            && $bolsa->puntos_campamento >= (int) config('iskali.umbral_campamento');
    }
}
