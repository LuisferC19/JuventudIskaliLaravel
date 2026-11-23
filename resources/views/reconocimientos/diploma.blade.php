<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 20px; }
    body { margin: 0; font-family: "DejaVu Sans", sans-serif; color: #173b37; }
    .watermark { position: fixed; width: 430px; height: 430px; left: 50%; top: 50%; margin-left: -215px; margin-top: -215px; opacity: 0.08; z-index: -1; }
    .certificate { width: 100%; height: 750px; box-sizing: border-box; border: 12px solid #0aafa0; outline: 3px solid #087f74; outline-offset: -22px; padding: 38px 70px; text-align: center; position: relative; }
    .logo { float: left; width: 75px; height: 75px; }
    .mascot { position: absolute; right: 72px; bottom: 38px; width: 130px; }
    h1 { margin: 82px 0 8px; font-size: 38px; color: #087f74; }
    .award { font-size: 16px; margin-top: 20px; }
    .receiver { font-size: 32px; font-weight: bold; margin: 12px 0 18px; }
    .description { width: 75%; margin: 0 auto; font-size: 16px; line-height: 1.5; }
    .campaign { margin-top: 12px; font-size: 14px; }
    .folio { margin-top: 18px; font-size: 17px; font-weight: bold; color: #087f74; }
    .signature { position: absolute; bottom: 55px; left: 50%; width: 300px; margin-left: -150px; border-top: 1px solid #173b37; padding-top: 8px; }
  </style>
</head>
<body>
  @if (imagen_base64('/img/Logo.jpeg'))
    <img class="watermark" src="{{ imagen_base64('/img/Logo.jpeg') }}" alt="">
  @endif
  <div class="certificate">
    @if (imagen_base64('/img/Logo.jpeg'))
      <img class="logo" src="{{ imagen_base64('/img/Logo.jpeg') }}" alt="Logo">
    @endif
    @if (imagen_base64('/img/Mascota_Iski_1.jpeg'))
      <img class="mascot" src="{{ imagen_base64('/img/Mascota_Iski_1.jpeg') }}" alt="Iski">
    @endif
    <h1>Diploma de agradecimiento</h1>
    <div class="award">Se otorga a:</div>
    <div class="receiver">{{ $receptor }}</div>
    <div class="description">{{ $reconocimiento->descripcion }}</div>
    @if ($campana)<div class="campaign">Por su participación en: <strong>{{ $campana }}</strong></div>@endif
    <div class="campaign">{{ \Illuminate\Support\Carbon::parse($reconocimiento->fecha_emision)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</div>
    <div class="folio">Folio: {{ $reconocimiento->folio }}</div>
    <div class="signature">
      <strong>{{ config('iskali.institucion.firma_nombre') }}</strong><br>
      {{ config('iskali.institucion.firma_cargo') }}
    </div>
  </div>
</body>
</html>
