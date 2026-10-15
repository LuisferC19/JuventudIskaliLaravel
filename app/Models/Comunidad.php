<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comunidad extends Model
{
    protected $table = 'comunidades';

    protected $primaryKey = 'id_comunidad';

    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'municipio',
        'estado',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
