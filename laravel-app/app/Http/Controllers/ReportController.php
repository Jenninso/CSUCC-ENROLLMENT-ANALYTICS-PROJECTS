<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\Alert;
use App\Models\Forecast;
use App\Services\AnalyticsService;
use App\Services\AuditLogger;
use App\Services\DataQualityService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(AnalyticsService $analytics)
    {
        return view('reports.index', $analytics->filterOptions());
    }

    public function store(Request $request, AnalyticsService $analytics, DataQualityService $quality, AuditLogger $audit)
    {
        $validated = $request->validate([
            'report_type' => ['required', 'in:executive,enrollment_summary,program_analysis,college_analysis,retention'],
            'format' => ['required', 'in:csv,pdf,xlsx'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'college_id' => ['nullable', 'exists:colleges,id'],
            'program_id' => ['nullable', 'exists:programs,id'],
        ]);

        $data = $analytics->dashboard($validated);
        $payload = [
            'data' => $data,
            'quality' => $quality->report(),
            'insights' => AiInsight::latest()->limit(8)->get(),
            'forecast' => Forecast::latest()->first(),
            'alerts' => Alert::latest()->limit(8)->get(),
            'filters' => $validated,
            'generated_at' => now(),
        ];

        $audit->log('generate_report', 'reports', $validated['report_type'].' as '.$validated['format']);

        $rows = $this->rows($payload);
        $stamp = now()->format('Y-m-d_His');

        if ($validated['format'] === 'pdf') {
            return response($this->pdfBytes($rows), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$validated['report_type'].'_'.$stamp.'.pdf"',
            ]);
        }

        if ($validated['format'] === 'xlsx') {
            return response($this->xlsxBytes($rows), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$validated['report_type'].'_'.$stamp.'.xlsx"',
            ]);
        }

        return $this->csv($rows, $validated['report_type'].'_'.$stamp.'.csv');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function rows(array $payload): array
    {
        $rows = [
            ['Section', 'Metric', 'Value'],
            ['Overview', 'Total enrollment', (string) $payload['data']['totalEnrollment']],
            ['Overview', 'Growth rate %', (string) $payload['data']['growthRate']],
            ['Overview', 'Retention rate %', (string) $payload['data']['retentionRate']],
            ['Overview', 'Data quality score %', (string) $payload['quality']['score']],
        ];
        foreach ($payload['data']['topPrograms'] as $program) {
            $rows[] = ['Program', (string) $program['program_code'], (string) $program['current']];
        }
        if ($payload['forecast']) {
            $rows[] = ['Forecast', (string) $payload['forecast']->forecast_period, (string) $payload['forecast']->predicted_enrollment];
        }

        return $rows;
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $rows
     */
    private function csv(array $rows, string $filename): StreamedResponse
    {
        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $rows
     */
    private function xlsxBytes(array $rows): string
    {
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach ($rows as $index => $row) {
            $line = $index + 1;
            $sheet .= '<row r="'.$line.'">';
            foreach (['A', 'B', 'C'] as $column => $letter) {
                $value = htmlspecialchars((string) ($row[$column] ?? ''), ENT_XML1);
                $sheet .= '<c r="'.$letter.$line.'" t="inlineStr"><is><t>'.$value.'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'</Relationships>',
            'xl/worksheets/sheet1.xml' => $sheet,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'report');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();
        $bytes = (string) file_get_contents($tmp);
        unlink($tmp);

        return $bytes;
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $rows
     */
    private function pdfBytes(array $rows): string
    {
        $lines = ['CEIT enrollment report', ''];
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }
            $lines[] = $row[0].': '.$row[1].' = '.$row[2];
        }
        $commands = '';
        $y = 800;
        foreach ($lines as $line) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], preg_replace('/[^\x20-\x7E]/', '', $line) ?? '');
            $commands .= "BT /F1 11 Tf 48 {$y} Td ({$safe}) Tj ET\n";
            $y -= 16;
            if ($y < 48) {
                break;
            }
        }
        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Count 1 /Kids [3 0 R] >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n",
            "4 0 obj << /Length ".strlen($commands)." >> stream\n".$commands."endstream\nendobj\n",
            "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer << /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }
}
