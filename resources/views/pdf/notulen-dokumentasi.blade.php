<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Dokumentasi Notulen RTM - {{ $notulenRtm->jadwalRtm?->judul }}</title>
    <style>
        @page {
            margin: 2cm 2cm 2cm 2.5cm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000000;
        }
        .header-title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13pt;
            margin-bottom: 25px;
        }
        .image-block {
            text-align: center;
            margin-bottom: 25px;
        }
        .doc-img {
            max-width: 90%;
            max-height: 18cm;
            display: block;
            margin: 0 auto 10px auto;
            border: 1px solid #ccc;
            padding: 3px;
        }
        .caption {
            font-size: 10pt;
            color: #333333;
            font-style: italic;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header-title">
        DOKUMENTASI RAPAT TINJAUAN MANAJEMEN<br>
        {{ $notulenRtm->jadwalRtm?->judul ? strtoupper($notulenRtm->jadwalRtm->judul) : 'RTM' }}
    </div>

    @foreach($images as $index => $src)
        <div class="image-block">
            <img src="{{ $src }}" class="doc-img">
            <div class="caption">Dokumentasi Foto {{ $index + 1 }}</div>
        </div>
        @if(!$loop->last && count($images) > 1 && $index % 2 === 1)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
