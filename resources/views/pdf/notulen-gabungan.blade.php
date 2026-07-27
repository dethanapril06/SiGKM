<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Notulen RTM - {{ $notulenRtm->jadwalRtm?->judul }}</title>
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
        .text-center {
            text-align: center;
        }
        .fw-bold {
            font-weight: bold;
        }
        .text-uppercase {
            text-transform: uppercase;
        }
        .header-title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13pt;
            line-height: 1.3;
            margin-bottom: 25px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .meta-table td {
            vertical-align: top;
            padding: 3px 0;
        }
        .meta-label {
            width: 110px;
        }
        .meta-sep {
            width: 15px;
            text-align: center;
        }
        .meta-value {
            text-align: left;
        }
        .section-title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12pt;
            margin: 25px 0 15px 0;
        }
        .content {
            text-align: justify;
        }
        .content p {
            margin: 0 0 10px 0;
        }
        .content ul, .content ol {
            margin: 5px 0 10px 0;
            padding-left: 25px;
        }
        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .content table, .content th, .content td {
            border: 1px solid #000;
            padding: 5px 8px;
        }
        .page-break {
            page-break-after: always;
        }
        .attachment-title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .attachment-img {
            max-width: 100%;
            max-height: 23cm;
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>

    @php
        $jadwal = $notulenRtm->jadwalRtm;
        $tanggalFormatted = $jadwal?->tanggal
            ? \Carbon\Carbon::parse($jadwal->tanggal)->locale('id')->isoFormat('dddd, D MMMM YYYY')
            : '-';
        $waktuFormatted = trim(($jadwal?->waktu_mulai ?? '') . ($jadwal?->waktu_selesai ? ' - ' . $jadwal->waktu_selesai : ''));
        if (blank($waktuFormatted)) {
            $waktuFormatted = '-';
        }
    @endphp

    <div class="header-title">
        NOTULEN RAPAT TINJAUAN MANAJEMEN DAN EVALUASI<br>
        {{ $jadwal?->judul ? strtoupper($jadwal->judul) : 'PERKULUAHAN' }}<br>
        PROGRAM STUDI ILMU KOMPUTER<br>
        UNIVERSITAS NUSA CENDANA
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Hari/tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $tanggalFormatted }}</td>
        </tr>
        <tr>
            <td class="meta-label">Waktu</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $waktuFormatted }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tempat</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $jadwal?->lokasi ?: '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Agenda</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{!! nl2br(e($jadwal?->agenda ?: '-')) !!}</td>
        </tr>
    </table>

    <div class="section-title">HASIL</div>

    <div class="content">
        {!! $notulenRtm->isi_notulen !!}
    </div>

    @foreach($imageAttachments as $attachment)
        <div class="page-break"></div>
        <div class="attachment-title">LAMPIRAN: {{ strtoupper($attachment['title']) }}</div>
        <div class="text-center">
            <img src="{{ $attachment['src'] }}" class="attachment-img">
        </div>
    @endforeach

</body>
</html>
