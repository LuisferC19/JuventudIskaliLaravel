<?php

namespace App\Services;

use App\Models\Notificacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificacionService
{
    public function crear(
        int $idUsuario,
        string $tipo,
        string $asunto,
        string $mensaje,
        ?string $referencia = null,
        ?int $idReferencia = null
    ): void {
        $idTipoReferencia = $referencia
            ? DB::table('tipos_referencia_notif')->where('nombre', $referencia)->value('id_tipo_ref')
            : null;

        Notificacion::create([
            'id_usuario' => $idUsuario,
            'tipo' => $tipo,
            'canal' => 'web',
            'asunto' => $asunto,
            'mensaje' => $mensaje,
            'leida' => false,
            'id_tipo_referencia' => $idTipoReferencia,
            'id_referencia' => $idReferencia,
        ]);
    }

    public function notificarUsuarios(iterable $usuarios, string $tipo, string $asunto, string $mensaje, ?string $referencia = null, ?int $idReferencia = null): void
    {
        foreach ($usuarios as $idUsuario) {
            try {
                $this->crear((int) $idUsuario, $tipo, $asunto, $mensaje, $referencia, $idReferencia);
            } catch (Throwable $exception) {
                Log::error('No se pudo guardar una notificación.', [
                    'id_usuario' => (int) $idUsuario,
                    'tipo' => $tipo,
                    'exception' => $exception,
                ]);
            }
        }
    }

    public function notificarAdministradores(string $tipo, string $asunto, string $mensaje, ?string $referencia = null, ?int $idReferencia = null): void
    {
        $usuarios = DB::table('usuarios')
            ->whereIn('id_rol', function ($query): void {
                $query->select('id_rol')
                    ->from('roles')
                    ->whereIn('nombre', ['Administrador', 'Coordinador']);
            })
            ->pluck('id_usuario');

        $this->notificarUsuarios($usuarios, $tipo, $asunto, $mensaje, $referencia, $idReferencia);
    }

    public function notificarUsuariosActivos(string $tipo, string $asunto, string $mensaje, ?string $referencia = null, ?int $idReferencia = null): void
    {
        $this->notificarUsuarios(
            DB::table('usuarios')->where('activo', true)->pluck('id_usuario'),
            $tipo,
            $asunto,
            $mensaje,
            $referencia,
            $idReferencia
        );
    }

    public function campanaActiva(int $idCampana, string $nombre, string $fechaCierre): void
    {
        try {
            $this->notificarUsuariosActivos(
                'campana',
                'Nueva campaña activa',
                "La campaña {$nombre} está activa. Fecha de cierre: {$fechaCierre}.",
                'campana',
                $idCampana
            );
        } catch (Throwable $exception) {
            Log::error('No se pudieron preparar las notificaciones de campaña activa.', [
                'id_campana' => $idCampana,
                'exception' => $exception,
            ]);
        }
    }

    public function donacionNueva(int $idDonacion, string $donador, string $campana): void
    {
        $this->notificarAdministradores(
            'donacion',
            'Nueva donación registrada',
            'Donación de '.trim($donador).' para la campaña '.$campana.'.',
            'donacion',
            $idDonacion
        );
    }

    public function donacionVerificada(int $idUsuario, int $idDonacion, string $campana): void
    {
        $this->crear(
            $idUsuario,
            'donacion',
            'Tu donación fue verificada',
            "La donación para la campaña {$campana} fue verificada y ya puede generarse una entrega con ella.",
            'donacion',
            $idDonacion
        );
    }

    public function entregaCompletada(int $idEntrega, string $beneficiario, string $cantidad): void
    {
        $this->notificarAdministradores(
            'entrega',
            'Entrega completada',
            'Entrega a '.trim($beneficiario).' completada. Cantidad entregada: '.$cantidad.'.',
            'entrega',
            $idEntrega
        );
    }

    public function cuentaBloqueada(int $idUsuario, string $nombre, string $email): void
    {
        try {
            $this->notificarUsuarios(
                DB::table('usuarios')
                    ->whereIn('id_rol', function ($query): void {
                        $query->select('id_rol')
                            ->from('roles')
                            ->where('nombre', 'Administrador');
                    })
                    ->pluck('id_usuario'),
                'alerta',
                'Cuenta bloqueada por intentos fallidos',
                "La cuenta de {$nombre} ({$email}) alcanzó cinco intentos fallidos.",
                null,
                $idUsuario
            );
        } catch (Throwable $exception) {
            Log::error('No se pudieron preparar las notificaciones de cuenta bloqueada.', [
                'id_usuario' => $idUsuario,
                'exception' => $exception,
            ]);
        }
    }
}
