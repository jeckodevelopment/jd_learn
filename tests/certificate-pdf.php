<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
use Jlms\Component\Jlms\Administrator\Infrastructure\CertificatePdf;
function certificatePdfExpect(bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } }
$identity = ['name' => 'Luca (Test)\\', 'course_title' => 'Rigoletto — Opera', 'serial' => 'ABC123', 'completed_at' => '2026-11-13 12:00:00', 'language' => 'it-IT'];
$pdf = CertificatePdf::render($identity, ['heading' => 'Attestato', 'subheading' => 'ha completato', 'footer' => 'JD Learn', 'accent_color' => '#315A70']);
certificatePdfExpect(str_starts_with($pdf, '%PDF-1.4'), 'PDF header missing');
certificatePdfExpect(str_ends_with($pdf, "%%EOF\n"), 'PDF trailer missing');
certificatePdfExpect(str_contains($pdf, 'Luca \\(Test\\)\\\\'), 'PDF text escaping failed');
certificatePdfExpect(str_contains($pdf, 'Rigoletto'), 'Course title missing');
$english = CertificatePdf::render(['name' => 'Student', 'course_title' => 'Course', 'serial' => 'XYZ', 'completed_at' => '2026-11-13', 'language' => 'en-GB'], []);
certificatePdfExpect(str_contains($english, 'Certificate of completion'), 'English fallback missing');
certificatePdfExpect(str_contains($english, 'November 13, 2026'), 'English date missing');
echo "Certificate PDF generation and escaping checks passed\n";
