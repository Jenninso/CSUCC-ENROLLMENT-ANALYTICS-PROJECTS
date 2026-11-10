<?php

namespace App\Services;

use ZipArchive;

/**
 * Lightweight CSV / Excel (.xlsx) reader — no PhpSpreadsheet dependency.
 * Old binary .xls is not supported; save as .xlsx or .csv instead.
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

        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = $this->parseSharedStrings($sharedXml);
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // Fallback: first worksheet entry
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (is_string($name) && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetXml === false || $sheetXml === null || $sheetXml === '') {
            return ['ok' => false, 'rows' => [], 'errors' => ['No worksheet data found in the Excel file.']];
        }

        $rows = $this->parseSheet($sheetXml, $shared);
        if ($rows === []) {
            return ['ok' => false, 'rows' => [], 'errors' => ['The Excel sheet is empty.']];
        }

        return ['ok' => true, 'rows' => $rows, 'errors' => []];
    }

    /**
     * @return list<string>
     */
    private function parseSharedStrings(string $xml): array
    {
        $out = [];
        $sx = @simplexml_load_string($xml);
        if (! $sx) {
            return $out;
        }

        $sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($sx->xpath('//m:si') ?: [] as $si) {
            $texts = $si->xpath('.//m:t') ?: [];
            $parts = [];
            foreach ($texts as $t) {
                $parts[] = (string) $t;
            }
            $out[] = implode('', $parts);
        }

        return $out;
    }

    /**
     * @param  list<string>  $shared
     * @return list<list<string>>
     */
    private function parseSheet(string $xml, array $shared): array
    {
        $sx = @simplexml_load_string($xml);
        if (! $sx) {
            return [];
        }

        $sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $grid = [];
        $maxCol = 0;

        foreach ($sx->xpath('//m:sheetData/m:row') ?: [] as $row) {
            $rAttr = (int) ($row['r'] ?? 0);
            $rowIndex = $rAttr > 0 ? $rAttr - 1 : count($grid);
            foreach ($row->xpath('./m:c') ?: [] as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                $colIndex = $this->columnIndexFromRef($ref);
                if ($colIndex < 0) {
                    continue;
                }
                $maxCol = max($maxCol, $colIndex);
                $type = (string) ($cell['t'] ?? '');
                $value = '';
                if (isset($cell->v)) {
                    $raw = (string) $cell->v;
                    if ($type === 's') {
                        $value = $shared[(int) $raw] ?? '';
                    } else {
                        $value = $raw;
                    }
                } elseif (isset($cell->is)) {
                    $texts = $cell->xpath('.//m:t') ?: [];
                    $parts = [];
                    foreach ($texts as $t) {
                        $parts[] = (string) $t;
                    }
                    $value = implode('', $parts);
                }
                $grid[$rowIndex][$colIndex] = trim($value);
            }
        }

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
