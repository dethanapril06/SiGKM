<?php

namespace App\Services;

use App\Models\IndikatorKinerjaKegiatanSatuan;
use App\Models\IndikatorMutu;
use App\Models\KeputusanRtm;
use Carbon\CarbonInterface;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class LaporanRtmExcelService
{
    private const FIRST_DATA_ROW = 14;

    public function generateFakultas(
        Collection $keputusanRtm,
        ?string $semester,
        ?string $tahunAkademik,
        string $fakultas,
        CarbonInterface $tanggalLaporan,
    ): string {
        return $this->generate($keputusanRtm, [
            'template' => 'templates/Template Laporan RTM FST.xlsx',
            'sheet' => 1,
            'sheet_name' => 'RTM Fakultas',
            'columns' => range('A', 'N'),
            'footer_date_cell' => 'J',
            'title_cell' => 'B8',
            'semester_cell' => 'B9',
            'title' => 'LAPORAN TINJAUAN MANAJEMEN - STANDAR MUTU '.mb_strtoupper($fakultas),
        ], $semester, $tahunAkademik, $tanggalLaporan, 'fakultas');
    }

    public function generateProdi(
        Collection $keputusanRtm,
        ?string $semester,
        ?string $tahunAkademik,
        string $programStudi,
        CarbonInterface $tanggalLaporan,
    ): string {
        return $this->generate($keputusanRtm, [
            'template' => 'templates/Template Laporan RTM Program Studi.xlsx',
            'sheet' => 1,
            'sheet_name' => 'RTM Prodi',
            'columns' => range('A', 'P'),
            'footer_date_cell' => 'L',
            'title_cell' => 'B8',
            'semester_cell' => 'B9',
            'title' => 'LAPORAN TINJAUAN MANAJEMEN - KINERJA PROGRAM STUDI '.mb_strtoupper($programStudi),
        ], $semester, $tahunAkademik, $tanggalLaporan, 'prodi');
    }

    private function generate(
        Collection $keputusanRtm,
        array $config,
        ?string $semester,
        ?string $tahunAkademik,
        CarbonInterface $tanggalLaporan,
        string $jenis,
    ): string {
        $templatePath = resource_path($config['template']);

        if (! File::exists($templatePath)) {
            throw new RuntimeException('Template laporan RTM tidak ditemukan.');
        }

        $directory = storage_path('app/private/laporan');
        File::ensureDirectoryExists($directory);

        $outputPath = $directory.'/laporan-rtm-'.$jenis.'-'.uniqid().'.xlsx';
        File::copy($templatePath, $outputPath);

        $zip = new ZipArchive;

        if ($zip->open($outputPath) !== true) {
            throw new RuntimeException('File laporan RTM Excel tidak dapat dibuat.');
        }

        try {
            $sheetPath = 'xl/worksheets/sheet'.$config['sheet'].'.xml';
            $sheetXml = $zip->getFromName($sheetPath);

            if ($sheetXml === false) {
                throw new RuntimeException('Sheet laporan RTM tidak ditemukan di dalam template.');
            }

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = false;
            $dom->loadXML($sheetXml);

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            $footerStartRow = $this->detectFooterStartRow($zip, $xpath, self::FIRST_DATA_ROW);
            $rowCount = max(1, $keputusanRtm->count());

            $footerDateRow = $this->prepareDataArea($dom, $xpath, self::FIRST_DATA_ROW, $footerStartRow, $rowCount, $config['columns']);

            $this->setText($dom, $xpath, $config['title_cell'], $config['title']);
            $this->setText($dom, $xpath, $config['semester_cell'], 'SEMESTER '.mb_strtoupper($semester ?: '........').' '.($tahunAkademik ?: '........'));
            $this->setText($dom, $xpath, $config['footer_date_cell'].$footerDateRow, 'Kupang, '.$tanggalLaporan->locale('id')->translatedFormat('d F Y'));

            $jenis === 'fakultas'
                ? $this->fillFakultasRows($dom, $xpath, $keputusanRtm)
                : $this->fillProdiRows($dom, $xpath, $keputusanRtm);

            $this->mergeGroupRows($dom, $xpath, $keputusanRtm, $jenis);
            $this->setDimension($xpath, end($config['columns']), $footerDateRow + 10);

            $zip->addFromString($sheetPath, $dom->saveXML());
            $this->keepOnlySheet($zip, $config['sheet'], $config['sheet_name']);
        } finally {
            $zip->close();
        }

        return $outputPath;
    }

    private function fillFakultasRows(DOMDocument $dom, DOMXPath $xpath, Collection $keputusanRtm): void
    {
        $standardNumbers = [];

        foreach ($keputusanRtm->values() as $index => $item) {
            $row = self::FIRST_DATA_ROW + $index;
            $standardKey = (string) ($item->standar_id ?? $item->standar_nama ?? 'tanpa-standar');

            if (! array_key_exists($standardKey, $standardNumbers)) {
                $standardNumbers[$standardKey] = count($standardNumbers) + 1;
            }

            $this->setNumber($dom, $xpath, 'A'.$row, $standardNumbers[$standardKey]);
            $this->setText($dom, $xpath, 'B'.$row, $item->standar_nama ?? '-');
            $this->setText($dom, $xpath, 'C'.$row, $item->indikator_kode ?: (string) ($index + 1));
            $this->setText($dom, $xpath, 'D'.$row, $item->indikator_isi ?: '-');
            $this->setText($dom, $xpath, 'E'.$row, $item->temuan ?: '-');
            $this->setText($dom, $xpath, 'F'.$row, $item->risiko ?: '-');
            $this->setText($dom, $xpath, 'G'.$row, $item->dampak ?: '-');
            $this->setText($dom, $xpath, 'H'.$row, $item->peringkat ?: '-');
            $this->setText($dom, $xpath, 'I'.$row, $item->keputusan_rtm ?: '-');
            $this->setText($dom, $xpath, 'J'.$row, $item->tindak_lanjut ?: '-');
            $this->setText($dom, $xpath, 'K'.$row, $item->strategi ?: '-');
            $this->setText($dom, $xpath, 'L'.$row, $item->penanggung_jawab ?: '-');
            $this->setText($dom, $xpath, 'M'.$row, $item->target_selesai ?: '-');
            $this->setText($dom, $xpath, 'N'.$row, $this->statusLabel($item->status));
        }
    }

    private function fillProdiRows(DOMDocument $dom, DOMXPath $xpath, Collection $keputusanRtm): void
    {
        $sasaranNumbers = [];

        foreach ($keputusanRtm->values() as $index => $item) {
            $row = self::FIRST_DATA_ROW + $index;
            $sasaranKey = (string) ($item->sasaran_id ?? $item->sasaran_kode ?? 'tanpa-sasaran');

            if (! array_key_exists($sasaranKey, $sasaranNumbers)) {
                $sasaranNumbers[$sasaranKey] = count($sasaranNumbers) + 1;
            }

            $this->setNumber($dom, $xpath, 'A'.$row, $sasaranNumbers[$sasaranKey]);
            $this->setText($dom, $xpath, 'B'.$row, $item->sasaran_text ?? '-');
            $this->setText($dom, $xpath, 'C'.$row, $item->iku_kode ?? '-');
            $this->setText($dom, $xpath, 'D'.$row, $item->iku_uraian ?? '-');
            $this->setText($dom, $xpath, 'E'.$row, $item->ikk_text ?? '-');
            $this->setText($dom, $xpath, 'F'.$row, $item->ikks_text ?? '-');
            $this->setText($dom, $xpath, 'G'.$row, $item->temuan ?: '-');
            $this->setText($dom, $xpath, 'H'.$row, $item->risiko ?: '-');
            $this->setText($dom, $xpath, 'I'.$row, $item->dampak ?: '-');
            $this->setText($dom, $xpath, 'J'.$row, $item->peringkat ?: '-');
            $this->setText($dom, $xpath, 'K'.$row, $item->keputusan_rtm ?: '-');
            $this->setText($dom, $xpath, 'L'.$row, $item->tindak_lanjut ?: '-');
            $this->setText($dom, $xpath, 'M'.$row, $item->strategi ?: '-');
            $this->setText($dom, $xpath, 'N'.$row, $item->penanggung_jawab ?: '-');
            $this->setText($dom, $xpath, 'O'.$row, $item->target_selesai ?: '-');
            $this->setText($dom, $xpath, 'P'.$row, $this->statusLabel($item->status));
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

        return 17;
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

    private function mergeGroupRows(DOMDocument $dom, DOMXPath $xpath, Collection $keputusanRtm, string $jenis): void
    {
        if ($keputusanRtm->isEmpty()) {
            return;
        }

        $mergeCells = $this->mergeCellsNode($dom, $xpath);
        $startRow = self::FIRST_DATA_ROW;
        $previousKey = null;

        foreach ($keputusanRtm->values() as $index => $item) {
            $key = $jenis === 'fakultas' ? $this->facultyGroupKey($item) : $this->prodiGroupKey($item);

            if ($previousKey !== null && $key !== $previousKey) {
                $this->mergeGroupRange($dom, $mergeCells, $startRow, self::FIRST_DATA_ROW + $index - 1);
                $startRow = self::FIRST_DATA_ROW + $index;
            }

            $previousKey = $key;
        }

        $this->mergeGroupRange($dom, $mergeCells, $startRow, self::FIRST_DATA_ROW + $keputusanRtm->count() - 1);
        $mergeCells->setAttribute('count', (string) $mergeCells->childNodes->length);
    }

    private function mergeGroupRange(DOMDocument $dom, DOMElement $mergeCells, int $startRow, int $endRow): void
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

    private function removeTemplateDataMerges(DOMXPath $xpath, int $lastTemplateRow): void
    {
        $mergeCells = $xpath->query('//x:mergeCells')->item(0);

        if (! $mergeCells instanceof DOMElement) {
            return;
        }

        foreach (iterator_to_array($xpath->query('x:mergeCell', $mergeCells)) as $mergeCell) {
            if ($mergeCell instanceof DOMElement && $this->mergeTouchesDataArea($mergeCell->getAttribute('ref'), $lastTemplateRow)) {
                $mergeCells->removeChild($mergeCell);
            }
        }

        $mergeCells->setAttribute('count', (string) $mergeCells->childNodes->length);
    }

    private function mergeTouchesDataArea(string $reference, int $lastTemplateRow): bool
    {
        preg_match_all('/([A-Z]+)(\d+)/', $reference, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (in_array($match[1], ['A', 'B'], true)
                && (int) $match[2] >= self::FIRST_DATA_ROW
                && (int) $match[2] <= $lastTemplateRow) {
                return true;
            }
        }

        return false;
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

                return $matches[1].($row > $afterRow ? $row + $delta : $row);
            }, $mergeCell->getAttribute('ref'));

            $mergeCell->setAttribute('ref', $reference);
        }
    }

    private function keepOnlySheet(ZipArchive $zip, int $sheetNumber, string $sheetName): void
    {
        $sheetPath = 'xl/worksheets/sheet'.$sheetNumber.'.xml';
        $workbook = $this->xmlFromZip($zip, 'xl/workbook.xml');
        $workbookXpath = new DOMXPath($workbook);
        $workbookXpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbookXpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $rels = $this->xmlFromZip($zip, 'xl/_rels/workbook.xml.rels');
        $relsXpath = new DOMXPath($rels);
        $relsXpath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');

        foreach (iterator_to_array($workbookXpath->query('//x:sheets/x:sheet')) as $sheet) {
            if (! $sheet instanceof DOMElement) {
                continue;
            }

            $relationshipId = $sheet->getAttribute('r:id');
            $relationship = $relsXpath->query('//r:Relationship[@Id="'.$relationshipId.'"]')->item(0);
            $targetPath = $relationship instanceof DOMElement ? 'xl/'.ltrim($relationship->getAttribute('Target'), '/') : '';

            if ($targetPath === $sheetPath) {
                $sheet->setAttribute('name', $sheetName);
                $sheet->setAttribute('sheetId', '1');
            } else {
                $sheet->parentNode?->removeChild($sheet);
            }
        }

        foreach (iterator_to_array($relsXpath->query('//r:Relationship')) as $relationship) {
            if (! $relationship instanceof DOMElement) {
                continue;
            }

            if (str_ends_with($relationship->getAttribute('Type'), '/worksheet')
                && 'xl/'.ltrim($relationship->getAttribute('Target'), '/') !== $sheetPath) {
                $relationship->parentNode?->removeChild($relationship);
            }
        }

        $contentTypes = $this->xmlFromZip($zip, '[Content_Types].xml');
        foreach (iterator_to_array($contentTypes->getElementsByTagName('Override')) as $override) {
            if ($override instanceof DOMElement
                && str_starts_with($override->getAttribute('PartName'), '/xl/worksheets/')
                && $override->getAttribute('PartName') !== '/'.$sheetPath) {
                $override->parentNode?->removeChild($override);
            }
        }

        $zip->addFromString('xl/workbook.xml', $workbook->saveXML());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels->saveXML());
        $zip->addFromString('[Content_Types].xml', $contentTypes->saveXML());
    }

    private function riskColumns(Collection $risikoTemuans): array
    {
        return [
            $risikoTemuans->pluck('deskripsi_risiko')->filter()->join("\n") ?: '-',
            $risikoTemuans->pluck('dampak_risiko')->filter()->join("\n") ?: '-',
            $risikoTemuans->pluck('tingkatRisiko.nama_tingkat')->filter()->join("\n") ?: '-',
        ];
    }

    private function facultyGroupKey(object $item): string
    {
        return (string) ($item->standar_id ?? $item->standar_nama ?? 'tanpa-standar');
    }

    private function prodiGroupKey(object $item): string
    {
        return (string) ($item->sasaran_id ?? $item->sasaran_kode ?? 'tanpa-sasaran');
    }

    private function statusLabel(?string $status): string
    {
        if (! $status || $status === '-') {
            return '-';
        }

        return match ($status) {
            'selesai', 'ditutup' => 'Selesai',
            'proses', 'dalam_proses' => 'Proses',
            'belum_dikerjakan', 'belum_tercapai', 'terbuka' => 'Belum Dikerjakan',
            default => str($status)->replace('_', ' ')->title()->toString(),
        };
    }

    private function codeAndText(?string $code, ?string $text): string
    {
        return collect([$code, $text])->filter()->join(' - ') ?: '-';
    }

    private function setDimension(DOMXPath $xpath, string $lastColumn, int $lastRow): void
    {
        $dimension = $xpath->query('//x:dimension')->item(0);
        $dimension?->setAttribute('ref', 'A1:'.$lastColumn.$lastRow);
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

    private function xmlFromZip(ZipArchive $zip, string $path): DOMDocument
    {
        $xml = $zip->getFromName($path);

        if ($xml === false) {
            throw new RuntimeException("Berkas {$path} tidak ditemukan pada template RTM.");
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xml);

        return $dom;
    }

    private function mergeCellsNode(DOMDocument $dom, DOMXPath $xpath): DOMElement
    {
        $mergeCells = $xpath->query('//x:mergeCells')->item(0);

        if ($mergeCells instanceof DOMElement) {
            return $mergeCells;
        }

        $worksheet = $xpath->query('/x:worksheet')->item(0);

        if (! $worksheet instanceof DOMElement) {
            throw new RuntimeException('Struktur worksheet pada template RTM tidak valid.');
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
}
