<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class DiplomaService
{
    public function siguienteFolio(): string
    {
        return DB::transaction(function (): string {
            $anio = now()->year;
            $prefijo = "ISK-{$anio}-";
            $filas = DB::table('reconocimientos')
                ->where('folio', 'like', $prefijo.'%')
                ->lockForUpdate()
                ->pluck('folio');

            $maximo = 0;
            foreach ($filas as $folio) {
                if (preg_match('/^ISK-\d{4}-(\d+)$/', (string) $folio, $coincidencia)) {
                    $maximo = max($maximo, (int) $coincidencia[1]);
                }
            }

            return $prefijo.str_pad((string) ($maximo + 1), 4, '0', STR_PAD_LEFT);
        });
    }

    public function emitir(int $idDonador, ?int $idCampana, string $descripcion, ?string $otorgadoPor, int $idUsuarioEmisor): int
    {
        return DB::transaction(function () use ($idDonador, $idCampana, $descripcion, $otorgadoPor, $idUsuarioEmisor): int {
            $folio = $this->siguienteFolio();
            $id = DB::table('reconocimientos')->insertGetId([
                'id_donador' => $idDonador,
                'id_campana' => $idCampana,
                'tipo' => 'diploma',
                'descripcion' => $descripcion,
                'folio' => $folio,
                'fecha_emision' => now(),
                'id_usuario_emisor' => $idUsuarioEmisor,
                'otorgado_por' => $otorgadoPor ?: config('iskali.institucion.firma_nombre'),
            ], 'id_reconocimiento');

            return (int) $id;
        });
    }

    public function pdf(int $idReconocimiento): string
    {
        $reconocimiento = DB::table('reconocimientos')->where('id_reconocimiento', $idReconocimiento)->first();
        abort_if(! $reconocimiento, 404);

        $donador = DB::table('donadores as d')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'd.id_donador')
            ->where('d.id_donador', $reconocimiento->id_donador)
            ->select('d.tipo_donante', 'df.nombre', 'df.apellido', 'dm.razon_social', 'dg.nombre_grupo')
            ->first();

        $receptor = 'Donador anónimo';
        if ($donador) {
            $receptor = match ($donador->tipo_donante) {
                'persona' => trim(($donador->nombre ?? '').' '.($donador->apellido ?? '')) ?: 'Donador',
                'organizacion' => $donador->razon_social ?: 'Organización donante',
                'grupo' => $donador->nombre_grupo ?: 'Grupo donante',
                default => 'Donador anónimo',
            };
        }

        $campana = $reconocimiento->id_campana
            ? DB::table('campanas')->where('id_campana', $reconocimiento->id_campana)->value('nombre')
            : null;

        return Pdf::loadView('reconocimientos.diploma', [
            'reconocimiento' => $reconocimiento,
            'receptor' => $receptor,
            'campana' => $campana,
        ])->setPaper('letter', 'landscape')->output();
    }
}
