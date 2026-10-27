<?php

namespace Tests\Feature;

use App\Support\WordDocumentPreview;
use RuntimeException;
use Tests\TestCase;

class WordDocumentPreviewTest extends TestCase
{
    private string $previewStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previewStorage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hris-word-preview-'.bin2hex(random_bytes(8));
        mkdir($this->previewStorage, 0700, true);
        $this->app->useStoragePath($this->previewStorage);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->previewStorage.'/app/private/document-previews/*') ?: [] as $file) {
            unlink($file);
        }
        foreach (glob($this->previewStorage.'/*.*') ?: [] as $file) {
            unlink($file);
        }
        foreach (['/app/private/document-previews', '/app/private', '/app', ''] as $directory) {
            if (is_dir($this->previewStorage.$directory)) {
                rmdir($this->previewStorage.$directory);
            }
        }
        parent::tearDown();
    }

    public function test_cache_is_reused_and_replaced_upload_gets_new_preview(): void
    {
        $source = $this->previewStorage.'/form.docx';
        file_put_contents($source, 'first document');
        $converter = new class extends WordDocumentPreview {
            public int $conversions = 0;
            protected function convert(string $source, string $target): void
            {
                $this->conversions++;
                file_put_contents($target, '%PDF-1.7 test preview');
            }
        };
        $first = $converter->pdf($source);
        $this->assertSame($first, $converter->pdf($source));
        $this->assertSame(1, $converter->conversions);
        file_put_contents($source, 'replacement document');
        $this->assertNotSame($first, $converter->pdf($source));
        $this->assertSame(2, $converter->conversions);
    }

    public function test_failed_export_is_not_cached(): void
    {
        $source = $this->previewStorage.'/form.docx';
        file_put_contents($source, 'document');
        $converter = new class extends WordDocumentPreview {
            protected function convert(string $source, string $target): void
            {
                file_put_contents($target, 'invalid output');
            }
        };
        try {
            $converter->pdf($source);
            $this->fail('Invalid conversion should fail.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('valid PDF', $error->getMessage());
        }
        $this->assertSame([], glob($this->previewStorage.'/app/private/document-previews/*.pdf'));
    }

    public function test_converter_preserves_os_environment_missing_from_http_request(): void
    {
        putenv('HRIS_PREVIEW_ENV_TEST=present');
        unset($_SERVER['HRIS_PREVIEW_ENV_TEST'], $_ENV['HRIS_PREVIEW_ENV_TEST']);
        try {
            $converter = new class extends WordDocumentPreview {
                public function process(): \Symfony\Component\Process\Process
                {
                    return $this->conversionProcess('source.docx', 'preview.pdf');
                }
            };
            $this->assertSame('present', $converter->process()->getEnv()['HRIS_PREVIEW_ENV_TEST']);
        } finally {
            putenv('HRIS_PREVIEW_ENV_TEST');
        }
    }

    public function test_word_uses_page_images_and_pdf_keeps_its_canvas_viewer(): void
    {
        $this->app['config']->set('view.compiled', sys_get_temp_dir());
        foreach (['docx', 'doc', 'pdf'] as $extension) {
            $html = view('Admin.adminDocumentPreview', [
                'fileName' => 'form.'.$extension, 'extension' => $extension,
                'viewUrl' => '/admin/documents/123/view',
                'isPdf' => $extension === 'pdf', 'isImage' => false, 'isText' => false,
                'wordText' => null, 'wordImages' => [],
            ])->render();
            $isWord = $extension !== 'pdf';
            $this->assertStringContainsString('data-url="/admin/documents/123/view'.($isWord ? '?preview=pdf' : '').'"', $html);
            $this->assertStringContainsString('data-render-as="'.($isWord ? 'image' : 'canvas').'"', $html);
            $this->assertStringNotContainsString('id="docxViewer"', $html);
        }
    }

    public function test_linux_converter_uses_an_isolated_profile_and_safe_arguments(): void
    {
        $converter = new class extends WordDocumentPreview {
            public function process(): \Symfony\Component\Process\Process
            {
                return $this->libreOfficeProcess('/tmp/preview job/document.docx', '/tmp/preview job');
            }
        };
        $process = $converter->process();
        $command = str_replace('"^%"', '%', $process->getCommandLine());
        $this->assertStringContainsString('file:///tmp/preview%20job/profile', $command);
        $this->assertStringContainsString('--headless', $process->getCommandLine());
        $this->assertStringContainsString('/tmp/preview job/document.docx', $process->getCommandLine());
        $this->assertSame(90.0, $process->getTimeout());
    }
}
