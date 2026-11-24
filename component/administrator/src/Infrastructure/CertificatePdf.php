<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

/** Small dependency-free, single-page A4 landscape certificate renderer. */
final class CertificatePdf
{
    private const WIDTH = 841.89;
    private const HEIGHT = 595.28;

    /** @param array<string, mixed> $identity @param array<string, mixed> $template */
    public static function render(array $identity, array $template, ?string $logoFile = null): string
    {
        $english = str_starts_with(strtolower((string) ($identity["language"] ?? "it")), "en");
        $heading =
            trim((string) ($template["heading"] ?? "")) ?:
            ($english
                ? "Certificate of completion"
                : "Attestato di completamento");
        $subheading =
            trim((string) ($template["subheading"] ?? "")) ?:
            ($english
                ? "has successfully completed the course"
                : "ha completato il corso");
        $footer =
            trim((string) ($template["footer"] ?? "")) ?: ($english ? "Issued by JD Learn" : "Rilasciato da JD Learn");
        $name = trim((string) ($identity["name"] ?? ""));
        $course = trim((string) ($identity["course_title"] ?? ""));
        $serial = trim((string) ($identity["serial"] ?? ""));
        $date = self::date((string) ($identity["completed_at"] ?? ""), $english);
        if ($name === "" || $course === "" || $serial === "" || $date === "") {
            throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
        }

        $accent = self::color((string) ($template["accent_color"] ?? "#315A70"));
        $content = self::drawBox(30, 30, self::WIDTH - 60, self::HEIGHT - 60, $accent, 2.2);
        $content .= self::drawLine(85, 83, self::WIDTH - 170, $accent, 1.2);

        $imageObject = null;
        $imageResources = "";
        if ($logoFile !== null) {
            [$imageObject, $imageBytes, $imageWidth, $imageHeight] = self::jpeg($logoFile);
            $imageResources = " /XObject << /Logo " . $imageObject . " 0 R >>";
            $fit = min(104 / $imageWidth, 48 / $imageHeight);
            $drawWidth = $imageWidth * $fit;
            $drawHeight = $imageHeight * $fit;
            $content .= sprintf(
                "q %.3F 0 0 %.3F %.3F %.3F cm /Logo Do Q\n",
                $drawWidth,
                $drawHeight,
                (self::WIDTH - $drawWidth) / 2,
                512 - $drawHeight / 2,
            );
        }

        $content .= self::center($heading, 457, 25, $accent, true);
        $content .= self::center($subheading, 409, 15, [0.22, 0.25, 0.28]);
        $content .= self::center($name, 348, 28, [0.12, 0.15, 0.18], true, 48);
        $content .= self::center($english ? "for completing" : "per aver completato", 306, 13, [0.32, 0.34, 0.36]);
        $content .= self::center($course, 268, 20, [0.16, 0.19, 0.22], true, 62);
        $content .= self::center(($english ? "Completed on " : "Completato il ") . $date, 190, 13, [0.25, 0.28, 0.3]);
        $content .= self::center(($english ? "Certificate code: " : "Codice attestato: ") . $serial, 145, 10, [
            0.38,
            0.4,
            0.42,
        ]);
        $content .= self::center($footer, 57, 10, [0.38, 0.4, 0.42], false, 100);

        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[3] =
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " .
            self::WIDTH .
            " " .
            self::HEIGHT .
            "] /Resources << /Font << /F1 4 0 R /F2 5 0 R >>" .
            $imageResources .
            " >> /Contents 6 0 R >>";
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        $objects[6] = "<< /Length " . strlen($content) . ">>\nstream\n" . $content . "endstream";
        if ($imageObject !== null) {
            $objects[7] =
                "<< /Type /XObject /Subtype /Image /Width " .
                $imageWidth .
                " /Height " .
                $imageHeight .
                " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " .
                strlen($imageBytes) .
                ">>\nstream\n" .
                $imageBytes .
                "\nendstream";
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n ", $offsets[$i]) . "\n";
        }
        return $pdf .
            "trailer" .
            "\n<< /Size " .
            (count($objects) + 1) .
            " /Root 1 0 R >>\nstartxref\n" .
            $xref .
            "\n%%EOF\n";
    }

    private static function date(string $value, bool $english): string
    {
        $value = trim($value);
        // Persisted SQL DATETIME(6) may contain up to six fractional digits.
        // A certificate must never silently use today's date for a missing or
        // relative timestamp. Completion dates come from persisted SQL/ISO values.
        if (
            preg_match(
                "/\A\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})?)?\z/D",
                $value,
            ) !== 1
        ) {
            return "";
        }
        try {
            $date = new \DateTimeImmutable($value, new \DateTimeZone("UTC"));
            $errors = \DateTimeImmutable::getLastErrors();
            if (is_array($errors) && ($errors["warning_count"] > 0 || $errors["error_count"] > 0)) {
                return "";
            }
        } catch (\Throwable) {
            return "";
        }
        return $date->format($english ? "F j, Y" : "d/m/Y");
    }

    private static function color(string $hex): array
    {
        if (preg_match("/\A#?([a-f0-9]{6})\z/iD", $hex, $matches) !== 1) {
            $matches[1] = "315A70";
        }
        return [
            hexdec(substr($matches[1], 0, 2)) / 255,
            hexdec(substr($matches[1], 2, 2)) / 255,
            hexdec(substr($matches[1], 4, 2)) / 255,
        ];
    }

    private static function drawBox(float $x, float $y, float $width, float $height, array $color, float $line): string
    {
        return sprintf(
            "%.4F %.4F %.4F RG %.2F w %.2F %.2F %.2F %.2F re S\n",
            ...[...$color, $line, $x, $y, $width, $height],
        );
    }

    private static function drawLine(float $x, float $y, float $width, array $color, float $line): string
    {
        return sprintf(
            "%.4F %.4F %.4F RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
            ...[...$color, $line, $x, $y, $width, $y],
        );
    }

    private static function center(
        string $value,
        float $y,
        float $size,
        array $color,
        bool $bold = false,
        int $wrap = 0,
    ): string {
        $encoded = self::encode($value);
        if ($wrap > 0 && strlen($encoded) > $wrap) {
            $encoded = wordwrap($encoded, $wrap, "\n", true);
        }
        $lines = explode("\n", $encoded);
        $output = "";
        foreach ($lines as $index => $line) {
            $x = max(54, (self::WIDTH - strlen($line) * $size * 0.48) / 2);
            $lineY = $y - $index * ($size + 7);
            $output .= sprintf(
                "%.4F %.4F %.4F rg BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n",
                ...[...$color, $bold ? "F2" : "F1", $size, $x, $lineY, self::escape($line)],
            );
        }
        return $output;
    }

    private static function encode(string $value): string
    {
        $value = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', "", $value) ?? "");
        $encoded = iconv("UTF-8", "Windows-1252//TRANSLIT", $value);
        if ($encoded === false) {
            throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
        }
        return $encoded;
    }

    private static function escape(string $value): string
    {
        return str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $value);
    }

    /** @return array{0:int,1:string,2:int,3:int} */
    private static function jpeg(string $path): array
    {
        if (!is_file($path) || is_link($path) || !is_readable($path)) {
            throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
        }
        $size = filesize($path);
        if ($size === false || $size < 1 || $size > 10485760) {
            throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
        }
        $info = @getimagesize($path);
        $bytes = @file_get_contents($path);
        if (
            !$info ||
            !is_string($bytes) ||
            strlen($bytes) !== $size ||
            $info[0] < 1 ||
            $info[1] < 1 ||
            $info[0] > 4000 ||
            $info[1] > 4000
        ) {
            throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
        }
        $mime = (string) ($info["mime"] ?? "");
        if (!in_array($mime, ["image/jpeg", "image/png", "image/gif", "image/webp", "image/avif"], true)) {
            throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
        }
        if ($mime === "image/jpeg") {
            return [7, $bytes, (int) $info[0], (int) $info[1]];
        }
        // Decode common raster formats via GD; no SVG, remote images or path input.
        $decoders = [
            "image/png" => "imagecreatefrompng",
            "image/gif" => "imagecreatefromgif",
            "image/webp" => "imagecreatefromwebp",
            "image/avif" => "imagecreatefromavif",
        ];
        $decoder = $decoders[$mime] ?? "";
        if (!function_exists($decoder) || !function_exists("imagecreatetruecolor") || !function_exists("imagejpeg")) {
            throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
        }
        $source = @$decoder($path);
        if (!($source instanceof \GdImage)) {
            throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
        }
        $width = (int) $info[0];
        $height = (int) $info[1];
        $canvas = imagecreatetruecolor($width, $height);
        if (!($canvas instanceof \GdImage)) {
            imagedestroy($source);
            throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
        }
        try {
            // Flatten PNG/WebP/AVIF transparency onto white for JPEG embedding.
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, $white);
            imagealphablending($canvas, true);
            if (!imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height)) {
                throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
            }
            ob_start();
            try {
                $ok = imagejpeg($canvas, null, 88);
                $converted = ob_get_contents();
            } finally {
                ob_end_clean();
            }
            if (!$ok || !is_string($converted) || $converted === "") {
                throw new \RuntimeException("COM_JLMS_CERTIFICATE_RENDER_FAILED", 503);
            }
            return [7, $converted, $width, $height];
        } finally {
            imagedestroy($source);
            imagedestroy($canvas);
        }
    }
}
