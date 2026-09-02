<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\Export;
use App\Models\User;
use App\Support\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The certificate is only useful if the QR code actually reaches the page —
 * without it there is no way to verify a printed document, which is the whole
 * point of the export. dompdf drops an image *silently* when it cannot write
 * its temp files, so these assertions look inside the generated PDF.
 */
class ExportPdfTest extends TestCase
{
    use RefreshDatabase;

    private function issue(): Export
    {
        $registrar = User::factory()->registrar()->create();
        $record = AcademicRecord::factory()->create();

        return app(ExportService::class)->issue($record, $registrar);
    }

    public function test_the_certificate_embeds_both_the_logo_and_the_qr_code(): void
    {
        $pdf = app(ExportService::class)->pdf($this->issue())->output();

        $this->assertStringStartsWith('%PDF-', $pdf);

        // One XObject for the QR, one for the flattened logo. A dropped image
        // leaves the text intact, so only this count catches the regression.
        $this->assertSame(
            2,
            substr_count($pdf, '/Subtype /Image'),
            'The certificate should embed exactly two images: the mark and the QR code.',
        );
    }

    public function test_dompdf_renders_into_a_directory_the_app_owns(): void
    {
        $options = app(ExportService::class)->pdf($this->issue())->getDomPDF()->getOptions();
        $tempDir = (string) $options->getTempDir();

        // sys_get_temp_dir() resolves to C:\WINDOWS under some SAPIs, which is
        // not writable — dompdf then discards every image without erroring.
        $this->assertTrue(is_dir($tempDir), "dompdf temp dir does not exist: {$tempDir}");
        $this->assertTrue(is_writable($tempDir), "dompdf temp dir is not writable: {$tempDir}");
        $this->assertStringStartsWith(storage_path(), $tempDir);
    }

    public function test_the_logo_is_flattened_so_it_needs_no_soft_mask(): void
    {
        $pdf = app(ExportService::class)->pdf($this->issue())->output();

        // A soft mask means the source alpha survived — that path needs a
        // second temp file and renders the mark as a black box when it fails.
        $this->assertSame(0, substr_count($pdf, '/SMask'));
    }

    public function test_a_registrar_can_download_the_certificate_over_http(): void
    {
        $export = $this->issue();
        $registrar = User::factory()->registrar()->create();

        $response = $this->actingAs($registrar)
            ->get(route('exports.download', $export))
            ->assertOk();

        $pdf = $response->getContent();

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(2, substr_count($pdf, '/Subtype /Image'));
    }
}
