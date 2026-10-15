<?php

namespace App\Contracts;

interface InventarioService
{
    // Implementations must use Laravel's current DB connection and leave transaction ownership to the caller.
    public function registrarEntrada(
        int $idTipoBien,
        float $cantidad,
        int $idUsuario,
        string $tipoReferencia,
        ?int $idReferencia,
        ?string $motivo = null
    ): bool;

    public function registrarSalida(
        int $idTipoBien,
        float $cantidad,
        int $idUsuario,
        string $tipoReferencia,
        ?int $idReferencia,
        ?string $motivo = null
    ): bool;
}
