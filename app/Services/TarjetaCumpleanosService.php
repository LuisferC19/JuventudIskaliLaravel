<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class TarjetaCumpleanosService
{
    public function generar(int $idVoluntario): string
    {
        $voluntario = DB::table('voluntarios as v')
            ->join('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
            ->where('v.id_voluntario', $idVoluntario)
            ->select('v.fecha_nacimiento', 'u.nombre', 'u.apellido')
            ->first();
        abort_if(! $voluntario, 404);

        $nombre = trim(($voluntario->nombre ?? '').' '.($voluntario->apellido ?? ''));
        $edad = null;
        if ($voluntario->fecha_nacimiento) {
            $edad = (int) now()->year - (int) \Illuminate\Support\Carbon::parse($voluntario->fecha_nacimiento)->year;
        }

        return Pdf::loadView('pdf.tarjeta_cumpleanos', [
            'nombre' => $nombre,
            'edad' => $edad,
        ])->setPaper([0, 0, 792, 612], 'landscape')->output();
    }
}
