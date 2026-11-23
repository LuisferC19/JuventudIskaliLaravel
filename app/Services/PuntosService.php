<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use DomainException;
use Illuminate\Support\Collection;

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

    public function calificaCampamento(int $idVoluntario, int $umbral): bool
    {
        $bolsa = DB::table('bolsa_voluntario')
            ->where('id_voluntario', $idVoluntario)
            ->first();

        return $bolsa !== null
            && ! (bool) $bolsa->campamento_bloqueado
            && (int) $bolsa->puntos_campamento >= $umbral;
    }

    public function campamentosParaVoluntario(int $idVoluntario): Collection
    {
        $voluntario = DB::table('voluntarios')
            ->where('id_voluntario', $idVoluntario)
            ->where('activo', true)
            ->first();
        if (! $voluntario) {
            return collect();
        }

        return DB::table('campamentos')
            ->where('estado', 'abierto')
            ->where(function ($query) use ($voluntario): void {
                $query->where('zona', 'ambas')
                    ->orWhere('zona', $voluntario->zona_asignada);
                if ($voluntario->zona_asignada === 'ambas') {
                    $query->orWhereIn('zona', ['san_martin', 'tlaxcala']);
                }
            })
            ->orderBy('fecha_inicio')
            ->get()
            ->filter(fn ($campamento): bool => $this->calificaCampamento(
                $idVoluntario,
                (int) $campamento->umbral_puntos
            ))
            ->values();
    }

    public function voluntariosQueCalifican(int $idCampamento): Collection
    {
        $campamento = DB::table('campamentos')
            ->where('id_campamento', $idCampamento)
            ->first();
        if (! $campamento) {
            throw new DomainException('El campamento indicado no existe.');
        }

        $consulta = DB::table('voluntarios as v')
            ->join('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
            ->join('bolsa_voluntario as b', 'b.id_voluntario', '=', 'v.id_voluntario')
            ->where('v.activo', true)
            ->where('b.campamento_bloqueado', false)
            ->where('b.puntos_campamento', '>=', $campamento->umbral_puntos);
        if ($campamento->zona !== 'ambas') {
            $consulta->where(function ($query) use ($campamento): void {
                $query->where('v.zona_asignada', $campamento->zona)
                    ->orWhere('v.zona_asignada', 'ambas');
            });
        }

        return $consulta->orderBy('u.nombre')
            ->orderBy('u.apellido')
            ->get([
                'v.id_voluntario',
                'v.zona_asignada',
                'u.nombre',
                'u.apellido',
                'b.puntos_canjeables',
                'b.puntos_campamento',
                'b.faltas_injustificadas',
                'b.campamento_bloqueado',
            ]);
    }

    public function penalizar(
        int $idVoluntario,
        int $idActividad,
        string $tipo,
        ?string $motivo,
        ?int $idUsuario
    ): int {
        if (! in_array($tipo, ['retardo', 'inasistencia_injustificada'], true)) {
            throw new DomainException('El tipo de penalización no es válido.');
        }

        return DB::transaction(function () use ($idVoluntario, $idActividad, $tipo, $motivo, $idUsuario): int {
            if (! DB::table('voluntarios')->where('id_voluntario', $idVoluntario)->exists()) {
                throw new DomainException('El voluntario indicado no existe.');
            }
            if (! DB::table('actividades')->where('id_actividad', $idActividad)->exists()) {
                throw new DomainException('La actividad indicada no existe.');
            }

            $bolsa = $this->bolsaDe($idVoluntario);
            $yaPenalizado = DB::table('penalizaciones_asistencia')
                ->where('id_voluntario', $idVoluntario)
                ->where('id_actividad', $idActividad)
                ->exists();
            if ($yaPenalizado) {
                throw new DomainException('Este voluntario ya tiene una penalización registrada para esa actividad.');
            }

            $puntosDescontados = (int) config(
                $tipo === 'retardo' ? 'iskali.penalizacion_retardo' : 'iskali.penalizacion_inasistencia'
            );
            $descuentoCanjeables = min((int) $bolsa->puntos_canjeables, $puntosDescontados);
            $descuentoCampamento = min((int) $bolsa->puntos_campamento, $puntosDescontados);
            $faltas = (int) $bolsa->faltas_injustificadas;
            if ($tipo === 'inasistencia_injustificada') {
                $faltas++;
            }

            DB::table('bolsa_voluntario')
                ->where('id_bolsa', $bolsa->id_bolsa)
                ->update([
                    'puntos_canjeables' => (int) $bolsa->puntos_canjeables - $descuentoCanjeables,
                    'puntos_campamento' => (int) $bolsa->puntos_campamento - $descuentoCampamento,
                    'faltas_injustificadas' => $faltas,
                    'campamento_bloqueado' => $faltas >= 2,
                    'updated_at' => now(),
                ]);

            $idPenalizacion = (int) DB::table('penalizaciones_asistencia')->insertGetId([
                'id_voluntario' => $idVoluntario,
                'id_actividad' => $idActividad,
                'tipo' => $tipo,
                'puntos_descontados' => $puntosDescontados,
                'motivo' => $motivo,
                'id_usuario_registra' => $idUsuario,
                'fecha' => now(),
                'anulada' => false,
            ], 'id_penalizacion');
            DB::table('movimientos_puntos')->insert([
                'id_voluntario' => $idVoluntario,
                'tipo' => 'penalizacion',
                'puntos_canjeables' => -$descuentoCanjeables,
                'puntos_campamento' => -$descuentoCampamento,
                'id_actividad' => $idActividad,
                'motivo' => 'Penalización: '.$tipo,
                'id_usuario' => $idUsuario,
                'fecha' => now(),
            ]);

            return $idPenalizacion;
        });
    }

    public function anularPenalizacion(int $idPenalizacion, ?int $idUsuario): void
    {
        DB::transaction(function () use ($idPenalizacion, $idUsuario): void {
            $penalizacion = DB::table('penalizaciones_asistencia')
                ->where('id_penalizacion', $idPenalizacion)
                ->lockForUpdate()
                ->first();
            if (! $penalizacion) {
                throw new DomainException('La penalización indicada no existe.');
            }
            if ((bool) $penalizacion->anulada) {
                throw new DomainException('La penalización ya fue anulada.');
            }

            $movimiento = DB::table('movimientos_puntos')
                ->where('id_voluntario', $penalizacion->id_voluntario)
                ->where('id_actividad', $penalizacion->id_actividad)
                ->where('tipo', 'penalizacion')
                ->lockForUpdate()
                ->first();
            if (! $movimiento) {
                throw new DomainException('No se encontró el movimiento original de la penalización.');
            }
            if ((int) $movimiento->puntos_canjeables > 0 || (int) $movimiento->puntos_campamento > 0) {
                throw new DomainException('El movimiento original de la penalización no contiene descuentos válidos.');
            }

            $bolsa = $this->bolsaDe((int) $penalizacion->id_voluntario);
            $faltas = (int) $bolsa->faltas_injustificadas;
            if ($penalizacion->tipo === 'inasistencia_injustificada') {
                $faltas = max(0, $faltas - 1);
            }
            $actualizarBloqueo = $penalizacion->tipo === 'inasistencia_injustificada'
                ? $faltas >= 2
                : (bool) $bolsa->campamento_bloqueado;
            $devolucionCanjeables = -(int) $movimiento->puntos_canjeables;
            $devolucionCampamento = -(int) $movimiento->puntos_campamento;

            DB::table('bolsa_voluntario')
                ->where('id_bolsa', $bolsa->id_bolsa)
                ->update([
                    'puntos_canjeables' => (int) $bolsa->puntos_canjeables + $devolucionCanjeables,
                    'puntos_campamento' => (int) $bolsa->puntos_campamento + $devolucionCampamento,
                    'faltas_injustificadas' => $faltas,
                    'campamento_bloqueado' => $actualizarBloqueo,
                    'updated_at' => now(),
                ]);
            DB::table('penalizaciones_asistencia')
                ->where('id_penalizacion', $idPenalizacion)
                ->update(['anulada' => true]);
            DB::table('movimientos_puntos')->insert([
                'id_voluntario' => $penalizacion->id_voluntario,
                'tipo' => 'ajuste',
                'puntos_canjeables' => $devolucionCanjeables,
                'puntos_campamento' => $devolucionCampamento,
                'motivo' => 'Anulación de penalización',
                'id_usuario' => $idUsuario,
                'fecha' => now(),
            ]);
        });
    }
}
