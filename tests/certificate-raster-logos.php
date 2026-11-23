<?php

declare(strict_types=1);

require dirname(__DIR__) . '/component/administrator/src/Infrastructure/CertificatePdf.php';

use Jlms\Component\Jlms\Administrator\Infrastructure\CertificatePdf;

function assertPdf(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}

$identity = [
    'language' => 'it-IT', 'name' => 'Studente Test', 'course_title' => 'Corso di prova',
    'serial' => 'ABC123', 'completed_at' => '2026-11-17 12:00:00.123456',
];
$template = ['heading' => 'Attestato', 'subheading' => 'ha completato il corso'];
$plain = CertificatePdf::render($identity, $template);
assertPdf(str_starts_with($plain, '%PDF-'), 'PDF without logo');

if (!extension_loaded('gd')) {
    fwrite(STDERR, "GD required for PNG/GIF/WebP/AVIF certificate logos\n");
    exit(1);
}
$dir = sys_get_temp_dir() . '/jlms-logo-test-' . bin2hex(random_bytes(8));
assertPdf(mkdir($dir, 0700), 'Cannot create test directory');
$paths = [];
try {
    $im = imagecreatetruecolor(48, 24);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    $transparent = imagecolorallocatealpha($im, 255, 255, 255, 127);
    imagefilledrectangle($im, 0, 0, 47, 23, $transparent);
    $solid = imagecolorallocatealpha($im, 30, 50, 120, 0);
    imagefilledrectangle($im, 6, 6, 42, 18, $solid);
    $writers = ['png' => 'imagepng', 'jpg' => 'imagejpeg', 'gif' => 'imagegif',
        'webp' => 'imagewebp', 'avif' => 'imageavif'];
    foreach ($writers as $ext => $writer) {
        if (!function_exists($writer)) {
            continue;
        }
        $path = $dir . '/logo.' . $ext;
        assertPdf($writer($im, $path), 'Cannot write ' . $ext);
        $paths[] = $path;
        echo "Testing raster logo: " . $ext . "\\n";
        $pdf = CertificatePdf::render($identity, $template, $path);
        assertPdf(str_starts_with($pdf, '%PDF-'), 'No PDF for ' . $ext);
        assertPdf(str_contains($pdf, '/DCTDecode'), 'Logo not embedded for ' . $ext);
        assertPdf(str_contains(substr($pdf, -128), '%%EOF'), 'PDF incomplete for ' . $ext);
    }
    imagedestroy($im);
    assertPdf(count($paths) >= 2, 'PNG and JPEG fixture required');
    file_put_contents($dir . '/invalid.png', 'not-an-image');
    try {
        CertificatePdf::render($identity, $template, $dir . '/invalid.png');
        throw new RuntimeException('Invalid image accepted');
    } catch (RuntimeException $e) {
        assertPdf($e->getMessage() === 'COM_JLMS_CERTIFICATE_RENDER_FAILED', 'Wrong invalid image error');
    }
} finally {
    foreach (glob($dir . '/*') ?: [] as $file) {
        unlink($file);
    }
    rmdir($dir);
}
echo "Certificate raster logo PDF tests passed\n";
