<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

/** MP4/H.264 (+ optional AAC). No shell, network protocols or caller-supplied command switches. */
final class VideoFilePolicy
{
    public const MAX_BYTES = 262144000;

    public static function inspect(string $path, string $name, int $limit, string $binary): array
    {
        MaterialFilePolicy::name($name);
        $size = filesize($path);
        if (!$size || $size > min($limit, self::MAX_BYTES)) { throw new \RuntimeException('COM_JLMS_FILE_SIZE', 413); }
        $head = file_get_contents($path, false, null, 0, 12);
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'mp4' || substr((string) $head, 4, 4) !== 'ftyp'
            || (new \finfo(FILEINFO_MIME_TYPE))->file($path) !== 'video/mp4') { throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422); }
        if (!function_exists('proc_open') || $binary === '' || $binary[0] !== '/' || !is_file($binary) || !is_executable($binary) || is_link($binary)) {
            throw new \RuntimeException('COM_JLMS_VIDEO_PROBE_UNAVAILABLE', 503);
        }
        $pipes = [];
        $process = @proc_open([$binary, '-v', 'error', '-protocol_whitelist', 'file', '-enable_drefs', '0', '-use_absolute_path', '0',
            '-f', 'mov', '-i', $path, '-show_entries', 'format=duration,format_name:stream=codec_type,codec_name,width,height,pix_fmt', '-of', 'json'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) { throw new \RuntimeException('COM_JLMS_VIDEO_PROBE_UNAVAILABLE', 503); }
        fclose($pipes[0]); stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
        $output = ''; $errors = ''; $deadline = microtime(true) + 10; $exit = -1;
        try {
            do {
                $output .= (string) stream_get_contents($pipes[1], 65537 - strlen($output));
                $errors .= (string) stream_get_contents($pipes[2], 4097 - strlen($errors));
                $status = proc_get_status($process);
                if (strlen($output) > 65536 || strlen($errors) > 4096 || microtime(true) > $deadline) {
                    proc_terminate($process, 9); throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422);
                }
                if (!$status['running']) {
                    $exit = $status['exitcode'];
                    $output .= (string) stream_get_contents($pipes[1], 65537 - strlen($output));
                    $errors .= (string) stream_get_contents($pipes[2], 4097 - strlen($errors));
                    break;
                }
                usleep(10000);
            } while (true);
        } finally {
            fclose($pipes[1]); fclose($pipes[2]); proc_close($process);
        }
        if ($exit !== 0 || strlen($output) > 65536 || strlen($errors) > 4096) { throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422); }
        try { $data = json_decode($output, true, 16, JSON_THROW_ON_ERROR); }
        catch (\JsonException $error) { throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422); }
        $duration = $data['format']['duration'] ?? null; $streams = $data['streams'] ?? null;
        if (!is_string($duration) || !preg_match('/\A[0-9]{1,5}(?:\.[0-9]{1,6})?\z/D', $duration)
            || (float) $duration < 1 || (float) $duration > 28800 || !is_array($streams) || count($streams) < 1 || count($streams) > 2) {
            throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422);
        }
        $videos = 0; $audios = 0;
        foreach ($streams as $stream) {
            if (($stream['codec_type'] ?? '') === 'video' && ($stream['codec_name'] ?? '') === 'h264'
                && ($stream['pix_fmt'] ?? '') === 'yuv420p' && ($stream['width'] ?? 0) >= 16 && ($stream['width'] ?? 8193) <= 8192
                && ($stream['height'] ?? 0) >= 16 && ($stream['height'] ?? 8193) <= 8192) { $videos++; }
            elseif (($stream['codec_type'] ?? '') === 'audio' && ($stream['codec_name'] ?? '') === 'aac') { $audios++; }
            else { throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422); }
        }
        if ($videos !== 1 || $audios > 1) { throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422); }
        return ['original_name' => $name, 'mime_type' => 'video/mp4', 'byte_size' => $size, 'sha256' => hash_file('sha256', $path),
            'duration_ms' => (int) round((float) $duration * 1000), 'video_codec' => 'h264', 'audio_codec' => $audios ? 'aac' : null];
    }
}
