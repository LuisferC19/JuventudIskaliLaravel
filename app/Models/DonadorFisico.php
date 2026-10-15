<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonadorFisico extends Model
{
    protected $table = 'donadores_fisicos';

    protected $primaryKey = 'id_donador';

    public $timestamps = false;
    protected $fillable = [
        'id_donador',
        'nombre',
        'apellido',
        'curp',
        'fecha_nacimiento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function donador(): BelongsTo
    {
        return $this->belongsTo(Donador::class, 'id_donador', 'id_donador');
    }
}
