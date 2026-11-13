<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Jlms\Component\Jlms\Administrator\Infrastructure\CertificatePdf;
use Jlms\Component\Jlms\Administrator\Infrastructure\MaterialFilePolicy;
use Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage;

/** Own-certificate listing and protected, on-demand PDF generation/delivery. */
final class CertificateService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly ProtectedMaterialStorage $storage) {}

    /** @return list<array{enrollment_id:int,title:string,serial:string,issued_at:string,status:string}> */
    public function listForStudent(User $identity): array
    {
        $userId = (int) $identity->id;
        if ($userId < 1) { throw new \RuntimeException('COM_JLMS_LOGIN_REQUIRED', 401); }
        $query = $this->db->createQuery()->select('e.id AS enrollment_id, d.serial, d.issued_at, d.status, d.identity_snapshot_json')
            ->from('#__jlms_certificates AS c')->innerJoin('#__jlms_enrollments AS e ON e.id = c.enrollment_id')
            ->innerJoin('#__jlms_certificate_documents AS d ON d.id = c.current_document_id AND d.certificate_id = c.id')
            ->where('e.user_id = :user')->where('e.completed_at IS NOT NULL')->where('d.revoked_at IS NULL')
            ->bind(':user', $userId, ParameterType::INTEGER)->order('d.issued_at DESC, d.id DESC');
        $items = [];
        foreach ($this->db->setQuery($query)->loadObjectList() as $row) {
            $snapshot = json_decode((string) $row->identity_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($snapshot)) { continue; }
            $items[] = ['enrollment_id' => (int) $row->enrollment_id,
                'title' => (string) ($snapshot['course_title'] ?? ''), 'serial' => (string) $row->serial,
                'issued_at' => (string) $row->issued_at, 'status' => (string) $row->status];
        }
        return $items;
    }

    /** @return array{handle:resource,size:int,mime:string,name:string,etag:string} */
    public function download(User $identity, int $enrollmentId): array
    {
        $userId = (int) $identity->id;
        if ($userId < 1) { throw new \RuntimeException('COM_JLMS_LOGIN_REQUIRED', 401); }
        if ($enrollmentId < 1) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $record = $this->document($userId, $enrollmentId);
        if (!$record) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        if ($record->status === 'pending_render') {
            $this->renderPending($record, $userId, $enrollmentId);
            $record = $this->document($userId, $enrollmentId);
        }
        if (!$record || $record->status !== 'ready' || (int) $record->asset_id < 1) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503);
        }
        $query = $this->db->createQuery()->select('a.*')->from('#__jlms_assets AS a')
            ->where('a.id = :asset')->where('a.status = :ready')->where('a.mime_type = :mime')
            ->bind(':asset', (int) $record->asset_id, ParameterType::INTEGER)->bind(':ready', 'ready')->bind(':mime', 'application/pdf');
        $asset = $this->db->setQuery($query)->loadObject();
        if (!$asset) { throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503); }
        $handle = $this->storage->open($asset);
        return ['handle' => $handle, 'size' => (int) $asset->byte_size, 'mime' => 'application/pdf',
            'name' => 'certificate-' . (string) $record->serial . '.pdf', 'etag' => '"' . (string) $asset->sha256 . '"'];
    }

    private function document(int $userId, int $enrollmentId): ?object
    {
        $query = $this->db->createQuery()->select('d.id AS document_id, d.certificate_id, d.serial, d.status, d.revision, d.pdf_asset_id AS asset_id,
                d.identity_snapshot_json, d.template_snapshot_json, e.user_id, e.id AS enrollment_id')
            ->from('#__jlms_certificates AS c')->innerJoin('#__jlms_enrollments AS e ON e.id = c.enrollment_id')
            ->innerJoin('#__jlms_certificate_documents AS d ON d.id = c.current_document_id AND d.certificate_id = c.id')
            ->where('e.id = :enrollment')->where('e.user_id = :user')->where('e.completed_at IS NOT NULL')->where('d.revoked_at IS NULL')
            ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)->bind(':user', $userId, ParameterType::INTEGER);
        return $this->db->setQuery($query)->loadObject() ?: null;
    }

    private function renderPending(object $record, int $userId, int $enrollmentId): void
    {
        $identity = json_decode((string) $record->identity_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
        $template = json_decode((string) $record->template_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($identity) || !is_array($template)) { throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503); }
        $identity['serial'] = (string) $record->serial;
        $logoFile = $this->logoFile((string) ($template['logo_path'] ?? ''));
        $pdf = CertificatePdf::render($identity, $template, $logoFile);
        $temporary = tempnam(sys_get_temp_dir(), 'jlms-cert-');
        if ($temporary === false || file_put_contents($temporary, $pdf, LOCK_EX) !== strlen($pdf)) {
            if (is_string($temporary)) { @unlink($temporary); }
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        try { $staged = $this->storage->stage($temporary, 'certificate.pdf'); }
        finally { @unlink($temporary); }

        $started = false;
        $committed = false;
        try {
            $this->db->transactionStart(); $started = true;
            $locked = $this->db->createQuery()->select('d.id, d.status, c.id AS certificate_id')
                ->from('#__jlms_certificate_documents AS d')->innerJoin('#__jlms_certificates AS c ON c.id = d.certificate_id AND c.current_document_id = d.id')
                ->innerJoin('#__jlms_enrollments AS e ON e.id = c.enrollment_id')->where('d.id = :document')
                ->where('e.id = :enrollment')->where('e.user_id = :user')->where('e.completed_at IS NOT NULL')
                ->bind(':document', (int) $record->document_id, ParameterType::INTEGER)->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':user', $userId, ParameterType::INTEGER);
            $current = $this->db->setQuery((string) $locked . ' FOR UPDATE')->loadObject();
            if (!$current) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
            if ($current->status !== 'pending_render') {
                $this->db->transactionCommit(); $started = false;
                $this->storage->remove((object) $staged);
                $committed = true;
                return;
            }
            $now = gmdate('Y-m-d H:i:s');
            $asset = (object) ($staged + ['asset_revision' => 1, 'status' => 'ready', 'scan_json' => '{"engine":"generated","result":"trusted"}',
                'created_by' => $userId, 'modified_by' => $userId, 'created_at' => $now, 'modified_at' => $now]);
            $this->db->insertObject('#__jlms_assets', $asset, 'id');
            $update = (object) ['id' => (int) $record->document_id, 'status' => 'ready', 'pdf_asset_id' => (int) $asset->id,
                'revision' => (int) $record->revision + 1];
            $this->db->updateObject('#__jlms_certificate_documents', $update, 'id');
            $this->db->transactionCommit(); $started = false; $committed = true;
        } catch (\Throwable $error) {
            if ($started) { $this->db->transactionRollback(); }
            if (!$committed) { $this->storage->remove((object) $staged); }
            throw $error;
        }
    }

    private function logoFile(string $value): ?string
    {
        if ($value === '') { return null; }
        try { $path = CourseArtwork::path($value); CourseArtwork::validateLocal($path); }
        catch (\Throwable $error) { throw new \RuntimeException('COM_JLMS_CERTIFICATE_RENDER_FAILED', 503); }
        if (!preg_match('/\.jpe?g\z/iD', $path)) { throw new \RuntimeException('COM_JLMS_CERTIFICATE_RENDER_FAILED', 503); }
        return JPATH_ROOT . '/' . $path;
    }
}
