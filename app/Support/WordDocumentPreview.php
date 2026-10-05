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
            throw new RuntimeException('Word previews require Microsoft Word on the Windows application host.');
        }
        $process = $this->conversionProcess($source, $target);
        $process->mustRun();
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
