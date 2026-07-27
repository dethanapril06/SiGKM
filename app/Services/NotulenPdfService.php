<?php

namespace App\Services;

use App\Models\NotulenRtm;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class NotulenPdfService
{
    public function generateCombinedPdf(NotulenRtm $notulenRtm)
    {
        $notulenRtm->load(['jadwalRtm.semester.tahunAkademik']);

        // 1. Render Hasil RTM (Halaman 2 / Section Main)
        $mainPdfContent = Pdf::loadView('pdf.notulen-hasil', [
            'notulenRtm' => $notulenRtm,
        ])->setPaper('a4', 'portrait')->output();

        // 2. Render Dokumentasi (Halaman Terakhir / Section 4) if images exist
        $dokImages = [];
        foreach ($notulenRtm->dokumentasi_list as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                $fullPath = Storage::disk('public')->path($path);
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $base64 = 'data:image/'.($ext === 'jpg' ? 'jpeg' : $ext).';base64,'.base64_encode(file_get_contents($fullPath));
                $dokImages[] = $base64;
            }
        }

        $dokPdfContent = null;
        if (! empty($dokImages)) {
            $dokPdfContent = Pdf::loadView('pdf.notulen-dokumentasi', [
                'notulenRtm' => $notulenRtm,
                'images' => $dokImages,
            ])->setPaper('a4', 'portrait')->output();
        }

        // Save temp files for Dompdf generated sections
        $tempMainPdf = tempnam(sys_get_temp_dir(), 'notulen_main_').'.pdf';
        file_put_contents($tempMainPdf, $mainPdfContent);

        $tempDokPdf = null;
        if ($dokPdfContent) {
            $tempDokPdf = tempnam(sys_get_temp_dir(), 'notulen_dok_').'.pdf';
            file_put_contents($tempDokPdf, $dokPdfContent);
        }

        $fpdi = new Fpdi();
        $filename = 'Notulen_RTM_'.str($notulenRtm->jadwalRtm?->judul ?? 'Gabungan')->slug().'.pdf';

        try {
            // URUTAN GABUNGAN:
            // 1. HALAMAN PERTAMA: Lampiran Undangan (PDF)
            if ($notulenRtm->file_undangan && Storage::disk('public')->exists($notulenRtm->file_undangan)) {
                $undanganPath = Storage::disk('public')->path($notulenRtm->file_undangan);
                $this->appendPdfFile($fpdi, $undanganPath, 'Lampiran Undangan Rapat');
            }

            // 2. HALAMAN KEDUA: Hasil RTM
            $this->appendPdfFile($fpdi, $tempMainPdf, 'Hasil Rapat RTM');

            // 3. HALAMAN KETIGA: Lembar Absensi (PDF)
            if ($notulenRtm->file_absensi && Storage::disk('public')->exists($notulenRtm->file_absensi)) {
                $absensiPath = Storage::disk('public')->path($notulenRtm->file_absensi);
                $this->appendPdfFile($fpdi, $absensiPath, 'Lembar Absensi Rapat');
            }

            // 4. HALAMAN TERAKHIR: Dokumentasi Rapat
            if ($tempDokPdf && file_exists($tempDokPdf)) {
                $this->appendPdfFile($fpdi, $tempDokPdf, 'Dokumentasi Rapat');
            }

            $output = $fpdi->Output('S');

            @unlink($tempMainPdf);
            if ($tempDokPdf) {
                @unlink($tempDokPdf);
            }

            return response()->streamDownload(function () use ($output) {
                echo $output;
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Throwable $e) {
            @unlink($tempMainPdf);
            if ($tempDokPdf) {
                @unlink($tempDokPdf);
            }

            return response()->streamDownload(function () use ($mainPdfContent) {
                echo $mainPdfContent;
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }
    }

    private function appendPdfFile(Fpdi $fpdi, string $pdfPath, string $sectionTitle): void
    {
        try {
            $pageCount = $fpdi->setSourceFile($pdfPath);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $fpdi->importPage($pageNo);
                $size = $fpdi->getTemplateSize($templateId);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($templateId);
            }
        } catch (\Throwable $e) {
            // Fallback for compressed PDF stream or unparseable PDFs
            $fpdi->AddPage('P', 'A4');
            $fpdi->SetFont('Arial', 'B', 13);
            $fpdi->Cell(0, 15, 'LAMPIRAN: '.strtoupper($sectionTitle), 0, 1, 'C');
            $fpdi->Ln(5);
            $fpdi->SetFont('Arial', '', 10);
            $fpdi->MultiCell(0, 7, "Berkas lampiran PDF (".basename($pdfPath).") terlampir dalam sistem SiGKM.\nAnda dapat mengunduh atau melihat berkas secara terpisah.", 1, 'C');
        }
    }
}
