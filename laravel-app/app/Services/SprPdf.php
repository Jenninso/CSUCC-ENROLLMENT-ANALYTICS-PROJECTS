<?php

namespace App\Services;

use App\Models\SpesApplication;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class SprPdf
{
    public function download(SpesApplication $application, bool $studentMode): BinaryFileResponse|Response
    {
        $path = $this->write($application, $studentMode);

        return response()->download($path, $application->reference_no.'.pdf', [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    public function write(SpesApplication $application, bool $studentMode): string
    {
        $binary = $this->browser();
        if ($binary === null) {
            throw new RuntimeException('Chrome or Edge is required to export the long-bond SPR PDF.');
        }

        $headerPath = public_path('images/branding/csu-cabadbaran-header.png');
        $headerSrc = is_file($headerPath)
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($headerPath))
            : '';

        $html = view('spes.print', [
            'application' => $application,
            'studentMode' => $studentMode,
            'forPdf' => true,
            'headerSrc' => $headerSrc,
        ])->render();

        $dir = storage_path('app/spr-pdf');
        File::ensureDirectoryExists($dir);
        $token = $application->id.'-'.bin2hex(random_bytes(4));
        $htmlPath = $dir.DIRECTORY_SEPARATOR.$token.'.html';
        $pdfPath = $dir.DIRECTORY_SEPARATOR.$token.'.pdf';
        $profile = $dir.DIRECTORY_SEPARATOR.'profile-'.$token;
        File::put($htmlPath, $html);

        $process = new Process([
            $binary,
            '--headless=new',
            '--disable-gpu',
            '--no-first-run',
            '--no-default-browser-check',
            '--disable-extensions',
            '--hide-scrollbars',
            '--no-pdf-header-footer',
            '--user-data-dir='.$profile,
            '--print-to-pdf='.$pdfPath,
            $this->fileUrl($htmlPath),
        ]);
        $process->setTimeout(45);
        $process->run();

        File::delete($htmlPath);
        File::deleteDirectory($profile);

        if (! is_file($pdfPath) || filesize($pdfPath) < 1000 || ! str_starts_with((string) file_get_contents($pdfPath, false, null, 0, 5), '%PDF')) {
            File::delete($pdfPath);
            throw new RuntimeException('The SPR PDF could not be created.');
        }

        return $pdfPath;
    }

    private function browser(): ?string
    {
        $candidates = [
            getenv('PROGRAMFILES').'\\Google\\Chrome\\Application\\chrome.exe',
            getenv('PROGRAMFILES(X86)').'\\Google\\Chrome\\Application\\chrome.exe',
            getenv('LOCALAPPDATA').'\\Google\\Chrome\\Application\\chrome.exe',
            getenv('PROGRAMFILES(X86)').'\\Microsoft\\Edge\\Application\\msedge.exe',
            getenv('PROGRAMFILES').'\\Microsoft\\Edge\\Application\\msedge.exe',
        ];

        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function fileUrl(string $path): string
    {
        return 'file:///'.str_replace(' ', '%20', str_replace('\\', '/', $path));
    }
}
