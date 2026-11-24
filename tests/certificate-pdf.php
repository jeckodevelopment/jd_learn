<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/certificate-pdf.functions.php';


require __DIR__ . '/bootstrap.php';
use Jlms\Component\Jlms\Administrator\Infrastructure\CertificatePdf;

$identity = ['name' => 'Luca (Test)\\', 'course_title' => 'Rigoletto — Opera', 'serial' => 'ABC123',
    'completed_at' => '2026-11-13 12:00:00', 'language' => 'it-IT'];
$pdf = CertificatePdf::render($identity, ['heading' => 'Attestato', 'subheading' => 'ha completato',
    'footer' => 'JD Learn', 'accent_color' => '#315A70']);
certificatePdfExpect(str_starts_with($pdf, '%PDF-1.4'), 'PDF header missing');
certificatePdfExpect(str_ends_with($pdf, "%%EOF\n"), 'PDF trailer missing');
certificatePdfExpect(str_contains($pdf, 'Luca \\(Test\\)\\\\'), 'PDF text escaping failed');
certificatePdfExpect(str_contains($pdf, 'Rigoletto'), 'Course title missing');
$english = CertificatePdf::render(['name' => 'Student', 'course_title' => 'Course', 'serial' => 'XYZ',
    'completed_at' => '2026-11-13', 'language' => 'en-GB'], []);
certificatePdfExpect(str_contains($english, 'Certificate of completion'), 'English fallback missing');
certificatePdfExpect(str_contains($english, 'November 13, 2026'), 'English date missing');
foreach (['', 'tomorrow', '2026-02-30', '2026-13-01', '2026-11-13 junk'] as $invalidDate) {
    try {
        CertificatePdf::render([
            'name' => 'Student',
            'course_title' => 'Course',
            'serial' => 'XYZ',
            'completed_at' => $invalidDate,
            'language' => 'en-GB',
        ], []);
        throw new RuntimeException('Invalid completion date accepted: ' . $invalidDate);
    } catch (RuntimeException $error) {
        certificatePdfExpect(
            $error->getMessage() === 'COM_JLMS_CERTIFICATE_RENDER_FAILED',
            'Unexpected failure for invalid completion date: ' . $invalidDate
        );
    }
}
$iso = CertificatePdf::render([
    'name' => 'Student', 'course_title' => 'Course', 'serial' => 'XYZ',
    'completed_at' => '2026-11-13T12:00:00Z', 'language' => 'en-GB',
], []);
certificatePdfExpect(str_contains($iso, 'November 13, 2026'), 'Valid ISO completion date rejected');
echo "Certificate PDF generation and escaping checks passed\n";
