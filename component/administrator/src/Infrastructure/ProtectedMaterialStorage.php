<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\Registry\Registry;

/** Files have random immutable keys. No URL, original name or client path is used as a storage key. */
final class ProtectedMaterialStorage
{
    public function __construct(private readonly \Closure $configuration)
    {
    }

    public function config(): Registry
    {
        return ($this->configuration)();
    }

    public function root(): string
    {
        $configured = (string) $this->config()->get('material_storage_root', '');
        $absolute = str_starts_with($configured, '/') || (strlen($configured) > 2 && ctype_alpha($configured[0])
            && $configured[1] === ':' && in_array($configured[2], ['/', '\\'], true));
        if (!$absolute) { throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503); }
        $root = realpath($configured);
        if ($configured === '' || $root === false || !is_dir($root) || !is_writable($root) || is_link($configured)) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        foreach ([JPATH_ROOT, defined('JPATH_PUBLIC') ? JPATH_PUBLIC : JPATH_ROOT, $_SERVER['DOCUMENT_ROOT'] ?? ''] as $public) {
            $base = $public !== '' ? realpath($public) : false;
            if ($base && ($root === $base || str_starts_with($root . DIRECTORY_SEPARATOR, $base . DIRECTORY_SEPARATOR))) {
                throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
            }
        }
        return $root;
    }

    public function limit(): int
    {
        $parse = static function (string $value): int {
            $value = trim($value);
            $factor = match (strtolower(substr($value, -1))) { 'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1 };
            return (int) ((float) $value * $factor);
        };
        $limits = [MaterialFilePolicy::MAX_BYTES, max(1, min(20, (int) $this->config()->get('material_max_mib', 20))) * 1048576];
        foreach (['upload_max_filesize', 'post_max_size'] as $setting) {
            $value = $parse((string) ini_get($setting));
            if ($value > 0) {
                $limits[] = $setting === 'post_max_size' ? max(1, $value - 65536) : $value;
            }
        }
        return min($limits);
    }

    /** Trusted local file source; HTTP callers must first verify is_uploaded_file(). */
    public function stage(string $source, string $name): array
    {
        $root = $this->root();
        if (is_link($source) || !is_file($source)) {
            throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
        }
        $metadata = MaterialFilePolicy::inspect($source, $name, $this->limit());
        $key = bin2hex(random_bytes(32)) . '.blob';
        $path = $root . DIRECTORY_SEPARATOR . $key;
        $input = @fopen($source, 'rb');
        $output = @fopen($path, 'xb');
        if (!$input || !$output) {
            if (is_resource($input)) { fclose($input); }
            if (is_resource($output)) { fclose($output); }
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        try {
            if (!@chmod($path, 0600)) {
                throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
            }
            $copied = stream_copy_to_stream($input, $output, $metadata['byte_size'] + 1);
            if ($copied !== $metadata['byte_size']) {
                throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
            }
        } catch (\Throwable $error) {
            fclose($input); fclose($output); unlink($path); throw $error;
        }
        fclose($input); fclose($output);
        if (!hash_equals($metadata['sha256'], hash_file('sha256', $path))) {
            unlink($path);
            throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
        }
        return $metadata + ['storage' => 'local', 'storage_key' => $key];
    }

    public function open(object $asset)
    {
        if ($asset->storage !== 'local' || preg_match('/\A[a-f0-9]{64}\.blob\z/D', $asset->storage_key) !== 1) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $path = $this->root() . DIRECTORY_SEPARATOR . $asset->storage_key;
        if (is_link($path) || !is_file($path)) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $handle = @fopen($path, 'rb');
        $stat = $handle ? fstat($handle) : false;
        if (!$stat || ($stat['mode'] & 0170000) !== 0100000 || $stat['size'] !== (int) $asset->byte_size) {
            if (is_resource($handle)) { fclose($handle); }
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $hash = hash_init('sha256');
        hash_update_stream($hash, $handle);
        if (!hash_equals((string) $asset->sha256, hash_final($hash))) {
            fclose($handle);
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        rewind($handle);
        return $handle;
    }

    /** Deliver an authorised immutable asset through the internal Nginx location, or bounded PHP. */
    public function delivery(object $asset): array
    {
        if (($asset->storage ?? '') !== 'local' || preg_match('/\\A[a-f0-9]{64}\\.blob\\z/D', (string) ($asset->storage_key ?? '')) !== 1) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $key = (string) $asset->storage_key;
        if ((bool) $this->config()->get('material_x_accel_enabled', false)) {
            $prefix = (string) $this->config()->get('material_internal_prefix', '');
            if (!(bool) $this->config()->get('material_internal_confirmed', false)
                || !preg_match('~\\A/(?:[A-Za-z0-9_-]+/)+\\z~D', $prefix)) {
                throw new \RuntimeException('COM_JLMS_DELIVERY_DISABLED', 503);
            }
            $handle = $this->open($asset);
            fclose($handle);
            return ['internal_uri' => $prefix . $key];
        }
        if (!(bool) $this->config()->get('material_php_delivery', false)) {
            throw new \RuntimeException('COM_JLMS_DELIVERY_DISABLED', 503);
        }
        return ['handle' => $this->open($asset)];
    }

    /** Remove only a private immutable blob addressed by its validated storage key. */
    public function remove(object $asset): bool
    {
        if (($asset->storage ?? '') !== 'local' || preg_match('/\A[a-f0-9]{64}\.blob\z/D', (string) ($asset->storage_key ?? '')) !== 1) {
            return false;
        }
        $path = $this->root() . DIRECTORY_SEPARATOR . $asset->storage_key;
        if (is_link($path)) { return false; }
        return !file_exists($path) || (is_file($path) && @unlink($path));
    }

    public function scan(object $asset): array
    {
        $config = $this->config();
        if (!(bool) $config->get('material_scan_required', false)) {
            if (($asset->status ?? '') === 'rejected') {
                return ['status' => 'rejected', 'scan_json' => $asset->scan_json];
            }
            return ['status' => 'ready', 'scan_json' => '{"engine":"none","result":"not_configured"}'];
        }
        $socket = (string) $config->get('material_clamd_socket', '');
        if ($socket === '' || $socket[0] !== '/' || str_contains($socket, "\0")) {
            return ['status' => 'quarantine', 'scan_json' => '{"engine":"clamd","result":"unavailable"}'];
        }
        $stream = @stream_socket_client('unix://' . $socket, $errno, $error, 2);
        if (!$stream) {
            return ['status' => 'quarantine', 'scan_json' => '{"engine":"clamd","result":"unavailable"}'];
        }
        $input = $this->open($asset);
        stream_set_timeout($stream, 3);
        $deadline = microtime(true) + 8;
        $write = static function ($handle, string $data) use ($deadline): void {
            while ($data !== '') {
                if (microtime(true) > $deadline) { throw new \RuntimeException('Scanner unavailable'); }
                $n = fwrite($handle, $data);
                if (!$n) { throw new \RuntimeException('Scanner unavailable'); }
                $data = substr($data, $n);
            }
        };
        try {
            $write($stream, "zINSTREAM\0");
            while (!feof($input)) {
                $chunk = fread($input, 65536);
                if ($chunk === false) { throw new \RuntimeException('Scanner unavailable'); }
                if ($chunk !== '') { $write($stream, pack('N', strlen($chunk)) . $chunk); }
            }
            $write($stream, pack('N', 0));
            $reply = stream_get_line($stream, 1024, "\0");
            $result = $reply === 'stream: OK' ? 'clean' : (is_string($reply) && str_ends_with($reply, ' FOUND') ? 'infected' : 'unavailable');
        } catch (\Throwable $error) {
            $result = 'unavailable';
        } finally {
            fclose($input); fclose($stream);
        }
        return ['status' => $result === 'clean' ? 'ready' : ($result === 'infected' ? 'rejected' : 'quarantine'),
            'scan_json' => json_encode(['engine' => 'clamd', 'result' => $result], JSON_THROW_ON_ERROR)];
    }
}
