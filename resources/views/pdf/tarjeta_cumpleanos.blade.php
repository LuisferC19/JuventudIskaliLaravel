<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 0; }
    body { margin: 0; font-family: "DejaVu Sans", sans-serif; color: #173b37; }
    .card { width: 792px; height: 612px; box-sizing: border-box; background: #f4fcfb; border: 18px solid #0aafa0; text-align: center; position: relative; }
    .logo { width: 72px; height: 72px; margin-top: 28px; }
    .mascot { position: absolute; right: 58px; bottom: 42px; width: 155px; }
    h1 { margin: 28px 40px 12px; font-size: 34px; color: #087f74; }
    .message { width: 520px; margin: 0 auto; font-size: 17px; line-height: 1.6; }
    .age { margin-top: 20px; font-size: 22px; font-weight: bold; }
    .signature { position: absolute; bottom: 58px; left: 55px; width: 260px; border-top: 1px solid #173b37; padding-top: 8px; }
  </style>
</head>
<body>
  <div class="card">
    @if (imagen_base64('/img/Logo.jpeg'))<img class="logo" src="{{ imagen_base64('/img/Logo.jpeg') }}" alt="Logo">@endif
    @if (imagen_base64('/img/Mascota_Iski_2.jpeg'))<img class="mascot" src="{{ imagen_base64('/img/Mascota_Iski_2.jpeg') }}" alt="Iski">@endif
    <h1>¡Feliz cumpleaños, {{ $nombre }}!</h1>
    <div class="message">De parte de {{ config('iskali.institucion.nombre') }}, te deseamos un día lleno de alegría y seguimos celebrando tu valiosa participación.</div>
    @if ($edad !== null)<div class="age">{{ $edad }} años</div>@endif
    <div class="signature"><strong>{{ config('iskali.institucion.firma_nombre') }}</strong><br>{{ config('iskali.institucion.nombre') }}</div>
  </div>
</body>
</html>
