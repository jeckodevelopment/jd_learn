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

    /** @return array{items:list<array<string,mixed>>,total:int,limit:int,offset:int,filters:array<string,string>} */
    public function adminList(User $identity, array $filters = []): array
    {
        $this->assertCertificateAdministrator($identity);
        $search = trim((string) ($filters['q'] ?? ''));
        $search = function_exists('mb_substr') ? mb_substr($search, 0, 120) : substr($search, 0, 120);
        $status = (string) ($filters['status'] ?? '');
        if (!in_array($status, ['', 'pending_render', 'ready', 'revoked'], true)) { $status = ''; }
        $limit = 50;
        $offset = max(0, min(50000, (int) ($filters['offset'] ?? 0)));
        $where = $this->db->createQuery()->from('#__jlms_certificates AS c')
            ->innerJoin('#__jlms_enrollments AS e ON e.id = c.enrollment_id')
            ->innerJoin('#__jlms_certificate_documents AS d ON d.id = c.current_document_id AND d.certificate_id = c.id')
            ->innerJoin('#__jlms_course_versions AS v ON v.id = c.course_version_id');
        if ($status === 'revoked') { $where->where('d.revoked_at IS NOT NULL'); }
        elseif ($status !== '') { $where->where('d.revoked_at IS NULL')->where('d.status = :status')->bind(':status', $status); }
        if ($search !== '') {
            $needle = '%' . $search . '%';
            $where->where("(d.serial LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(d.identity_snapshot_json, '$.name')) LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(d.identity_snapshot_json, '$.course_title')) LIKE :search)")
                ->bind(':search', $needle);
        }
        $countQuery = clone $where;
        $countQuery->clear('select')->select('COUNT(*)');
        $total = (int) $this->db->setQuery($countQuery)->loadResult();
        $query = clone $where;
        $query->clear('select')->select('c.id AS certificate_id, d.id AS document_id, d.serial, d.status, d.issued_at, d.revoked_at, d.identity_snapshot_json')
            ->order('d.issued_at DESC, d.id DESC');
        $rows = $this->db->setQuery($query, $offset, $limit)->loadObjectList();
        $items = [];
        foreach ($rows as $row) {
            $snapshot = json_decode((string) $row->identity_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
            $items[] = ['id' => (int) $row->certificate_id, 'document_id' => (int) $row->document_id,
                'serial' => (string) $row->serial, 'status' => $row->revoked_at ? 'revoked' : (string) $row->status,
                'issued_at' => (string) $row->issued_at, 'student_name' => (string) ($snapshot['name'] ?? ''),
                'course_title' => (string) ($snapshot['course_title'] ?? '')];
        }
        $this->recordCertificateAudit(null, null, (int) $identity->id, 'admin_list', ['result_count' => count($items), 'status' => $status]);
        return ['items' => $items, 'total' => $total, 'limit' => $limit, 'offset' => $offset,
            'filters' => ['q' => $search, 'status' => $status]];
    }

    /** @return array<string,mixed> */
    public function adminDetail(User $identity, int $certificateId): array
    {
        $this->assertCertificateAdministrator($identity);
        if ($certificateId < 1) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
        $record = $this->adminDocument($certificateId);
        if (!$record) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        $identitySnapshot = json_decode((string) $record->identity_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
        $templateSnapshot = json_decode((string) $record->template_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($identitySnapshot) || !is_array($templateSnapshot)) { throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503); }
        $this->recordCertificateAudit((int) $record->certificate_id, (int) $record->document_id, (int) $identity->id, 'admin_view');
        $auditQuery = $this->db->createQuery()->select('action, actor_user_id, created_at')
            ->from('#__jlms_certificate_audit')->where('certificate_id = :certificate')
            ->bind(':certificate', (int) $record->certificate_id, ParameterType::INTEGER)->order('id DESC');
        $audit = $this->db->setQuery($auditQuery, 0, 50)->loadObjectList();
        return ['id' => (int) $record->certificate_id, 'document_id' => (int) $record->document_id,
            'serial' => (string) $record->serial, 'status' => $record->revoked_at ? 'revoked' : (string) $record->status,
            'issued_at' => (string) $record->issued_at, 'revoked_at' => (string) ($record->revoked_at ?? ''),
            'revocation_reason' => (string) ($record->revocation_reason ?? ''), 'student_name' => (string) ($identitySnapshot['name'] ?? ''),
            'course_title' => (string) ($identitySnapshot['course_title'] ?? ''), 'completed_at' => (string) ($identitySnapshot['completed_at'] ?? ''),
            'course_version_id' => (int) ($identitySnapshot['course_version_id'] ?? 0), 'template' => $templateSnapshot,
            'audit' => $audit];
    }

    /** @return array{handle:resource,size:int,mime:string,name:string,etag:string} */
    public function adminDownload(User $identity, int $certificateId): array
    {
        $this->assertCertificateAdministrator($identity);
        $record = $this->adminDocument($certificateId);
        if (!$record) { throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404); }
        if ($record->status === 'pending_render' && !$record->revoked_at) {
            $this->renderPending($record, (int) $record->user_id, (int) $record->enrollment_id);
            $record = $this->adminDocument($certificateId);
        }
        if (!$record || $record->revoked_at || $record->status !== 'ready' || (int) $record->asset_id < 1) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503);
        }
        $query = $this->db->createQuery()->select('a.*')->from('#__jlms_assets AS a')->where('a.id = :asset')
            ->where('a.status = :ready')->where('a.mime_type = :mime')
            ->bind(':asset', (int) $record->asset_id, ParameterType::INTEGER)->bind(':ready', 'ready')->bind(':mime', 'application/pdf');
        $asset = $this->db->setQuery($query)->loadObject();
        if (!$asset) { throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503); }
        $handle = $this->storage->open($asset);
        $this->recordCertificateAudit((int) $record->certificate_id, (int) $record->document_id, (int) $identity->id, 'admin_download');
        return ['handle' => $handle, 'size' => (int) $asset->byte_size, 'mime' => 'application/pdf',
            'name' => 'certificate-' . (string) $record->serial . '.pdf', 'etag' => '"' . (string) $asset->sha256 . '"'];
    }

    private function assertCertificateAdministrator(User $identity): void
    {
        if ((int) $identity->id < 1 || !$identity->authorise('core.manage', 'com_jlms')
            || !$identity->authorise('jlms.certificates.manage', 'com_jlms')) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }
    }

    private function adminDocument(int $certificateId): ?object
    {
        $query = $this->db->createQuery()->select('c.id AS certificate_id, c.enrollment_id, d.id AS document_id, d.serial, d.status, d.issued_at, d.revoked_at, d.revocation_reason, d.revision, d.pdf_asset_id AS asset_id, d.identity_snapshot_json, d.template_snapshot_json, e.user_id')
            ->from('#__jlms_certificates AS c')->innerJoin('#__jlms_enrollments AS e ON e.id = c.enrollment_id')
            ->innerJoin('#__jlms_certificate_documents AS d ON d.id = c.current_document_id AND d.certificate_id = c.id')
            ->where('c.id = :certificate')->bind(':certificate', $certificateId, ParameterType::INTEGER);
        return $this->db->setQuery($query)->loadObject() ?: null;
    }

    private function recordCertificateAudit(?int $certificateId, ?int $documentId, int $actorId, string $action, array $context = []): void
    {
        $row = (object) ['certificate_id' => $certificateId, 'document_id' => $documentId, 'actor_user_id' => $actorId,
            'action' => $action, 'context_json' => json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'created_at' => gmdate('Y-m-d H:i:s')];
        try { $this->db->insertObject('#__jlms_certificate_audit', $row, 'id'); }
        catch (\Throwable $error) { throw new \RuntimeException('COM_JLMS_CERTIFICATE_AUDIT_FAILED', 503, $error); }
    }

}
