<?php

namespace Tests\Feature;

use App\Http\Requests\CampamentoRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class GamificacionRequestTest extends TestCase
{
    public function test_campamento_umbral_must_be_between_fifty_and_two_hundred(): void
    {
        $request = new CampamentoRequest();
        $datos = [
            'nombre' => 'Campamento de prueba',
            'fecha_inicio' => '2027-01-01',
            'zona' => 'san_martin',
            'umbral_puntos' => 75,
        ];

        $this->assertTrue(Validator::make($datos, $request->rules(), $request->messages())->passes());

        foreach ([30, 250] as $umbral) {
            $datos['umbral_puntos'] = $umbral;
            $validador = Validator::make($datos, $request->rules(), $request->messages());

            $this->assertTrue($validador->fails());
            $this->assertSame(
                'El umbral debe estar entre 50 y 200 puntos.',
                $validador->errors()->first('umbral_puntos')
            );
        }
    }
}
