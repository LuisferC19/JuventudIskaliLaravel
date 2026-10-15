<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoReferenciaNotif extends Model
{
    protected $table = 'tipos_referencia_notif';

    protected $primaryKey = 'id_tipo_ref';

    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
