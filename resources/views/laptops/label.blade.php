<!DOCTYPE html>
<html lang="id">
    <head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Label {{ $laptop->asset_code }}</title>@vite(['resources/css/app.css'])</head>
    <body class="label-page">
        <main class="asset-label"><div class="label-brand">PT LINKIT <span>INVENTARIS ASET</span></div><img src="{{ route('laptops.qr', $laptop) }}" alt="QR code aset {{ $laptop->asset_code }}"><strong>{{ $laptop->asset_code }}</strong><span>{{ $laptop->brand }} {{ $laptop->model }}</span></main>
        <div class="print-actions"><button class="button button-primary" onclick="window.print()">Cetak label</button><a class="button button-outline" href="{{ route('laptops.index') }}">Kembali</a></div>
    </body>
</html>
