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

            $footerStartRow = $this->detectFooterStartRow($zip, $xpath, self::FIRST_DATA_ROW);
            $rowCount = max(1, $indikatorMutu->count());

            $footerDateRow = $this->prepareDataArea($dom, $xpath, self::FIRST_DATA_ROW, $footerStartRow, $rowCount, range('A', 'I'));

            $this->setReportHeader($dom, $xpath, $semester, $tahunAkademik, $fakultas, $tanggalLaporan, $footerDateRow);
            $this->fillIndicatorRows($dom, $xpath, $indikatorMutu);
            $this->mergeStandardRows($dom, $xpath, $indikatorMutu);
            $this->setDimension($xpath, 'I', $footerDateRow + 10);

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
            $this->setText($dom, $xpath, 'H'.$row, $evaluasi ? $this->statusText($evaluasi) : '');
            $this->setText($dom, $xpath, 'I'.$row, $evaluasi ? $this->keteranganText($evaluasi) : '');
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

    private function detectFooterStartRow(ZipArchive $zip, DOMXPath $xpath, int $firstDataRow): int
    {
        $sharedStrings = [];
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXml !== false) {
            $sdom = new DOMDocument;
            $sdom->loadXML($stringsXml);
            foreach ($sdom->getElementsByTagName('si') as $index => $si) {
                $tNodes = $si->getElementsByTagName('t');
                $txt = '';
                foreach ($tNodes as $node) {
                    $txt .= $node->nodeValue;
                }
                $sharedStrings[$index] = $txt;
            }
        }

        $rows = $xpath->query('//x:sheetData/x:row');

        foreach ($rows as $row) {
            $r = (int) $row->getAttribute('r');
            if ($r < $firstDataRow) {
                continue;
            }

            $cellTexts = [];
            foreach ($row->getElementsByTagName('c') as $c) {
                $vNode = $c->getElementsByTagName('v')->item(0);
                $t = $c->getAttribute('t');
                if ($vNode) {
                    $vVal = $vNode->nodeValue;
                    if ($t === 's' && isset($sharedStrings[(int) $vVal])) {
                        $cellTexts[] = $sharedStrings[(int) $vVal];
                    } else {
                        $cellTexts[] = $vVal;
                    }
                } else {
                    $isNode = $c->getElementsByTagName('is')->item(0);
                    if ($isNode) {
                        $cellTexts[] = $isNode->textContent;
                    }
                }
            }
            $rowText = mb_strtolower(implode(' ', $cellTexts));

            if (str_contains($rowText, 'kupang') || str_contains($rowText, 'mengetahui')) {
                return $r;
            }
        }

        return 16;
    }

    private function prepareDataArea(DOMDocument $dom, DOMXPath $xpath, int $firstDataRow, int $footerStartRow, int $rowCount, array $columns): int
    {
        $existingRows = [];
        foreach ($xpath->query('//x:sheetData/x:row') as $row) {
            $r = (int) $row->getAttribute('r');
            if ($r >= $firstDataRow && $r < $footerStartRow) {
                $existingRows[] = $row;
            }
        }
        $existingCount = count($existingRows);
        $deltaRows = $rowCount - $existingCount;

        if ($deltaRows !== 0) {
            $footerRows = [];
            foreach ($xpath->query('//x:sheetData/x:row') as $row) {
                if ((int) $row->getAttribute('r') >= $footerStartRow) {
                    $footerRows[] = $row;
                }
            }
            usort($footerRows, fn ($a, $b) => (int) $b->getAttribute('r') <=> (int) $a->getAttribute('r'));
            foreach ($footerRows as $row) {
                $this->renumberRow($row, (int) $row->getAttribute('r') + $deltaRows);
            }

            $this->shiftMergeRows($xpath, $footerStartRow - 1, $deltaRows);
        }

        $protoRow = $xpath->query('//x:sheetData/x:row[@r="'.($firstDataRow - 1).'"]')->item(0)
            ?? $xpath->query('//x:sheetData/x:row')->item(0);

        $sheetData = $xpath->query('//x:sheetData')->item(0);
        $newFooterStartRow = $footerStartRow + $deltaRows;
        $firstFooterRow = $xpath->query('//x:sheetData/x:row[@r="'.$newFooterStartRow.'"]')->item(0);

        for ($i = 0; $i < $rowCount; $i++) {
            $targetRowNumber = $firstDataRow + $i;
            $rowNode = $xpath->query('//x:sheetData/x:row[@r="'.$targetRowNumber.'"]')->item(0);

            if (! $rowNode instanceof DOMElement) {
                $newRow = $protoRow->cloneNode(true);
                $this->renumberRow($newRow, $targetRowNumber);
                foreach ($newRow->getElementsByTagName('c') as $c) {
                    $this->clearCell($c);
                }
                if ($firstFooterRow instanceof DOMElement) {
                    $sheetData->insertBefore($newRow, $firstFooterRow);
                } else {
                    $sheetData->appendChild($newRow);
                }
            } else {
                foreach ($rowNode->getElementsByTagName('c') as $c) {
                    $this->clearCell($c);
                }
            }
        }

        if ($deltaRows < 0) {
            foreach ($existingRows as $index => $row) {
                if ($index >= $rowCount) {
                    $sheetData->removeChild($row);
                }
            }
        }

        return $newFooterStartRow;
    }

    private function shiftMergeRows(DOMXPath $xpath, int $afterRow, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        foreach ($xpath->query('//x:mergeCell') as $mergeCell) {
            if (! $mergeCell instanceof DOMElement) {
                continue;
            }

            $reference = preg_replace_callback('/([A-Z]+)(\d+)/', function (array $matches) use ($afterRow, $delta) {
                $row = (int) $matches[2];

                if ($row > $afterRow) {
                    $row += $delta;
                }

                return $matches[1].$row;
            }, $mergeCell->getAttribute('ref'));

            $mergeCell->setAttribute('ref', $reference);
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

    private function setDimension(DOMXPath $xpath, string $lastColumn, int $maxRow): void
    {
        $dimension = $xpath->query('//x:dimension')->item(0);
        $dimension?->setAttribute('ref', "A1:{$lastColumn}{$maxRow}");
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

    private function statusText(EvaluasiIndikator $evaluasiIndikator): string
    {
        return $this->statusLabel($evaluasiIndikator->status_capaian);
    }

    private function keteranganText(EvaluasiIndikator $evaluasiIndikator): string
    {
        return collect([
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

        if ($cell instanceof DOMElement) {
            return $cell;
        }

        preg_match('/^([A-Z]+)(\d+)$/', $coordinate, $matches);
        $column = $matches[1] ?? '';
        $rowNumber = (int) ($matches[2] ?? 0);

        $row = $xpath->query('//x:sheetData/x:row[@r="'.$rowNumber.'"]')->item(0);

        if (! $row instanceof DOMElement) {
            $sheetData = $xpath->query('//x:sheetData')->item(0);
            if (! $sheetData instanceof DOMElement) {
                throw new RuntimeException("Sel {$coordinate} dan baris {$rowNumber} tidak dapat dibuat.");
            }

            $dom = $sheetData->ownerDocument;
            $row = $dom->createElementNS($sheetData->namespaceURI, 'row');
            $row->setAttribute('r', (string) $rowNumber);

            $inserted = false;
            foreach ($sheetData->childNodes as $childRow) {
                if ($childRow instanceof DOMElement && $childRow->localName === 'row') {
                    $childRowNum = (int) $childRow->getAttribute('r');
                    if ($childRowNum > $rowNumber) {
                        $sheetData->insertBefore($row, $childRow);
                        $inserted = true;
                        break;
                    }
                }
            }

            if (! $inserted) {
                $sheetData->appendChild($row);
            }
        }

        $dom = $row->ownerDocument;
        $cell = $dom->createElementNS($row->namespaceURI, 'c');
        $cell->setAttribute('r', $coordinate);

        $inserted = false;
        foreach ($row->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'c') {
                $childCol = preg_replace('/\d+$/', '', $child->getAttribute('r'));
                if (strcmp($childCol, $column) > 0) {
                    $row->insertBefore($cell, $child);
                    $inserted = true;
                    break;
                }
            }
        }

        if (! $inserted) {
            $row->appendChild($cell);
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
