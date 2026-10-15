<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reconocimiento extends Model
{
    protected $table = 'reconocimientos';

    protected $primaryKey = 'id_reconocimiento';

    public $timestamps = false;
    protected $fillable = [
        'id_donador',
        'id_usuario',
        'id_campana',
        'tipo',
        'descripcion',
        'archivo_pdf_url',
        'fecha_emision',
        'id_usuario_emisor',
        'otorgado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'datetime',
        ];
    }
}
