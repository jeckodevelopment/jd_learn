<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\Registry\Registry;

final class ProtectedVideoStorage
{
    private ProtectedMaterialStorage $files;

    public function __construct(private readonly \Closure $configuration)
    {
        $this->files = new ProtectedMaterialStorage(function (): Registry {
            $config = clone $this->config();
            $config->set('material_storage_root', $config->get('video_storage_root', ''));
            return $config;
        });
    }

    public function config(): Registry { return ($this->configuration)(); }
    public function root(): string { return $this->files->root(); }

    public function limit(): int
    {
        $limits = [VideoFilePolicy::MAX_BYTES, max(1, min(250, (int) $this->config()->get('video_max_mib', 250))) * 1048576];
        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $value = trim((string) ini_get($key));
            $factor = match (strtolower(substr($value, -1))) { 'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1 };
            $bytes = (int) ((float) $value * $factor);
            if ($bytes > 0) { $limits[] = $key === 'post_max_size' ? max(1, $bytes - 65536) : $bytes; }
        }
        return min($limits);
    }

    public function stage(string $source, string $name): array
    {
        if (is_link($source) || !is_file($source)) { throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422); }
        $root = $this->root();
        $meta = VideoFilePolicy::inspect($source, $name, $this->limit(), (string) $this->config()->get('video_ffprobe_path', ''));
        $key = bin2hex(random_bytes(32)) . '.blob'; $path = $root . DIRECTORY_SEPARATOR . $key;
        $input = @fopen($source, 'rb'); $output = @fopen($path, 'xb');
        if (!$input || !$output) {
            if (is_resource($input)) { fclose($input); } if (is_resource($output)) { fclose($output); }
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        try {
            if (!chmod($path, 0640) || stream_copy_to_stream($input, $output, $meta['byte_size'] + 1) !== $meta['byte_size']) {
                throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
            }
        } catch (\Throwable $error) { fclose($input); fclose($output); unlink($path); throw $error; }
        fclose($input); fclose($output);
        if (!hash_equals($meta['sha256'], hash_file('sha256', $path))) { unlink($path); throw new \RuntimeException('COM_JLMS_VIDEO_FILE_INVALID', 422); }
        return $meta + ['storage' => 'local_video', 'storage_key' => $key];
    }

    public function scan(object $asset): array
    {
        $copy = clone $asset; $copy->storage = 'local';
        return $this->files->scan($copy);
    }

    public function inspect(object $asset): string
    {
        if ($asset->storage !== 'local_video' || !preg_match('/\A[a-f0-9]{64}\.blob\z/D', $asset->storage_key)) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $path = $this->root() . DIRECTORY_SEPARATOR . $asset->storage_key;
        $stat = is_link($path) ? false : @stat($path);
        if (!$stat || ($stat['mode'] & 0170000) !== 0100000 || $stat['size'] !== (int) $asset->byte_size) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        return $path;
    }

    public function delivery(object $asset): array
    {
        $path = $this->inspect($asset); $mode = $this->config()->get('video_delivery_mode', 'disabled');
        if ($mode === 'php_bounded' || $mode === 'x_accel') {
            // Upgrade existing sites from the former offload setting to the
            // standard PHP streamer; never emit web-server-specific headers.
            return ['mode' => 'php_bounded', 'path' => $path];
        }
        throw new \RuntimeException('COM_JLMS_DELIVERY_DISABLED', 503);
    }
}
