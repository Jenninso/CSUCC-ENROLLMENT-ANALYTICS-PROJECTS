<?php

namespace App\Services;

use XMLReader;
use ZipArchive;

/**
 * Lightweight CSV / Excel (.xlsx) reader — no PhpSpreadsheet dependency.
 * Old binary .xls is not supported; save as .xlsx or .csv instead.
 *
 * .xlsx parsing uses XMLReader over zip:// streams so large CEIT extracts
 * do not need the whole worksheet XML in memory at once.
 */
class SpreadsheetFileReader
{
    /**
     * @return array{ok: bool, rows: list<list<string>>, errors: list<string>}
     */
    public function read(string $path, ?string $originalName = null): array
    {
        $ext = strtolower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));

        return match ($ext) {
            'csv', 'txt' => $this->readCsv($path),
            'xlsx' => $this->readXlsx($path),
            'xls' => [
                'ok' => false,
                'rows' => [],
                'errors' => ['Old .xls files are not supported. Save the file as .xlsx or .csv and try again.'],
            ],
            default => [
                'ok' => false,
                'rows' => [],
                'errors' => ['Unsupported file type. Upload a CSV or Excel (.xlsx) file.'],
            ],
        };
    }

    /**
     * @return array{ok: bool, rows: list<list<string>>, errors: list<string>}
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return ['ok' => false, 'rows' => [], 'errors' => ['Unable to read the uploaded file.']];
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === 1 && trim((string) $data[0]) === '') {
                continue;
            }
            $rows[] = array_map(fn ($v) => trim((string) $v), $data);
        }
        fclose($handle);

        if ($rows === []) {
            return ['ok' => false, 'rows' => [], 'errors' => ['The file is empty.']];
        }

        return ['ok' => true, 'rows' => $rows, 'errors' => []];
    }

    /**
     * @return array{ok: bool, rows: list<list<string>>, errors: list<string>}
     */
    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return ['ok' => false, 'rows' => [], 'errors' => ['Unable to open the Excel file.']];
        }

        $sheetPath = null;
        if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
            $sheetPath = 'xl/worksheets/sheet1.xml';
        } else {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (is_string($name) && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                    $sheetPath = $name;
                    break;
                }
            }
        }

        $hasShared = $zip->locateName('xl/sharedStrings.xml') !== false;
        $zip->close();

        if ($sheetPath === null) {
            return ['ok' => false, 'rows' => [], 'errors' => ['No worksheet data found in the Excel file.']];
        }

        $shared = $hasShared ? $this->parseSharedStringsFromZip($path) : [];
        $rows = $this->parseSheetFromZip($path, $sheetPath, $shared);

        if ($rows === []) {
            return ['ok' => false, 'rows' => [], 'errors' => ['The Excel sheet is empty.']];
        }

        return ['ok' => true, 'rows' => $rows, 'errors' => []];
    }

    /**
     * @return list<string>
     */
    private function parseSharedStringsFromZip(string $path): array
    {
        $reader = new XMLReader;
        $uri = 'zip://'.$path.'#xl/sharedStrings.xml';
        if (! @$reader->open($uri)) {
            return [];
        }

        $out = [];
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $out[] = $this->readInlineText($reader, 'si');
            }
        }
        $reader->close();

        return $out;
    }

    /**
     * @param  list<string>  $shared
     * @return list<list<string>>
     */
    private function parseSheetFromZip(string $path, string $sheetPath, array $shared): array
    {
        $reader = new XMLReader;
        $uri = 'zip://'.$path.'#'.$sheetPath;
        if (! @$reader->open($uri)) {
            return [];
        }

        $grid = [];
        $maxCol = 0;
        $sequentialRow = 0;

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }

            $rAttr = (int) ($reader->getAttribute('r') ?? 0);
            $rowIndex = $rAttr > 0 ? $rAttr - 1 : $sequentialRow;
            $sequentialRow++;

            $rowXml = $reader->readOuterXml();
            if ($rowXml === '') {
                continue;
            }

            $rowReader = new XMLReader;
            $rowReader->xml($rowXml, null, LIBXML_NONET | LIBXML_COMPACT);
            while ($rowReader->read()) {
                if ($rowReader->nodeType !== XMLReader::ELEMENT || $rowReader->localName !== 'c') {
                    continue;
                }

                $ref = (string) ($rowReader->getAttribute('r') ?? '');
                $colIndex = $this->columnIndexFromRef($ref);
                if ($colIndex < 0) {
                    continue;
                }
                $maxCol = max($maxCol, $colIndex);
                $type = (string) ($rowReader->getAttribute('t') ?? '');
                $value = $this->readCellValue($rowReader, $type, $shared);
                $grid[$rowIndex][$colIndex] = trim($value);
            }
            $rowReader->close();
        }
        $reader->close();

        if ($grid === []) {
            return [];
        }

        ksort($grid);
        $rows = [];
        foreach ($grid as $rowCells) {
            $line = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $line[] = $rowCells[$c] ?? '';
            }
            if (implode('', $line) === '') {
                continue;
            }
            $rows[] = $line;
        }

        return $rows;
    }

    /**
     * @param  list<string>  $shared
     */
    private function readCellValue(XMLReader $reader, string $type, array $shared): string
    {
        $depth = $reader->depth;
        $value = '';
        $inline = '';

        while ($reader->read() && $reader->depth > $depth) {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }
            if ($reader->localName === 'v') {
                $raw = $reader->readString();
                if ($type === 's') {
                    $value = $shared[(int) $raw] ?? '';
                } else {
                    $value = $raw;
                }
            } elseif ($reader->localName === 't') {
                $inline .= $reader->readString();
            }
        }

        if ($value === '' && $inline !== '') {
            return $inline;
        }

        return $value;
    }

    private function readInlineText(XMLReader $reader, string $untilLocalName): string
    {
        $depth = $reader->depth;
        $parts = [];
        while ($reader->read() && $reader->depth > $depth) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 't') {
                $parts[] = $reader->readString();
            }
        }

        return implode('', $parts);
    }

    private function columnIndexFromRef(string $ref): int
    {
        if (! preg_match('/^([A-Z]+)/i', $ref, $m)) {
            return -1;
        }
        $letters = strtoupper($m[1]);
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }
}
