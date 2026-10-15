<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonadorGrupo extends Model
{
    protected $table = 'donadores_grupos';

    protected $primaryKey = 'id_donador';

    public $timestamps = false;
    protected $fillable = [
        'id_donador',
        'nombre_grupo',
        'representante',
    ];

    protected function casts(): array
    {
        return [

        ];
    }

    public function donador(): BelongsTo
    {
        return $this->belongsTo(Donador::class, 'id_donador', 'id_donador');
    }
}
