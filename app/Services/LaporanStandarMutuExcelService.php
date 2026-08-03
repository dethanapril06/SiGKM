<?php

namespace App\Services;

use App\Models\EvaluasiIndikator;
use App\Models\IndikatorMutu;
use Carbon\CarbonInterface;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class LaporanStandarMutuExcelService
{
    private const FIRST_DATA_ROW = 13;

    private const LAST_TEMPLATE_DATA_ROW = 328;

    private const TEMPLATE_FOOTER_DATE_ROW = 332;

    public function generate(
        Collection $indikatorMutu,
        ?string $semester,
        ?string $tahunAkademik,
        string $fakultas,
        CarbonInterface $tanggalLaporan,
    ): string {
        $templatePath = resource_path('templates/Template Laporan Pencapaian Standar Mutu FST.xlsx');

        if (! File::exists($templatePath)) {
            throw new RuntimeException('Template laporan pencapaian standar mutu tidak ditemukan.');
        }

        $directory = storage_path('app/private/laporan');
        File::ensureDirectoryExists($directory);

        $outputPath = $directory.'/laporan-evaluasi-standar-mutu-'.uniqid().'.xlsx';
        File::copy($templatePath, $outputPath);

        $zip = new ZipArchive;

        if ($zip->open($outputPath) !== true) {
            throw new RuntimeException('File laporan Excel tidak dapat dibuat.');
        }

        try {
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

            if ($sheetXml === false) {
                throw new RuntimeException('Sheet laporan tidak ditemukan di dalam template.');
            }

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = false;
            $dom->loadXML($sheetXml);

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            $extraRows = max(0, $indikatorMutu->count() - $this->templateDataRowCount());

            if ($extraRows > 0) {
                $this->insertAdditionalRows($xpath, $extraRows);
            }

            $footerDateRow = self::TEMPLATE_FOOTER_DATE_ROW + $extraRows;

            $this->removeTemplateDataMerges($xpath);
            $this->clearTemplateDataRows($dom, $xpath, $indikatorMutu->count());
            $this->setReportHeader($dom, $xpath, $semester, $tahunAkademik, $fakultas, $tanggalLaporan, $footerDateRow);
            $this->fillIndicatorRows($dom, $xpath, $indikatorMutu);
            $this->mergeStandardRows($dom, $xpath, $indikatorMutu);
            $this->extendDimensionAndPrintArea($zip, $xpath, $extraRows);

            $zip->addFromString('xl/worksheets/sheet1.xml', $dom->saveXML());
        } finally {
            $zip->close();
        }

        return $outputPath;
    }

    private function setReportHeader(
        DOMDocument $dom,
        DOMXPath $xpath,
        ?string $semester,
        ?string $tahunAkademik,
        string $fakultas,
        CarbonInterface $tanggalLaporan,
        int $footerDateRow,
    ): void {
        $this->setText($dom, $xpath, 'B8', 'LAPORAN CAPAIAN STANDAR MUTU '.mb_strtoupper($fakultas));
        $this->setText(
            $dom,
            $xpath,
            'B9',
            'SEMESTER '.mb_strtoupper($semester ?: '........').' '.($tahunAkademik ?: '........')
        );
        $this->setText(
            $dom,
            $xpath,
            'G'.$footerDateRow,
            'Kupang, '.$tanggalLaporan->locale('id')->translatedFormat('d F Y')
        );
    }

    private function fillIndicatorRows(DOMDocument $dom, DOMXPath $xpath, Collection $indikatorMutu): void
    {
        $standardNumbers = [];

        foreach ($indikatorMutu->values() as $index => $indikator) {
            $row = self::FIRST_DATA_ROW + $index;
            $standardKey = $this->standardKey($indikator);

            if (! array_key_exists($standardKey, $standardNumbers)) {
                $standardNumbers[$standardKey] = count($standardNumbers) + 1;
            }

            $evaluasi = $indikator->evaluasiIndikators->first();

            $this->setNumber($dom, $xpath, 'A'.$row, $standardNumbers[$standardKey]);
            $this->setText($dom, $xpath, 'B'.$row, $indikator->standarMutu?->nama_standar ?? '-');
            $this->setText($dom, $xpath, 'C'.$row, $indikator->kode_indikator ?: (string) ($index + 1));
            $this->setText($dom, $xpath, 'D'.$row, $indikator->isi_indikator ?: '-');
            $this->setText($dom, $xpath, 'E'.$row, $evaluasi ? $this->temuanText($evaluasi) : '');
            $this->setText($dom, $xpath, 'F'.$row, $evaluasi ? $this->rencanaPerbaikanText($evaluasi) : '');
            $this->setText($dom, $xpath, 'G'.$row, $evaluasi ? $this->targetCapaianText($evaluasi) : '');
            $this->setText($dom, $xpath, 'H'.$row, $evaluasi ? $this->keteranganText($evaluasi) : '');
        }
    }

    private function mergeStandardRows(DOMDocument $dom, DOMXPath $xpath, Collection $indikatorMutu): void
    {
        if ($indikatorMutu->isEmpty()) {
            return;
        }

        $mergeCells = $this->mergeCellsNode($dom, $xpath);

        $startRow = self::FIRST_DATA_ROW;
        $previousKey = null;

        foreach ($indikatorMutu->values() as $index => $indikator) {
            $key = $this->standardKey($indikator);

            if ($previousKey !== null && $key !== $previousKey) {
                $this->mergeStandardRange($dom, $mergeCells, $startRow, self::FIRST_DATA_ROW + $index - 1);
                $startRow = self::FIRST_DATA_ROW + $index;
            }

            $previousKey = $key;
        }

        $this->mergeStandardRange($dom, $mergeCells, $startRow, self::FIRST_DATA_ROW + $indikatorMutu->count() - 1);
        $mergeCells->setAttribute('count', (string) $mergeCells->childNodes->length);
    }

    private function mergeStandardRange(DOMDocument $dom, DOMElement $mergeCells, int $startRow, int $endRow): void
    {
        if ($endRow <= $startRow) {
            return;
        }

        foreach (['A', 'B'] as $column) {
            $mergeCell = $dom->createElementNS($mergeCells->namespaceURI, 'mergeCell');
            $mergeCell->setAttribute('ref', "{$column}{$startRow}:{$column}{$endRow}");
            $mergeCells->appendChild($mergeCell);
        }
    }

    private function clearTemplateDataRows(DOMDocument $dom, DOMXPath $xpath, int $dataRows): void
    {
        $lastRow = self::FIRST_DATA_ROW + max($this->templateDataRowCount(), $dataRows) - 1;

        for ($row = self::FIRST_DATA_ROW; $row <= $lastRow; $row++) {
            foreach (range('A', 'H') as $column) {
                $this->setText($dom, $xpath, $column.$row, '');
            }
        }
    }

    private function removeTemplateDataMerges(DOMXPath $xpath): void
    {
        $mergeCells = $xpath->query('//x:mergeCells')->item(0);

        if (! $mergeCells instanceof DOMElement) {
            return;
        }

        foreach (iterator_to_array($xpath->query('x:mergeCell', $mergeCells)) as $mergeCell) {
            if (! $mergeCell instanceof DOMElement) {
                continue;
            }

            if ($this->mergeTouchesDataArea($mergeCell->getAttribute('ref'))) {
                $mergeCells->removeChild($mergeCell);
            }
        }

        $mergeCells->setAttribute('count', (string) $mergeCells->childNodes->length);
    }

    private function mergeTouchesDataArea(string $reference): bool
    {
        preg_match_all('/([A-Z]+)(\d+)/', $reference, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $column = $match[1];
            $row = (int) $match[2];

            if (in_array($column, ['A', 'B'], true)
                && $row >= self::FIRST_DATA_ROW
                && $row <= self::LAST_TEMPLATE_DATA_ROW) {
                return true;
            }
        }

        return false;
    }

    private function insertAdditionalRows(DOMXPath $xpath, int $extraRows): void
    {
        $rowsToShift = [];

        foreach ($xpath->query('//x:sheetData/x:row') as $row) {
            if ((int) $row->getAttribute('r') > self::LAST_TEMPLATE_DATA_ROW) {
                $rowsToShift[] = $row;
            }
        }

        foreach ($rowsToShift as $row) {
            $this->renumberRow($row, (int) $row->getAttribute('r') + $extraRows);
        }

        $templateRow = $xpath->query('//x:sheetData/x:row[@r="'.self::LAST_TEMPLATE_DATA_ROW.'"]')->item(0);
        $firstShiftedRow = $rowsToShift[0] ?? null;

        if (! $templateRow instanceof DOMElement || ! $firstShiftedRow instanceof DOMElement) {
            throw new RuntimeException('Struktur baris pada template laporan tidak valid.');
        }

        for ($i = 1; $i <= $extraRows; $i++) {
            $newRow = $templateRow->cloneNode(true);
            $this->renumberRow($newRow, self::LAST_TEMPLATE_DATA_ROW + $i);
            $firstShiftedRow->parentNode->insertBefore($newRow, $firstShiftedRow);
        }
    }

    private function renumberRow(DOMElement $row, int $newNumber): void
    {
        $row->setAttribute('r', (string) $newNumber);

        foreach ($row->childNodes as $cell) {
            if ($cell instanceof DOMElement && $cell->localName === 'c') {
                $column = preg_replace('/\d+$/', '', $cell->getAttribute('r'));
                $cell->setAttribute('r', $column.$newNumber);
            }
        }
    }

    private function extendDimensionAndPrintArea(ZipArchive $zip, DOMXPath $xpath, int $extraRows): void
    {
        $dimension = $xpath->query('//x:dimension')->item(0);
        $dimension?->setAttribute('ref', 'A1:J'.(341 + $extraRows));

        if ($extraRows === 0) {
            return;
        }

        $workbook = $zip->getFromName('xl/workbook.xml');

        if ($workbook !== false) {
            $workbook = str_replace('$J$341', '$J$'.(341 + $extraRows), $workbook);
            $zip->addFromString('xl/workbook.xml', $workbook);
        }
    }

    private function temuanText(EvaluasiIndikator $evaluasiIndikator): string
    {
        $temuan = $evaluasiIndikator->temuans
            ->pluck('pernyataan')
            ->filter()
            ->values();

        if ($temuan->isNotEmpty()) {
            return $temuan->join("\n");
        }

        return $evaluasiIndikator->status_capaian === 'tercapai'
            ? 'Tidak ada temuan'
            : ($evaluasiIndikator->catatan ?: '-');
    }

    private function rencanaPerbaikanText(EvaluasiIndikator $evaluasiIndikator): string
    {
        $plans = $evaluasiIndikator->temuans
            ->flatMap(fn ($temuan) => $temuan->rencanaTindakLanjuts)
            ->pluck('uraian_realisasi')
            ->filter()
            ->values();

        if ($plans->isNotEmpty()) {
            return $plans->join("\n");
        }

        $initialPlans = $evaluasiIndikator->temuans
            ->pluck('rencana_awal')
            ->filter()
            ->values();

        return $initialPlans->isNotEmpty() ? $initialPlans->join("\n") : '-';
    }

    private function targetCapaianText(EvaluasiIndikator $evaluasiIndikator): string
    {
        $targets = $evaluasiIndikator->temuans
            ->pluck('target_capaian')
            ->filter()
            ->values();

        return $targets->isNotEmpty() ? $targets->join("\n") : '-';
    }

    private function keteranganText(EvaluasiIndikator $evaluasiIndikator): string
    {
        return collect([
            'Status: '.$this->statusLabel($evaluasiIndikator->status_capaian),
            $evaluasiIndikator->catatan ? 'Catatan: '.$evaluasiIndikator->catatan : null,
            $evaluasiIndikator->bukti_capaian ? 'Bukti capaian tersedia' : null,
        ])->filter()->join("\n");
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'tercapai' => 'Tercapai',
            'dalam_proses' => 'Dalam Proses',
            default => 'Belum Tercapai',
        };
    }

    private function standardKey(IndikatorMutu $indikator): string
    {
        return (string) ($indikator->standar_mutu_id ?? $indikator->standarMutu?->nama_standar ?? 'tanpa-standar');
    }

    private function mergeCellsNode(DOMDocument $dom, DOMXPath $xpath): DOMElement
    {
        $mergeCells = $xpath->query('//x:mergeCells')->item(0);

        if ($mergeCells instanceof DOMElement) {
            return $mergeCells;
        }

        $worksheet = $xpath->query('/x:worksheet')->item(0);

        if (! $worksheet instanceof DOMElement) {
            throw new RuntimeException('Struktur worksheet pada template laporan tidak valid.');
        }

        $mergeCells = $dom->createElementNS($worksheet->namespaceURI, 'mergeCells');
        $worksheet->appendChild($mergeCells);

        return $mergeCells;
    }

    private function setText(DOMDocument $dom, DOMXPath $xpath, string $coordinate, string $value): void
    {
        $cell = $this->cell($xpath, $coordinate);
        $this->clearCell($cell);
        $cell->setAttribute('t', 'inlineStr');

        $inlineString = $dom->createElementNS($cell->namespaceURI, 'is');
        $text = $dom->createElementNS($cell->namespaceURI, 't');
        $text->setAttribute('xml:space', 'preserve');
        $text->appendChild($dom->createTextNode($value));
        $inlineString->appendChild($text);
        $cell->appendChild($inlineString);
    }

    private function setNumber(DOMDocument $dom, DOMXPath $xpath, string $coordinate, int $value): void
    {
        $cell = $this->cell($xpath, $coordinate);
        $this->clearCell($cell);
        $cell->removeAttribute('t');
        $cell->appendChild($dom->createElementNS($cell->namespaceURI, 'v', (string) $value));
    }

    private function cell(DOMXPath $xpath, string $coordinate): DOMElement
    {
        $cell = $xpath->query('//x:c[@r="'.$coordinate.'"]')->item(0);

        if (! $cell instanceof DOMElement) {
            throw new RuntimeException("Sel {$coordinate} tidak ditemukan pada template laporan.");
        }

        return $cell;
    }

    private function clearCell(DOMElement $cell): void
    {
        while ($cell->firstChild) {
            $cell->removeChild($cell->firstChild);
        }
    }

    private function templateDataRowCount(): int
    {
        return self::LAST_TEMPLATE_DATA_ROW - self::FIRST_DATA_ROW + 1;
    }
}
