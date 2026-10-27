<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

class WordDocumentPreview
{
    public function response(string $source)
    {
        try {
            return response()->file($this->pdf($source), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="document-preview.pdf"',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        } catch (\Throwable $error) {
            report($error);
            return response()->json(['message' => 'Unable to generate the Word document preview.'], 503);
        }
    }

    public function pdf(string $source): string
    {
        if (!is_file($source) || !in_array(strtolower(pathinfo($source, PATHINFO_EXTENSION)), ['doc', 'docx'], true)) {
            throw new RuntimeException('Word document not found.');
        }
        $directory = storage_path('app/private/document-previews');
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create document preview cache.');
        }
        $key = hash_file('sha256', $source);
        $target = $directory.DIRECTORY_SEPARATOR.$key.'.pdf';
        $lock = fopen($directory.DIRECTORY_SEPARATOR.$key.'.lock', 'c');
        if (!$lock) {
            throw new RuntimeException('Cannot lock document preview.');
        }
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('Cannot lock document preview.');
            }
            if ($this->isPdf($target)) {
                return $target;
            }
            $temporary = $directory.DIRECTORY_SEPARATOR.$key.'-'.bin2hex(random_bytes(6)).'.pdf';
            try {
                $this->convert($source, $temporary);
                if (!$this->isPdf($temporary) || !rename($temporary, $target)) {
                    throw new RuntimeException('Word did not produce a valid PDF preview.');
                }
            } finally {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
            return $target;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function isPdf(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return false;
        }
        try {
            return fread($handle, 5) === '%PDF-';
        } finally {
            fclose($handle);
        }
    }

    protected function convert(string $source, string $target): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->convertWithLibreOffice($source, $target);
            return;
        }
        $process = $this->conversionProcess($source, $target);
        $process->mustRun();
    }

    private function convertWithLibreOffice(string $source, string $target): void
    {
        // Each export needs its own profile: shared profiles can silently skip concurrent jobs.
        $workspace = $target.'.work';
        if (!mkdir($workspace, 0700, true)) {
            throw new RuntimeException('Cannot create Word conversion workspace.');
        }
        try {
            $input = $workspace.'/document.'.strtolower(pathinfo($source, PATHINFO_EXTENSION));
            if (!copy($source, $input)) {
                throw new RuntimeException('Cannot prepare Word document for conversion.');
            }
            $process = $this->libreOfficeProcess($input, $workspace);
            $process->mustRun();
            if (!$this->isPdf($workspace.'/document.pdf') || !rename($workspace.'/document.pdf', $target)) {
                throw new RuntimeException('LibreOffice did not produce a valid PDF preview.');
            }
        } finally {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($workspace, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($workspace);
        }
    }

    protected function libreOfficeProcess(string $source, string $workspace): Process
    {
        $profile = 'file://'.str_replace('%2F', '/', rawurlencode($workspace.'/profile'));
        $process = new Process([
            'libreoffice', '-env:UserInstallation='.$profile,
            '--headless', '--convert-to', 'pdf', '--outdir', $workspace, $source,
        ], base_path(), getenv());
        $process->setTimeout(90);
        return $process;
    }

    protected function conversionProcess(string $source, string $target): Process
    {
        // HTTP request variables omit OS variables; pass the real environment explicitly.
        $process = new Process([
            'cscript.exe', '//NoLogo',
            base_path('scripts/convert_word_preview.vbs'), $source, $target,
        ], base_path(), getenv());
        $process->setTimeout(90);
        return $process;
    }
}
