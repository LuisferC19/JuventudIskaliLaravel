<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonadorMoral extends Model
{
    protected $table = 'donadores_morales';

    protected $primaryKey = 'id_donador';

    public $timestamps = false;
    protected $fillable = [
        'id_donador',
        'razon_social',
        'rfc',
        'representante_legal',
        'giro_comercial',
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
