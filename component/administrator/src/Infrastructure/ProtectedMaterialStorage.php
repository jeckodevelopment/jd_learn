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

    /** Shared-hosting default: a private directory managed by JD Learn. */
    private function managed(): bool
    {
        return trim((string) $this->config()->get('material_storage_root', '')) === '';
    }

    private function managedRoot(): string
    {
        $siteRoot = realpath(JPATH_ROOT);
        if ($siteRoot === false) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $path = $siteRoot;
        foreach (['media', 'com_jlms', 'private'] as $part) {
            $path .= DIRECTORY_SEPARATOR . $part;
            if (is_link($path)) {
                throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
            }
            if (!is_dir($path) && (!@mkdir($path, $part === 'private' ? 0700 : 0755) && !is_dir($path))) {
                throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
            }
            if (!is_dir($path) || realpath($path) !== $path) {
                throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
            }
        }
        if (!is_writable($path)) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }

        // Apache/LiteSpeed deny direct HTTP access; encrypted guarded PHP blobs
        // remain confidential even on servers ignoring .htaccess (e.g. Nginx).
        $this->guard($path . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        $this->guard($path . '/index.php', "<?php http_response_code(404); exit;\n");
        return $path;
    }

    private function guard(string $path, string $content): void
    {
        if (is_link($path)) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        if (!is_file($path)) {
            $handle = @fopen($path, 'xb');
            if ($handle !== false) {
                try {
                    if (@fwrite($handle, $content) !== strlen($content) || !@chmod($path, 0600)) {
                        throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
                    }
                } finally {
                    fclose($handle);
                }
            }
        }
        if (!is_file($path) || !hash_equals(hash('sha256', $content), hash_file('sha256', $path))) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
    }

    private function encryptionKey(): string
    {
        $siteSecret = (string) \Joomla\CMS\Factory::getConfig()->get('secret', '');
        if ($siteSecret === '' || !function_exists('openssl_encrypt')) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        return hash_hkdf('sha256', $siteSecret, 32, 'JD Learn managed asset v1');
    }

    /** Accept Unix roots and Windows drive roots; reject drive-relative paths. */
    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || (strlen($path) > 2 && ctype_alpha($path[0])
            && $path[1] === ':' && ($path[2] === '/' || $path[2] === chr(92)));
    }

    public function root(): string
    {
        if ($this->managed()) {
            return $this->managedRoot();
        }

        // Existing external private roots remain supported for advanced hosting
        // setups and pre-existing .blob assets; no implicit relocation.
        $configured = trim((string) $this->config()->get('material_storage_root', ''));
        $absolute = self::isAbsolutePath($configured);
        if (!$absolute) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $root = realpath($configured);
        if ($root === false || !is_dir($root) || !is_writable($root) || is_link($configured)) {
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
            $factor = match (strtolower(substr($value, -1))) {
                'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1
            };
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

    private const GUARDED_HEADER = "<?php http_response_code(404); exit; __halt_compiler(); ?>\n";

    /** Trusted local file source; HTTP callers must first verify is_uploaded_file(). */
    public function stage(string $source, string $name): array
    {
        $root = $this->root();
        if (is_link($source) || !is_file($source)) {
            throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
        }
        $metadata = MaterialFilePolicy::inspect($source, $name, $this->limit());
        $managed = $this->managed();
        $key = bin2hex(random_bytes(32)) . ($managed ? '.php' : '.blob');
        $path = $root . DIRECTORY_SEPARATOR . $key;
        $input = @fopen($source, 'rb');
        $output = @fopen($path, 'xb');
        if (!$input || !$output) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        try {
            if (!@chmod($path, 0600)) {
                throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
            }
            if ($managed) {
                // GCM authenticates the bytes and hides private content even if a
                // reverse proxy misserves a PHP file as a static download.
                $plain = stream_get_contents($input, $metadata['byte_size'] + 1);
                if ($plain === false || strlen($plain) !== $metadata['byte_size']) {
                    throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
                }
                $iv = random_bytes(12);
                $tag = '';
                $encrypted = openssl_encrypt($plain, 'aes-256-gcm', $this->encryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
                if (!is_string($encrypted) || strlen($tag) !== 16) {
                    throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
                }
                $payload = self::GUARDED_HEADER . $iv . $tag . $encrypted;
                if (fwrite($output, $payload) !== strlen($payload)) {
                    throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
                }
            } else {
                if (stream_copy_to_stream($input, $output, $metadata['byte_size'] + 1) !== $metadata['byte_size']) {
                    throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
                }
            }
        } catch (\Throwable $error) {
            fclose($input);
            fclose($output);
            @unlink($path);
            throw $error;
        }
        fclose($input);
        fclose($output);
        if (!$managed && !hash_equals($metadata['sha256'], hash_file('sha256', $path))) {
            @unlink($path);
            throw new \RuntimeException('COM_JLMS_FILE_INVALID', 422);
        }
        return $metadata + ['storage' => 'local', 'storage_key' => $key];
    }

    /**
     * Store a trusted internally generated PDF directly. No plaintext temporary
     * files are written: shared hosts can restrict sys_get_temp_dir() and the
     * managed path can sit below the public webroot.
     */
    public function stageGeneratedPdf(string $pdf): array
    {
        $size = strlen($pdf);
        if (
            $size < 32 || $size > $this->limit()
            || !str_starts_with($pdf, '%PDF-')
            || !str_contains(substr($pdf, -1024), '%%EOF')
            || (new \finfo(FILEINFO_MIME_TYPE))->buffer($pdf) !== 'application/pdf'
        ) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_RENDER_FAILED', 503);
        }

        $root = $this->root();
        $managed = $this->managed();
        $key = bin2hex(random_bytes(32)) . ($managed ? '.php' : '.blob');
        $path = $root . DIRECTORY_SEPARATOR . $key;
        $digest = hash('sha256', $pdf);
        $payload = $pdf;
        if ($managed) {
            $nonce = random_bytes(12);
            $tag = '';
            $encrypted = openssl_encrypt($pdf, 'aes-256-gcm', $this->encryptionKey(), OPENSSL_RAW_DATA, $nonce, $tag);
            if (!is_string($encrypted) || strlen($tag) !== 16) {
                throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
            }
            $payload = self::GUARDED_HEADER . $nonce . $tag . $encrypted;
        }
        $file = @fopen($path, 'xb');
        if ($file === false) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        try {
            if (!@chmod($path, 0600)) {
                throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
            }
            $written = 0;
            $length = strlen($payload);
            while ($written < $length) {
                $part = @fwrite($file, substr($payload, $written, 65536));
                if ($part === false || $part === 0) {
                    throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
                }
                $written += $part;
            }
        } catch (\Throwable $error) {
            fclose($file);
            @unlink($path);
            throw $error;
        }
        fclose($file);
        if (!hash_equals(hash('sha256', $payload), (string) @hash_file('sha256', $path))) {
            @unlink($path);
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        return ['mime_type' => 'application/pdf', 'byte_size' => $size, 'sha256' => $digest,
            'original_name' => 'certificate.pdf', 'storage' => 'local', 'storage_key' => $key];
    }

    public function open(object $asset)
    {
        if (
            ($asset->storage ?? '') !== 'local'
            || preg_match('/\\A[a-f0-9]{64}\\.(?:blob|php)\\z/D', (string) ($asset->storage_key ?? '')) !== 1
        ) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $managed = str_ends_with($asset->storage_key, '.php');
        if ($managed && !$this->managed()) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $path = $this->root() . DIRECTORY_SEPARATOR . $asset->storage_key;
        if (is_link($path) || !is_file($path)) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $handle = @fopen($path, 'rb');
        $stat = $handle ? fstat($handle) : false;
        if (!$stat || ($stat['mode'] & 0170000) !== 0100000) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        if ($managed) {
            // Never return the on-disk wrapper/ciphertext as the document.
            try {
                $prefix = fread($handle, strlen(self::GUARDED_HEADER));
                $iv = fread($handle, 12);
                $tag = fread($handle, 16);
                $encrypted = stream_get_contents($handle, MaterialFilePolicy::MAX_BYTES + 1);
                if (
                    $prefix !== self::GUARDED_HEADER || strlen((string) $iv) !== 12
                    || strlen((string) $tag) !== 16 || $encrypted === false
                    || strlen($encrypted) !== (int) $asset->byte_size
                ) {
                    throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
                }
                $plain = openssl_decrypt($encrypted, 'aes-256-gcm', $this->encryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
                if (
                    !is_string($plain) || strlen($plain) !== (int) $asset->byte_size
                    || !hash_equals((string) $asset->sha256, hash('sha256', $plain))
                ) {
                    throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
                }
                $decoded = fopen('php://temp/maxmemory:1048576', 'w+b');
                if ($decoded === false || fwrite($decoded, $plain) !== strlen($plain)) {
                    if (is_resource($decoded)) {
                        fclose($decoded);
                    }
                    throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
                }
                rewind($decoded);
                return $decoded;
            } finally {
                fclose($handle);
            }
        }
        if ($stat['size'] !== (int) $asset->byte_size) {
            fclose($handle);
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

    /** Remove only an immutable blob addressed by its validated random key. */
    public function remove(object $asset): bool
    {
        if (
            ($asset->storage ?? '') !== 'local'
            || preg_match('/\\A[a-f0-9]{64}\\.(?:blob|php)\\z/D', (string) ($asset->storage_key ?? '')) !== 1
        ) {
            return false;
        }
        if (str_ends_with($asset->storage_key, '.php') && !$this->managed()) {
            return false;
        }
        $path = $this->root() . DIRECTORY_SEPARATOR . $asset->storage_key;
        if (is_link($path)) {
            return false;
        }
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
                if (microtime(true) > $deadline) {
                    throw new \RuntimeException('Scanner unavailable');
                }
                $n = fwrite($handle, $data);
                if (!$n) {
                    throw new \RuntimeException('Scanner unavailable');
                }
                $data = substr($data, $n);
            }
        };
        try {
            $write($stream, "zINSTREAM\0");
            while (!feof($input)) {
                $chunk = fread($input, 65536);
                if ($chunk === false) {
                    throw new \RuntimeException('Scanner unavailable');
                }
                if ($chunk !== '') {
                    $write($stream, pack('N', strlen($chunk)) . $chunk);
                }
            }
            $write($stream, pack('N', 0));
            $reply = stream_get_line($stream, 1024, "\0");
            $result = $reply === 'stream: OK' ? 'clean' : (is_string($reply) && str_ends_with($reply, ' FOUND') ? 'infected' : 'unavailable');
        } catch (\Throwable $error) {
            $result = 'unavailable';
        } finally {
            fclose($input);
            fclose($stream);
        }
        return ['status' => $result === 'clean' ? 'ready' : ($result === 'infected' ? 'rejected' : 'quarantine'),
            'scan_json' => json_encode(['engine' => 'clamd', 'result' => $result], JSON_THROW_ON_ERROR)];
    }
}
