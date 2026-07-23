<?php

namespace App\Services;

use App\Models\IndikatorKinerjaKegiatanSatuan;
use App\Models\IndikatorMutu;
use App\Models\RencanaTindakLanjut;
use Carbon\CarbonInterface;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class LaporanRtlExcelService
{
    private const TEMPLATE = 'templates/Template Laporan Rencana Tindak Lanjut FST.xlsx';

    private const SHEET_FAKULTAS = 1;

    private const SHEET_PRODI = 2;

    private const FIRST_DATA_ROW = 14;

    public function generateFakultas(
        Collection $rtl,
        ?string $semester,
        ?string $tahunAkademik,
        string $fakultas,
        CarbonInterface $tanggalLaporan,
    ): string {
        $config = [
            'sheet' => self::SHEET_FAKULTAS,
            'sheet_name' => 'RTL Fakultas',
            'columns' => range('A', 'H'),
            'last_template_row' => 329,
            'footer_date_row' => 333,
            'dimension_bottom' => 342,
            'footer_date_cell' => 'F',
            'title_cell' => 'B8',
            'semester_cell' => 'B9',
            'title' => 'LAPORAN RENCANA TINDAK LANJUT - STANDAR MUTU '.mb_strtoupper($fakultas),
        ];

        return $this->generate($rtl, $config, $semester, $tahunAkademik, $tanggalLaporan, 'fakultas');
    }

    public function generateProdi(
        Collection $rtl,
        ?string $semester,
        ?string $tahunAkademik,
        string $programStudi,
        CarbonInterface $tanggalLaporan,
    ): string {
        $config = [
            'sheet' => self::SHEET_PRODI,
            'sheet_name' => 'RTL Prodi',
            'columns' => range('A', 'J'),
            'last_template_row' => 28,
            'footer_date_row' => 32,
            'dimension_bottom' => 41,
            'footer_date_cell' => 'H',
            'title_cell' => 'B8',
            'semester_cell' => 'B9',
            'title' => 'LAPORAN RENCANA TINDAK LANJUT - KINERJA PROGRAM STUDI '.mb_strtoupper($programStudi),
        ];

        return $this->generate($rtl, $config, $semester, $tahunAkademik, $tanggalLaporan, 'prodi');
    }

    private function generate(
        Collection $rtl,
        array $config,
        ?string $semester,
        ?string $tahunAkademik,
        CarbonInterface $tanggalLaporan,
        string $jenis,
    ): string {
        $templatePath = resource_path(self::TEMPLATE);

        if (! File::exists($templatePath)) {
            throw new RuntimeException('Template laporan RTL tidak ditemukan.');
        }

        $directory = storage_path('app/private/laporan');
        File::ensureDirectoryExists($directory);

        $outputPath = $directory.'/laporan-rtl-'.$jenis.'-'.uniqid().'.xlsx';
        File::copy($templatePath, $outputPath);

        $zip = new ZipArchive;

        if ($zip->open($outputPath) !== true) {
            throw new RuntimeException('File laporan RTL Excel tidak dapat dibuat.');
        }

        try {
            $sheetPath = 'xl/worksheets/sheet'.$config['sheet'].'.xml';
            $sheetXml = $zip->getFromName($sheetPath);

            if ($sheetXml === false) {
                throw new RuntimeException('Sheet laporan RTL tidak ditemukan di dalam template.');
            }

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = false;
            $dom->loadXML($sheetXml);

            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            $rowCount = max(1, $rtl->count());
            $deltaRows = $this->resizeDataArea($xpath, $config['last_template_row'], $rowCount);
            $footerDateRow = $config['footer_date_row'] + $deltaRows;

            $this->removeTemplateDataMerges($xpath, $config['last_template_row']);
            $this->clearDataRows($dom, $xpath, $config['columns'], $rowCount);
            $this->setText($dom, $xpath, $config['title_cell'], $config['title']);
            $this->setText(
                $dom,
                $xpath,
                $config['semester_cell'],
                'SEMESTER '.mb_strtoupper($semester ?: '........').' '.($tahunAkademik ?: '........')
            );
            $this->setText(
                $dom,
                $xpath,
                $config['footer_date_cell'].$footerDateRow,
                'Kupang, '.$tanggalLaporan->locale('id')->translatedFormat('d F Y')
            );

            if ($jenis === 'fakultas') {
                $this->fillFakultasRows($dom, $xpath, $rtl);
            } else {
                $this->fillProdiRows($dom, $xpath, $rtl);
            }

            $this->mergeGroupRows($dom, $xpath, $rtl, $jenis);
            $this->setDimension($xpath, end($config['columns']), $config['dimension_bottom'] + $deltaRows);

            $zip->addFromString($sheetPath, $dom->saveXML());
            $this->keepOnlySheet($zip, $config['sheet'], $config['sheet_name']);
        } finally {
            $zip->close();
        }

        return $outputPath;
    }

    private function fillFakultasRows(DOMDocument $dom, DOMXPath $xpath, Collection $rtl): void
    {
        $standardNumbers = [];

        foreach ($rtl->values() as $index => $item) {
            $row = self::FIRST_DATA_ROW + $index;
            $evaluatable = $item->temuan?->evaluasiIndikator?->evaluatable;

            if (! $evaluatable instanceof IndikatorMutu) {
                continue;
            }

            $standardKey = (string) ($evaluatable->standar_mutu_id ?? $evaluatable->standarMutu?->nama_standar ?? 'tanpa-standar');

            if (! array_key_exists($standardKey, $standardNumbers)) {
                $standardNumbers[$standardKey] = count($standardNumbers) + 1;
            }

            $this->setNumber($dom, $xpath, 'A'.$row, $standardNumbers[$standardKey]);
            $this->setText($dom, $xpath, 'B'.$row, $evaluatable->standarMutu?->nama_standar ?? '-');
            $this->setText($dom, $xpath, 'C'.$row, $evaluatable->kode_indikator ?: (string) ($index + 1));
            $this->setText($dom, $xpath, 'D'.$row, $evaluatable->isi_indikator ?: '-');
            $this->setText($dom, $xpath, 'E'.$row, $item->temuan?->pernyataan ?: '-');
            $this->setText($dom, $xpath, 'F'.$row, $this->tindakLanjutText($item));
            $this->setText($dom, $xpath, 'G'.$row, $item->temuan?->nama_penanggung_jawab ?: '-');
            $this->setText($dom, $xpath, 'H'.$row, $this->targetText($item));
        }
    }

    private function fillProdiRows(DOMDocument $dom, DOMXPath $xpath, Collection $rtl): void
    {
        $sasaranNumbers = [];

        foreach ($rtl->values() as $index => $item) {
            $row = self::FIRST_DATA_ROW + $index;
            $ikks = $item->temuan?->evaluasiIndikator?->evaluatable;

            if (! $ikks instanceof IndikatorKinerjaKegiatanSatuan) {
                continue;
            }

            $ikk = $ikks->indikatorKinerjaKegiatan;
            $iku = $ikk?->indikatorKinerjaUtama;
            $sasaran = $iku?->sasaranStrategis;
            $sasaranKey = (string) ($sasaran?->id ?? $sasaran?->uraian_sasaran ?? 'tanpa-sasaran');

            if (! array_key_exists($sasaranKey, $sasaranNumbers)) {
                $sasaranNumbers[$sasaranKey] = count($sasaranNumbers) + 1;
            }

            $this->setNumber($dom, $xpath, 'A'.$row, $sasaranNumbers[$sasaranKey]);
            $this->setText($dom, $xpath, 'B'.$row, $this->codeAndText($sasaran?->kode_sasaran, $sasaran?->uraian_sasaran));
            $this->setText($dom, $xpath, 'C'.$row, $iku?->kode_iku ?: '-');
            $this->setText($dom, $xpath, 'D'.$row, $iku?->uraian_iku ?: '-');
            $this->setText($dom, $xpath, 'E'.$row, $this->codeAndText($ikk?->kode_ikk, $ikk?->uraian_ikk));
            $this->setText($dom, $xpath, 'F'.$row, $this->codeAndText($ikks->kode_ikks, $ikks->uraian_ikks));
            $this->setText($dom, $xpath, 'G'.$row, $item->temuan?->pernyataan ?: '-');
            $this->setText($dom, $xpath, 'H'.$row, $this->tindakLanjutText($item));
            $this->setText($dom, $xpath, 'I'.$row, $item->temuan?->nama_penanggung_jawab ?: '-');
            $this->setText($dom, $xpath, 'J'.$row, $this->targetText($item));
        }
    }

    private function resizeDataArea(DOMXPath $xpath, int $lastTemplateRow, int $requiredRows): int
    {
        $templateRows = $lastTemplateRow - self::FIRST_DATA_ROW + 1;
        $deltaRows = $requiredRows - $templateRows;

        if ($deltaRows > 0) {
            $this->insertRows($xpath, $lastTemplateRow, $deltaRows);
            $this->shiftMergeRows($xpath, $lastTemplateRow, $deltaRows);

            return $deltaRows;
        }

        if ($deltaRows < 0) {
            $this->deleteRows($xpath, self::FIRST_DATA_ROW + $requiredRows, $lastTemplateRow);
            $this->shiftMergeRows($xpath, $lastTemplateRow, $deltaRows);

            return $deltaRows;
        }

        return 0;
    }

    private function insertRows(DOMXPath $xpath, int $lastTemplateRow, int $count): void
    {
        $rowsToShift = [];

        foreach ($xpath->query('//x:sheetData/x:row') as $row) {
            if ((int) $row->getAttribute('r') > $lastTemplateRow) {
                $rowsToShift[] = $row;
            }
        }

        foreach ($rowsToShift as $row) {
            $this->renumberRow($row, (int) $row->getAttribute('r') + $count);
        }

        $templateRow = $xpath->query('//x:sheetData/x:row[@r="'.$lastTemplateRow.'"]')->item(0);
        $firstShiftedRow = $rowsToShift[0] ?? null;

        if (! $templateRow instanceof DOMElement || ! $firstShiftedRow instanceof DOMElement) {
            throw new RuntimeException('Struktur baris pada template RTL tidak valid.');
        }

        for ($i = 1; $i <= $count; $i++) {
            $newRow = $templateRow->cloneNode(true);
            $this->renumberRow($newRow, $lastTemplateRow + $i);
            $firstShiftedRow->parentNode->insertBefore($newRow, $firstShiftedRow);
        }
    }

    private function deleteRows(DOMXPath $xpath, int $fromRow, int $toRow): void
    {
        if ($fromRow > $toRow) {
            return;
        }

        $count = $toRow - $fromRow + 1;
        $sheetData = $xpath->query('//x:sheetData')->item(0);

        foreach (iterator_to_array($xpath->query('//x:sheetData/x:row')) as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }

            $rowNumber = (int) $row->getAttribute('r');

            if ($rowNumber >= $fromRow && $rowNumber <= $toRow) {
                $sheetData?->removeChild($row);
            } elseif ($rowNumber > $toRow) {
                $this->renumberRow($row, $rowNumber - $count);
            }
        }
    }

    private function clearDataRows(DOMDocument $dom, DOMXPath $xpath, array $columns, int $rowCount): void
    {
        for ($row = self::FIRST_DATA_ROW; $row < self::FIRST_DATA_ROW + $rowCount; $row++) {
            foreach ($columns as $column) {
                $this->setText($dom, $xpath, $column.$row, '');
            }
        }
    }

    private function mergeGroupRows(DOMDocument $dom, DOMXPath $xpath, Collection $rtl, string $jenis): void
    {
        if ($rtl->isEmpty()) {
            return;
        }

        $mergeCells = $this->mergeCellsNode($dom, $xpath);
        $startRow = self::FIRST_DATA_ROW;
        $previousKey = null;

        foreach ($rtl->values() as $index => $item) {
            $key = $jenis === 'fakultas'
                ? $this->facultyGroupKey($item)
                : $this->prodiGroupKey($item);

            if ($previousKey !== null && $key !== $previousKey) {
                $this->mergeGroupRange($dom, $mergeCells, $startRow, self::FIRST_DATA_ROW + $index - 1);
                $startRow = self::FIRST_DATA_ROW + $index;
            }

            $previousKey = $key;
        }

        $this->mergeGroupRange($dom, $mergeCells, $startRow, self::FIRST_DATA_ROW + $rtl->count() - 1);
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
            if (! $mergeCell instanceof DOMElement) {
                continue;
            }

            if ($this->mergeTouchesDataArea($mergeCell->getAttribute('ref'), $lastTemplateRow)) {
                $mergeCells->removeChild($mergeCell);
            }
        }

        $mergeCells->setAttribute('count', (string) $mergeCells->childNodes->length);
    }

    private function mergeTouchesDataArea(string $reference, int $lastTemplateRow): bool
    {
        preg_match_all('/([A-Z]+)(\d+)/', $reference, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $column = $match[1];
            $row = (int) $match[2];

            if (in_array($column, ['A', 'B'], true)
                && $row >= self::FIRST_DATA_ROW
                && $row <= $lastTemplateRow) {
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

        $selectedRelationshipIds = [];

        foreach (iterator_to_array($workbookXpath->query('//x:sheets/x:sheet')) as $sheet) {
            if (! $sheet instanceof DOMElement) {
                continue;
            }

            $relationshipId = $sheet->getAttribute('r:id');
            $relationship = $relsXpath->query('//r:Relationship[@Id="'.$relationshipId.'"]')->item(0);
            $target = $relationship instanceof DOMElement ? $relationship->getAttribute('Target') : '';
            $targetPath = 'xl/'.ltrim($target, '/');

            if ($targetPath === $sheetPath) {
                $sheet->setAttribute('name', $sheetName);
                $sheet->setAttribute('sheetId', '1');
                $selectedRelationshipIds[] = $relationshipId;
            } else {
                $sheet->parentNode?->removeChild($sheet);
            }
        }

        foreach (iterator_to_array($relsXpath->query('//r:Relationship')) as $relationship) {
            if (! $relationship instanceof DOMElement) {
                continue;
            }

            $type = $relationship->getAttribute('Type');
            $targetPath = 'xl/'.ltrim($relationship->getAttribute('Target'), '/');

            if (str_ends_with($type, '/worksheet') && $targetPath !== $sheetPath) {
                $relationship->parentNode?->removeChild($relationship);
            }
        }

        $contentTypes = $this->xmlFromZip($zip, '[Content_Types].xml');
        foreach (iterator_to_array($contentTypes->getElementsByTagName('Override')) as $override) {
            if (! $override instanceof DOMElement) {
                continue;
            }

            $partName = $override->getAttribute('PartName');

            if (str_starts_with($partName, '/xl/worksheets/') && $partName !== '/'.$sheetPath) {
                $override->parentNode?->removeChild($override);
            }
        }

        $zip->addFromString('xl/workbook.xml', $workbook->saveXML());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels->saveXML());
        $zip->addFromString('[Content_Types].xml', $contentTypes->saveXML());
    }

    private function xmlFromZip(ZipArchive $zip, string $path): DOMDocument
    {
        $xml = $zip->getFromName($path);

        if ($xml === false) {
            throw new RuntimeException("Berkas {$path} tidak ditemukan pada template RTL.");
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xml);

        return $dom;
    }

    private function setDimension(DOMXPath $xpath, string $lastColumn, int $lastRow): void
    {
        $dimension = $xpath->query('//x:dimension')->item(0);
        $dimension?->setAttribute('ref', 'A1:'.$lastColumn.$lastRow);
    }

    private function tindakLanjutText(RencanaTindakLanjut $rtl): string
    {
        return collect([
            $rtl->temuan?->rencana_awal ? 'Rencana awal: '.$rtl->temuan->rencana_awal : null,
            $rtl->uraian_realisasi ? 'Realisasi: '.$rtl->uraian_realisasi : null,
            $rtl->catatan ? 'Catatan: '.$rtl->catatan : null,
        ])->filter()->join("\n") ?: '-';
    }

    private function targetText(RencanaTindakLanjut $rtl): string
    {
        return $rtl->temuan?->target_selesai
            ? $rtl->temuan->target_selesai->locale('id')->translatedFormat('d F Y')
            : '-';
    }

    private function codeAndText(?string $code, ?string $text): string
    {
        return collect([$code, $text])->filter()->join(' - ') ?: '-';
    }

    private function facultyGroupKey(RencanaTindakLanjut $rtl): string
    {
        $evaluatable = $rtl->temuan?->evaluasiIndikator?->evaluatable;

        return $evaluatable instanceof IndikatorMutu
            ? (string) ($evaluatable->standar_mutu_id ?? $evaluatable->standarMutu?->nama_standar ?? 'tanpa-standar')
            : 'tanpa-standar';
    }

    private function prodiGroupKey(RencanaTindakLanjut $rtl): string
    {
        $ikks = $rtl->temuan?->evaluasiIndikator?->evaluatable;

        if (! $ikks instanceof IndikatorKinerjaKegiatanSatuan) {
            return 'tanpa-sasaran';
        }

        $sasaran = $ikks->indikatorKinerjaKegiatan?->indikatorKinerjaUtama?->sasaranStrategis;

        return (string) ($sasaran?->id ?? $sasaran?->uraian_sasaran ?? 'tanpa-sasaran');
    }

    private function mergeCellsNode(DOMDocument $dom, DOMXPath $xpath): DOMElement
    {
        $mergeCells = $xpath->query('//x:mergeCells')->item(0);

        if ($mergeCells instanceof DOMElement) {
            return $mergeCells;
        }

        $worksheet = $xpath->query('/x:worksheet')->item(0);

        if (! $worksheet instanceof DOMElement) {
            throw new RuntimeException('Struktur worksheet pada template RTL tidak valid.');
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
            throw new RuntimeException("Sel {$coordinate} tidak ditemukan pada template RTL.");
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
