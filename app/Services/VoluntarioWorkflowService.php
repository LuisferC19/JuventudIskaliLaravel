<?php

namespace App\Services;

use App\Models\AsistenciaVoluntario;
use App\Models\Voluntario;
use Illuminate\Support\Facades\DB;
use LogicException;

class VoluntarioWorkflowService
{
    public function crearAsignacion(array $datos, int $idUsuarioAsignador): int
    {
        return DB::transaction(function () use ($datos, $idUsuarioAsignador): int {
            $parExiste = DB::table('asignaciones_voluntario')
                ->where('id_voluntario', $datos['id_voluntario'])
                ->where('id_entrega', $datos['id_entrega'])
                ->whereNotIn('estado', ['cancelada', 'rechazada'])
                ->exists();

            if ($parExiste) {
                throw new LogicException('Este voluntario ya tiene una asignación activa para la entrega seleccionada.');
            }

            return (int) DB::table('asignaciones_voluntario')->insertGetId([
                'id_voluntario' => $datos['id_voluntario'],
                'id_entrega' => $datos['id_entrega'],
                'id_usuario_asignador' => $idUsuarioAsignador,
                'fecha_asignacion' => now(),
                'fecha_compromiso' => $datos['fecha_compromiso'],
                'estado' => 'asignada',
                'observaciones_voluntario' => $datos['observaciones_voluntario'] ?? null,
                'fecha_actualizacion' => now(),
            ], 'id_asignacion');
        });
    }

    public function actualizarAsignacion(int $idAsignacion, array $datos): void
    {
        $asignacion = DB::table('asignaciones_voluntario')->where('id_asignacion', $idAsignacion);
        abort_unless($asignacion->exists(), 404);

        $cambios = [
            'estado' => $datos['estado'],
            'fecha_actualizacion' => now(),
        ];
        if (array_key_exists('fecha_compromiso', $datos)) {
            $cambios['fecha_compromiso'] = $datos['fecha_compromiso'];
        }
        if (array_key_exists('observaciones_voluntario', $datos)) {
            $cambios['observaciones_voluntario'] = $datos['observaciones_voluntario'];
        }

        $asignacion->update($cambios);
    }

    public function eliminarAsignacion(int $idAsignacion): void
    {
        abort_unless(DB::table('asignaciones_voluntario')->where('id_asignacion', $idAsignacion)->delete() > 0, 404);
    }

    /**
     * A unique attendance row and opaque token are issued to each active volunteer
     * for the selected activity.
     */
    public function generarTokens(int $idActividad): array
    {
        return DB::transaction(function () use ($idActividad): array {
            $actividad = DB::table('actividades')->where('id_actividad', $idActividad)->first();
            abort_unless($actividad && in_array($actividad->estado, ['planeada', 'en_curso'], true), 404);

            $voluntarios = Voluntario::query()
                ->where('activo', true)
                ->with('usuario:id_usuario,nombre,apellido')
                ->orderBy('id_voluntario')
                ->get();

            $tokens = [];
            foreach ($voluntarios as $voluntario) {
                $asistencia = AsistenciaVoluntario::query()
                    ->where('id_voluntario', $voluntario->id_voluntario)
                    ->where('id_actividad', $idActividad)
                    ->lockForUpdate()
                    ->first();

                if (! $asistencia) {
                    $asistencia = AsistenciaVoluntario::create([
                        'id_voluntario' => $voluntario->id_voluntario,
                        'id_actividad' => $idActividad,
                        'metodo' => 'qr',
                        'presente' => false,
                        'token_qr' => $this->tokenUnico(),
                    ]);
                } elseif (! $asistencia->token_qr) {
                    $asistencia->token_qr = $this->tokenUnico();
                    $asistencia->save();
                }

                $tokens[] = [
                    'id_voluntario' => $voluntario->id_voluntario,
                    'nombre' => trim(($voluntario->usuario?->nombre ?? '').' '.($voluntario->usuario?->apellido ?? '')),
                    'token' => $asistencia->token_qr,
                ];
            }

            return $tokens;
        });
    }

    public function validarToken(string $token): bool
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        return DB::transaction(function () use ($token): bool {
            $asistencia = AsistenciaVoluntario::query()->where('token_qr', $token)->lockForUpdate()->first();
            if (! $asistencia) {
                return false;
            }

            $actividadActiva = DB::table('actividades')
                ->where('id_actividad', $asistencia->id_actividad)
                ->whereIn('estado', ['planeada', 'en_curso'])
                ->exists();
            $voluntarioActivo = DB::table('voluntarios')
                ->where('id_voluntario', $asistencia->id_voluntario)
                ->where('activo', true)
                ->exists();

            if (! $actividadActiva || ! $voluntarioActivo) {
                return false;
            }

            if ($asistencia->presente) {
                return true;
            }

            $asistencia->forceFill([
                'presente' => true,
                'metodo' => 'qr',
                'fecha_hora' => now(),
            ])->save();

            return true;
        });
    }

    public function registrarAsistenciaManual(int $idVoluntario, int $idActividad): void
    {
        $voluntarioActivo = DB::table('voluntarios')
            ->where('id_voluntario', $idVoluntario)
            ->where('activo', true)
            ->exists();
        $actividadActiva = DB::table('actividades')
            ->where('id_actividad', $idActividad)
            ->whereIn('estado', ['planeada', 'en_curso'])
            ->exists();

        abort_unless($voluntarioActivo && $actividadActiva, 422, 'El voluntario o la actividad no están disponibles.');

        DB::transaction(function () use ($idVoluntario, $idActividad): void {
            $asistencia = AsistenciaVoluntario::query()
                ->where('id_voluntario', $idVoluntario)
                ->where('id_actividad', $idActividad)
                ->lockForUpdate()
                ->first();

            if ($asistencia) {
                $asistencia->forceFill(['presente' => true, 'metodo' => 'manual', 'fecha_hora' => now()])->save();
                return;
            }

            AsistenciaVoluntario::create([
                'id_voluntario' => $idVoluntario,
                'id_actividad' => $idActividad,
                'presente' => true,
                'metodo' => 'manual',
                'fecha_hora' => now(),
            ]);
        });
    }

    private function tokenUnico(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (DB::table('asistencia_voluntarios')->where('token_qr', $token)->exists());

        return $token;
    }
}
