<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Log\Log;
use Jlms\Component\Jlms\Administrator\Infrastructure\CertificatePdf;
use Jlms\Component\Jlms\Administrator\Infrastructure\MaterialFilePolicy;
use Jlms\Component\Jlms\Administrator\Infrastructure\ProtectedMaterialStorage;

/** Own-certificate listing and protected, on-demand PDF generation/delivery. */
final class CertificateService
{
    public function __construct(private readonly DatabaseInterface $db, private readonly ProtectedMaterialStorage $storage)
    {
    }

    /** @return list<array{enrollment_id:int,title:string,serial:string,issued_at:string,status:string}> */
    public function listForStudent(User $identity): array
    {
        $userId = $this->assertCurrentStudent($identity);
        $query = $this->db->createQuery()->select(('e.id AS enrollment_id, d.serial, d.issued_at, d.status, '
            . 'd.identity_snapshot_json'))
            ->from('#__jlms_certificates AS c')->innerJoin('#__jlms_enrollments AS e ON e.id = c.enrollment_id')
            ->innerJoin(('#__jlms_certificate_documents AS d ON d.id = c.current_document_id '
                . 'AND d.certificate_id = c.id'))
            ->where('e.user_id = :user')->where('e.completed_at IS NOT NULL')->where('d.revoked_at IS NULL')
            ->bind(':user', $userId, ParameterType::INTEGER)->order('d.issued_at DESC, d.id DESC');
        $items = [];
        foreach ($this->db->setQuery($query)->loadObjectList() as $row) {
            $snapshot = json_decode((string) $row->identity_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($snapshot)) {
                continue;
            }
            $items[] = ['enrollment_id' => (int) $row->enrollment_id,
                'title' => (string) ($snapshot['course_title'] ?? ''), 'serial' => (string) $row->serial,
                'issued_at' => (string) $row->issued_at, 'status' => (string) $row->status];
        }
        return $items;
    }

    /**
     * Read the current Joomla account row for every certificate read/download.
     * The session User object can predate an administrator's block action.
     */
    private function assertCurrentStudent(User $identity): int
    {
        $userId = (int) $identity->id;
        if ($userId < 1 || $identity->guest) {
            throw new \RuntimeException('COM_JLMS_LOGIN_REQUIRED', 401);
        }
        $query = $this->db->createQuery()->select('u.id, u.block')
            ->from('#__users AS u')
            ->where('u.id = :certificateStudent')
            ->bind(':certificateStudent', $userId, ParameterType::INTEGER);
        $current = $this->db->setQuery($query)->loadObject();
        if (!$current || (int) $current->block === 1) {
            throw new \RuntimeException('COM_JLMS_LEARNING_DENIED', 403);
        }
        return $userId;
    }

    /**
     * Read-only, bounded administrative diagnosis of course completions that
     * have no certificate issuance. Never creates a certificate or changes
     * the frozen publication rules associated with the enrollment.
     *
     * @return list<array{enrollment_id:int,student_name:string,course_title:string,completed_at:string,reason:string}>
     */
    public function adminUnissuedCompletions(User $identity, int $limit = 25): array
    {
        $this->assertCertificateAdministrator($identity);
        $limit = max(1, min(50, $limit));
        $query = $this->db->createQuery()
            ->select('e.id AS enrollment_id, e.completed_at, u.name AS student_name, c.title AS course_title,
                c.params_json, v.rules_json, cert.id AS certificate_id, cert.current_document_id')
            ->from('#__jlms_enrollments AS e')
            ->innerJoin('#__users AS u ON u.id = e.user_id')
            ->innerJoin('#__jlms_courses AS c ON c.id = e.course_id')
            ->innerJoin('#__jlms_course_versions AS v ON v.id = e.course_version_id')
            ->leftJoin(('#__jlms_certificates AS cert ON cert.enrollment_id = e.id AND '
                . 'cert.course_version_id = e.course_version_id'))
            ->where('e.completed_at IS NOT NULL')
            ->where('(cert.id IS NULL OR cert.current_document_id IS NULL)')
            ->order('e.completed_at DESC, e.id DESC');
        $records = $this->db->setQuery($query, 0, $limit)->loadObjectList();
        if (!$records) {
            return [];
        }
        $activeTemplate = $this->db->createQuery()->select('id')->from('#__jlms_certificate_templates')
            ->where('state = 1')->order('id ASC');
        $templateExists = (int) $this->db->setQuery($activeTemplate, 0, 1)->loadResult() > 0;
        $globalEnabled = (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')
            ->get('certificate_enabled', 0);
        $result = [];
        foreach ($records as $record) {
            $reason = 'eligible_missing';
            try {
                $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $record->rules_json);
                if (($rules['mode'] ?? 'learning') !== 'learning' || ($rules['certificate_enabled'] ?? false) !== true) {
                    $reason = 'version_disabled';
                } elseif (!CertificatePreferences::enabled($globalEnabled, (string) $record->params_json)) {
                    $reason = 'preferences_disabled';
                } elseif (!$templateExists) {
                    $reason = 'template_missing';
                } elseif ($record->certificate_id !== null && $record->current_document_id === null) {
                    $reason = 'document_missing';
                }
            } catch (\Throwable) {
                $reason = 'invalid_configuration';
            }
            $result[] = [
                'enrollment_id' => (int) $record->enrollment_id,
                'student_name' => (string) $record->student_name,
                'course_title' => (string) $record->course_title,
                'completed_at' => (string) $record->completed_at,
                'reason' => $reason,
            ];
        }
        return $result;
    }

    /**
     * Explicit, administrator-approved issuance for completed enrollments tied to
     * a historical published version with certificate_enabled=false.
     * Never rewrites the frozen rules; preserves the original completion date.
     */
    public function adminRecoverHistorical(User $identity, int $enrollmentId, string $reason): void
    {
        $this->assertCertificateAdministrator($identity);
        $reason = trim($reason);
        if ($enrollmentId < 1 || mb_strlen($reason) < 8 || mb_strlen($reason) > 500) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 422);
        }
        $this->db->transactionStart();
        try {
            $query = $this->db->createQuery()
                ->select('e.id, e.course_version_id, e.completed_at, c.params_json, v.rules_json, v.status')
                ->from('#__jlms_enrollments AS e')
                ->innerJoin('#__jlms_courses AS c ON c.id = e.course_id')
                ->innerJoin('#__jlms_course_versions AS v ON v.id = e.course_version_id')
                ->where('e.id = :enrollment')->bind(':enrollment', $enrollmentId, ParameterType::INTEGER);
            $query->setQuery((string) $query . ' FOR UPDATE');
            $row = $this->db->setQuery($query)->loadObject();
            if (!$row || $row->completed_at === null || $row->status !== 'published') {
                throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
            }
            $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $row->rules_json);
            if ($rules['mode'] !== 'learning' || $rules['certificate_enabled'] !== false) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 409);
            }
            $globalEnabled = (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')
                ->get('certificate_enabled', 0);
            if (!CertificatePreferences::enabled($globalEnabled, (string) $row->params_json)) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 409);
            }
            $courseVersionId = (int) $row->course_version_id;
            $existing = $this->db->createQuery()->select('id')->from('#__jlms_certificates')
                ->where('enrollment_id = :enrollment')->where('course_version_id = :version')
                ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':version', $courseVersionId, ParameterType::INTEGER);
            if ((int) $this->db->setQuery($existing)->loadResult() > 0) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 409);
            }
            $issued = (new CertificateIssuer($this->db))->issueForCompletedEnrollment(
                $enrollmentId,
                (int) $row->course_version_id,
                (string) $row->completed_at,
                true
            );
            if (!$issued) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 409);
            }
            $certificate = $this->db->createQuery()->select('id, current_document_id')
                ->from('#__jlms_certificates')->where('enrollment_id = :enrollment')
                ->where('course_version_id = :version')
                ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':version', $courseVersionId, ParameterType::INTEGER);
            $result = $this->db->setQuery($certificate)->loadObject();
            if (!$result || (int) $result->current_document_id < 1) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503);
            }
            $this->recordCertificateAudit(
                (int) $result->id,
                (int) $result->current_document_id,
                (int) $identity->id,
                'historical_recovery',
                ['enrollment_id' => $enrollmentId, 'course_version_id' => (int) $row->course_version_id,
                'reason' => $reason,
                'historical_rules_preserved' => true]
            );
            $this->db->transactionCommit();
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        }
    }

    /**
     * Explicit manual recovery for a completed enrollment whose published
     * version already requires a certificate, but no issuance was recorded.
     * Never rewrites the frozen rules; preserves the original completion date.
     */
    public function adminRecoverMissing(User $identity, int $enrollmentId, string $reason): void
    {
        $this->assertCertificateAdministrator($identity);
        $reason = trim($reason);
        if ($enrollmentId < 1 || mb_strlen($reason) < 8 || mb_strlen($reason) > 500) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 422);
        }
        $this->db->transactionStart();
        try {
            $query = $this->db->createQuery()
                ->select('e.id, e.course_version_id, e.completed_at, c.params_json, v.rules_json, v.status')
                ->from('#__jlms_enrollments AS e')
                ->innerJoin('#__jlms_courses AS c ON c.id = e.course_id')
                ->innerJoin('#__jlms_course_versions AS v ON v.id = e.course_version_id')
                ->where('e.id = :enrollment')->bind(':enrollment', $enrollmentId, ParameterType::INTEGER);
            $query->setQuery((string) $query . ' FOR UPDATE');
            $row = $this->db->setQuery($query)->loadObject();
            if (!$row || $row->completed_at === null || $row->status !== 'published') {
                throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
            }
            $rules = \Jlms\Component\Jlms\Administrator\Domain\TeachingRules::decode((string) $row->rules_json);
            if ($rules['mode'] !== 'learning' || $rules['certificate_enabled'] !== true) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 409);
            }
            $globalEnabled = (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')
                ->get('certificate_enabled', 0);
            if (!CertificatePreferences::enabled($globalEnabled, (string) $row->params_json)) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 409);
            }
            $courseVersionId = (int) $row->course_version_id;
            $existing = $this->db->createQuery()->select('id')->from('#__jlms_certificates')
                ->where('enrollment_id = :enrollment')->where('course_version_id = :version')
                ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':version', $courseVersionId, ParameterType::INTEGER);
            if ((int) $this->db->setQuery($existing)->loadResult() > 0) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 409);
            }
            $issued = (new CertificateIssuer($this->db))->issueForCompletedEnrollment(
                $enrollmentId,
                (int) $row->course_version_id,
                (string) $row->completed_at,
                false
            );
            if (!$issued) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 409);
            }
            $certificate = $this->db->createQuery()->select('id, current_document_id')
                ->from('#__jlms_certificates')->where('enrollment_id = :enrollment')
                ->where('course_version_id = :version')
                ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':version', $courseVersionId, ParameterType::INTEGER);
            $result = $this->db->setQuery($certificate)->loadObject();
            if (!$result || (int) $result->current_document_id < 1) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503);
            }
            $this->recordCertificateAudit(
                (int) $result->id,
                (int) $result->current_document_id,
                (int) $identity->id,
                'missing_issuance_recovery',
                ['enrollment_id' => $enrollmentId, 'course_version_id' => (int) $row->course_version_id,
                'reason' => $reason,
                'published_rules_respected' => true]
            );
            $this->db->transactionCommit();
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        }
    }

    /** @return array{handle:resource,size:int,mime:string,name:string,etag:string} */
    public function download(User $identity, int $enrollmentId): array
    {
        $userId = $this->assertCurrentStudent($identity);
        if ($enrollmentId < 1) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        $record = $this->document($userId, $enrollmentId);
        if (!$record) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        if ($record->status === 'pending_render') {
            $this->renderPending($record, $userId, $enrollmentId);
            $record = $this->document($userId, $enrollmentId);
        }
        if (!$record || $record->status !== 'ready' || (int) $record->asset_id < 1) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503);
        }
        $assetId = (int) $record->asset_id;
        $readyStatus = 'ready';
        $pdfMime = 'application/pdf';
        $query = $this->db->createQuery()->select('a.*')->from('#__jlms_assets AS a')
            ->where('a.id = :asset')->where('a.status = :ready')->where('a.mime_type = :mime')
            ->bind(':asset', $assetId, ParameterType::INTEGER)->bind(':ready', $readyStatus)->bind(':mime', $pdfMime);
        $asset = $this->db->setQuery($query)->loadObject();
        if (!$asset) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $handle = $this->storage->open($asset);
        return ['handle' => $handle, 'size' => (int) $asset->byte_size, 'mime' => 'application/pdf',
            'name' => 'certificate-' . (string) $record->serial . '.pdf', 'etag' => '"' . (string) $asset
                ->sha256 . '"'];
    }

    private function document(int $userId, int $enrollmentId): ?object
    {
        $query = $this->db->createQuery()
            ->select('d.id AS document_id, d.certificate_id, d.serial, d.status, d.revision, d.pdf_asset_id AS asset_id,
                d.identity_snapshot_json, d.template_snapshot_json, e.user_id, e.id AS enrollment_id')
            ->from('#__jlms_certificates AS c')->innerJoin('#__jlms_enrollments AS e ON e.id = c.enrollment_id')
            ->innerJoin(('#__jlms_certificate_documents AS d ON d.id = c.current_document_id '
                . 'AND d.certificate_id = c.id'))
            ->where('e.id = :enrollment')
                ->where('e.user_id = :user')
                ->where('e.completed_at IS NOT NULL')->where('d.revoked_at IS NULL')
            ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':user', $userId, ParameterType::INTEGER);
        return $this->db->setQuery($query)->loadObject() ?: null;
    }

    private function renderPending(object $record, int $userId, int $enrollmentId): void
    {
        $identity = json_decode((string) $record->identity_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
        $template = json_decode((string) $record->template_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($identity) || !is_array($template)) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503);
        }
        $identity['serial'] = (string) $record->serial;
        try {
            $logoFile = $this->logoFile((string) ($template['logo_path'] ?? ''));
        } catch (\Throwable $error) {
            $this->logRenderFailure('logo', $error);
            throw $error;
        }
        try {
            $pdf = CertificatePdf::render($identity, $template, $logoFile);
        } catch (\Throwable $error) {
            $this->logRenderFailure('pdf', $error);
            throw $error;
        }
        try {
            // Do not use sys_get_temp_dir(): some shared hosts disallow it,
            // and no unencrypted PDF should be staged under public_html.
            $staged = $this->storage->stageGeneratedPdf($pdf);
        } catch (\Throwable $error) {
            $this->logRenderFailure('storage', $error);
            throw $error;
        }

        $started = false;
        $committed = false;
        try {
            $this->db->transactionStart();
            $started = true;
            $documentId = (int) $record->document_id;
            $locked = $this->db->createQuery()->select('d.id, d.status, c.id AS certificate_id')
                ->from('#__jlms_certificate_documents AS d')->innerJoin(('#__jlms_certificates AS c ON c.id = '
                    . 'd.certificate_id AND c.current_document_id = d.id'))
                ->innerJoin('#__jlms_enrollments AS e ON e.id = c.enrollment_id')->where('d.id = :document')
                ->where('e.id = :enrollment')->where('e.user_id = :user')->where('e.completed_at IS NOT NULL')
                ->bind(':document', $documentId, ParameterType::INTEGER)
                    ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)
                ->bind(':user', $userId, ParameterType::INTEGER);
            // Keep the original bound placeholders when appending the row lock.
            // Passing a SQL string to the driver here discards the query bindings.
            $locked->setQuery((string) $locked . ' FOR UPDATE');
            $current = $this->db->setQuery($locked)->loadObject();
            if (!$current) {
                throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
            }
            if ($current->status !== 'pending_render') {
                $this->db->transactionCommit();
                $started = false;
                $this->storage->remove((object) $staged);
                $committed = true;
                return;
            }
            $now = gmdate('Y-m-d H:i:s');
            $asset = (object) ($staged + ['asset_revision' => 1, 'status' => 'ready', 'scan_json' =>
                 '{"engine":"generated","result":"trusted"}',
                'created_by' => $userId, 'modified_by' => $userId, 'created_at' => $now, 'modified_at' => $now]);
            $this->db->insertObject('#__jlms_assets', $asset, 'id');
            $update = (object) ['id' => (int) $record->document_id, 'status' => 'ready',
                 'pdf_asset_id' => (int) $asset->id,
                'revision' => (int) $record->revision + 1];
            $this->db->updateObject('#__jlms_certificate_documents', $update, 'id');
            $this->db->transactionCommit();
            $started = false;
            $committed = true;
        } catch (\Throwable $error) {
            if ($started) {
                $this->db->transactionRollback();
            }
            if (!$committed) {
                $this->storage->remove((object) $staged);
            }
            $this->logRenderFailure('database', $error);
            throw $error;
        }
    }

    /** Log only operational phase and exception type, never names, paths or PDF data. */
    private function logRenderFailure(string $phase, \Throwable $error): void
    {
        Log::add(
            'Certificate PDF generation failure phase=' . $phase
            . '; error_type=' . get_debug_type($error)
            . '; error_code=' . (int) $error->getCode(),
            Log::ERROR,
            'com_jlms'
        );
    }

    private function logoFile(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        try {
            $path = CourseArtwork::path($value);
            CourseArtwork::validateLocal($path);
        } catch (\Throwable $error) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_RENDER_FAILED', 503);
        }
        // Validated local raster image; the PDF renderer converts non-JPEG formats.
        if (!preg_match('/\.(?:jpe?g|png|gif|webp|avif)\z/iD', $path)) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_RENDER_FAILED', 503);
        }
        return JPATH_ROOT . '/' . $path;
    }

    /** @return array{items:list<array<string,mixed>>,total:int,limit:int,offset:int,filters:array<string,string>} */
    public function adminList(User $identity, array $filters = []): array
    {
        $this->assertCertificateAdministrator($identity);
        $search = trim((string) ($filters['q'] ?? ''));
        $search = function_exists('mb_substr') ? mb_substr($search, 0, 120) : substr($search, 0, 120);
        $status = (string) ($filters['status'] ?? '');
        if (!in_array($status, ['', 'pending_render', 'ready', 'revoked'], true)) {
            $status = '';
        }
        $limit = max(1, min(100, (int) ($filters['limit'] ?? 50)));
        $offset = max(0, min(50000, (int) ($filters['offset'] ?? 0)));
        // Only known columns/expressions may reach ORDER BY; searchtools
        // ordering is request-controlled and must never be concatenated raw.
        $allowedOrders = [
            'd.issued_at' => 'd.issued_at',
            'd.serial' => 'd.serial',
            'certificate_state' => "CASE WHEN d.revoked_at IS NOT NULL THEN 'revoked' ELSE d.status END",
            'student_name' => "JSON_UNQUOTE(JSON_EXTRACT(d.identity_snapshot_json, '$.name'))",
            'course_title' => "JSON_UNQUOTE(JSON_EXTRACT(d.identity_snapshot_json, '$.course_title'))",
            'c.id' => 'c.id',
        ];
        $order = (string) ($filters['ordering'] ?? 'd.issued_at');
        $direction = strtoupper((string) ($filters['direction'] ?? 'DESC'));
        if (!isset($allowedOrders[$order])) {
            $order = 'd.issued_at';
        }
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }
        $where = $this->db->createQuery()->from('#__jlms_certificates AS c')
            ->innerJoin('#__jlms_enrollments AS e ON e.id = c.enrollment_id')
            ->innerJoin(('#__jlms_certificate_documents AS d ON d.id = c.current_document_id '
                . 'AND d.certificate_id = c.id'))
            ->innerJoin('#__jlms_course_versions AS v ON v.id = c.course_version_id');
        if ($status === 'revoked') {
            $where->where('d.revoked_at IS NOT NULL');
        } elseif ($status !== '') {
            $where->where('d.revoked_at IS NULL')->where('d.status = :status')->bind(':status', $status);
        }
        if ($search !== '') {
            $needle = '%' . $search . '%';
            $where->where(("(d.serial LIKE :search OR "
                . "JSON_UNQUOTE(JSON_EXTRACT(d.identity_snapshot_json, '$.name')) "
                . "LIKE :search OR "
                . "JSON_UNQUOTE(JSON_EXTRACT(d.identity_snapshot_json, "
                . "'$.course_title')) LIKE :search)"))
                ->bind(':search', $needle);
        }
        $countQuery = clone $where;
        $countQuery->clear('select')->select('COUNT(*)');
        $total = (int) $this->db->setQuery($countQuery)->loadResult();
        $query = clone $where;
        $query->clear('select')->select(('c.id AS certificate_id, d.id AS document_id, d.serial, d.status, '
            . 'd.issued_at, d.revoked_at, d.identity_snapshot_json'))
            ->order($allowedOrders[$order] . ' ' . $direction)
            ->order('d.id DESC');
        $rows = $this->db->setQuery($query, $offset, $limit)->loadObjectList();
        $items = [];
        foreach ($rows as $row) {
            $snapshot = json_decode((string) $row->identity_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
            $items[] = ['id' => (int) $row->certificate_id, 'document_id' => (int) $row->document_id,
                'serial' => (string) $row->serial, 'status' => $row->revoked_at ? 'revoked' : (string) $row->status,
                'issued_at' => (string) $row->issued_at, 'student_name' => (string) ($snapshot['name'] ?? ''),
                'course_title' => (string) ($snapshot['course_title'] ?? '')];
        }
        $this->recordCertificateAudit(null, null, (int) $identity->id, 'admin_list', ['result_count' =>
             count($items), 'status' => $status]);
        return ['items' => $items, 'total' => $total, 'limit' => $limit, 'offset' => $offset,
            'filters' => ['q' => $search, 'status' => $status]];
    }

    /** @return array<string,mixed> */
    public function adminDetail(User $identity, int $certificateId): array
    {
        $this->assertCertificateAdministrator($identity);
        if ($certificateId < 1) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400);
        }
        $record = $this->adminDocument($certificateId);
        if (!$record) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        $identitySnapshot = json_decode((string) $record->identity_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
        $templateSnapshot = json_decode((string) $record->template_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($identitySnapshot) || !is_array($templateSnapshot)) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503);
        }
        $this->recordCertificateAudit((int) $record->certificate_id, (int) $record->document_id, (int) $identity
            ->id, 'admin_view');
        $certificateId = (int) $record->certificate_id;
        $auditQuery = $this->db->createQuery()->select('action, actor_user_id, created_at')
            ->from('#__jlms_certificate_audit')->where('certificate_id = :certificate')
            ->bind(':certificate', $certificateId, ParameterType::INTEGER)->order('id DESC');
        $audit = $this->db->setQuery($auditQuery, 0, 50)->loadObjectList();
        return ['id' => (int) $record->certificate_id, 'document_id' => (int) $record->document_id,
            'serial' => (string) $record->serial, 'status' => $record->revoked_at ? 'revoked' : (string) $record->status,
            'issued_at' => (string) $record->issued_at, 'revoked_at' => (string) ($record->revoked_at ?? ''),
            'revocation_reason' => (string) ($record->revocation_reason ?? ''), 'student_name' =>
                 (string) ($identitySnapshot['name'] ?? ''),
            'course_title' => (string) ($identitySnapshot['course_title'] ?? ''), 'completed_at' =>
                 (string) ($identitySnapshot['completed_at'] ?? ''),
            'course_version_id' => (int) ($identitySnapshot['course_version_id'] ?? 0), 'revision' =>
                 (int) $record->revision, 'template' => $templateSnapshot,
            'audit' => $audit];
    }

    /** @return array{handle:resource,size:int,mime:string,name:string,etag:string} */
    public function adminDownload(User $identity, int $certificateId): array
    {
        $this->assertCertificateAdministrator($identity);
        $record = $this->adminDocument($certificateId);
        if (!$record) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        if ($record->status === 'pending_render' && !$record->revoked_at) {
            $this->renderPending($record, (int) $record->user_id, (int) $record->enrollment_id);
            $record = $this->adminDocument($certificateId);
        }
        if (!$record || $record->revoked_at || $record->status !== 'ready' || (int) $record->asset_id < 1) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503);
        }
        $assetId = (int) $record->asset_id;
        $readyStatus = 'ready';
        $pdfMime = 'application/pdf';
        $query = $this->db->createQuery()->select('a.*')->from('#__jlms_assets AS a')->where('a.id = :asset')
            ->where('a.status = :ready')->where('a.mime_type = :mime')
            ->bind(':asset', $assetId, ParameterType::INTEGER)->bind(':ready', $readyStatus)->bind(':mime', $pdfMime);
        $asset = $this->db->setQuery($query)->loadObject();
        if (!$asset) {
            throw new \RuntimeException('COM_JLMS_STORAGE_UNAVAILABLE', 503);
        }
        $handle = $this->storage->open($asset);
        $this->recordCertificateAudit((int) $record->certificate_id, (int) $record->document_id, (int) $identity
            ->id, 'admin_download');
        return ['handle' => $handle, 'size' => (int) $asset->byte_size, 'mime' => 'application/pdf',
            'name' => 'certificate-' . (string) $record->serial . '.pdf', 'etag' => '"' . (string) $asset->sha256 . '"'];
    }

    /**
     * Replace only the PDF binary from the original immutable issuance snapshots.
     * The same certificate/document, code, completion date and template snapshot
     * remain authoritative; the previous asset is retained for audit/retention.
     */
    public function adminRegenerate(User $identity, int $certificateId, int $expectedRevision, string $reason): void
    {
        $this->assertCertificateAdministrator($identity);
        $reason = trim($reason);
        if ($certificateId < 1 || $expectedRevision < 1 || mb_strlen($reason) < 8 || mb_strlen($reason) > 500) {
            throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 422);
        }
        $record = $this->adminDocument($certificateId);
        if (!$record) {
            throw new \RuntimeException('COM_JLMS_RESOURCE_NOT_FOUND', 404);
        }
        if ($record->revoked_at !== null || $record->status !== 'ready' || (int) $record->asset_id < 1) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 409);
        }
        if ((int) $record->revision !== $expectedRevision) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_REVISION_CONFLICT', 409);
        }

        $snapshot = json_decode((string) $record->identity_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
        $template = json_decode((string) $record->template_snapshot_json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($snapshot) || !is_array($template)) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_UNAVAILABLE', 503);
        }
        $snapshot['serial'] = (string) $record->serial;
        $pdf = CertificatePdf::render($snapshot, $template, $this->logoFile((string) ($template['logo_path'] ?? '')));
        // Avoid sys_get_temp_dir(): it may be outside open_basedir on shared
        // hosting, and plaintext PDFs must not be staged below public_html.
        $staged = $this->storage->stageGeneratedPdf($pdf);

        $started = false;
        $committed = false;
        try {
            $this->db->transactionStart();
            $started = true;
            $lock = $this->db->createQuery()
                ->select('c.current_document_id, d.id, d.revision, d.status, d.revoked_at, d.pdf_asset_id,
                    d.serial, d.identity_snapshot_json, d.template_snapshot_json')
                ->from('#__jlms_certificates AS c')
                ->innerJoin(('#__jlms_certificate_documents AS d ON d.id = c.current_document_id '
                    . 'AND d.certificate_id = c.id'))
                ->where('c.id = :certificate')->bind(':certificate', $certificateId, ParameterType::INTEGER);
            $current = $this->db->setQuery((string) $lock . ' FOR UPDATE')->loadObject();
            if (
                !$current || (int) $current->id !== (int) $record->document_id
                || (int) $current->revision !== $expectedRevision
                || $current->revoked_at !== null || $current->status !== 'ready'
                || (int) $current->pdf_asset_id < 1
            ) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_REVISION_CONFLICT', 409);
            }
            // A concurrent edit/revocation must never change certified inputs
            // between render and the atomic asset replacement.
            if (
                (string) $current->serial !== (string) $record->serial
                || (string) $current->identity_snapshot_json !== (string) $record->identity_snapshot_json
                || (string) $current->template_snapshot_json !== (string) $record->template_snapshot_json
            ) {
                throw new \RuntimeException('COM_JLMS_CERTIFICATE_REVISION_CONFLICT', 409);
            }

            $now = gmdate('Y-m-d H:i:s');
            $asset = (object) ($staged + [
                'asset_revision' => 1, 'status' => 'ready',
                'scan_json' => '{"engine":"generated","result":"trusted"}',
                'created_by' => (int) $identity->id, 'modified_by' => (int) $identity->id,
                'created_at' => $now, 'modified_at' => $now,
            ]);
            $this->db->insertObject('#__jlms_assets', $asset, 'id');
            $newAssetId = (int) $asset->id;
            $oldAssetId = (int) $current->pdf_asset_id;
            $document = (object) ['id' => (int) $current->id, 'pdf_asset_id' => $newAssetId,
                'revision' => $expectedRevision + 1];
            $this->db->updateObject('#__jlms_certificate_documents', $document, 'id');
            // Same transaction: audit failure rolls back the new PDF pointer.
            $this->recordCertificateAudit(
                $certificateId,
                (int) $current->id,
                (int) $identity->id,
                'admin_regenerate',
                ['reason' => $reason, 'previous_asset_id' => $oldAssetId,
                    'new_asset_id' => $newAssetId, 'revision' => $expectedRevision + 1,
                'sha256' => (string) $staged['sha256']]
            );
            $this->db->transactionCommit();
            $started = false;
            $committed = true;
        } catch (\Throwable $error) {
            if ($started) {
                $this->db->transactionRollback();
            }
            if (!$committed) {
                $this->storage->remove((object) $staged);
            }
            throw $error;
        }
    }

    private function assertCertificateAdministrator(User $identity): void
    {
        // Administrator permissions and account state must be read again for
        // every certificate operation. The Joomla session identity can be stale
        // after a block, group change or explicit permission revocation.
        $userId = (int) $identity->id;
        if ($userId < 1 || $identity->guest) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }
        $actor = new User($userId);
        $actor->clearAccessRights();
        if (
            !$actor->id || $actor->block
            || !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.certificates.manage', 'com_jlms')
        ) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }
    }

    private function adminDocument(int $certificateId): ?object
    {
        $query = $this->db->createQuery()->select(('c.id AS certificate_id, c.enrollment_id, d.id AS document_id, '
            . 'd.serial, d.status, d.issued_at, d.revoked_at, '
            . 'd.revocation_reason, d.revision, d.pdf_asset_id AS asset_id, '
            . 'd.identity_snapshot_json, d.template_snapshot_json, e.user_id'))
            ->from('#__jlms_certificates AS c')->innerJoin('#__jlms_enrollments AS e ON e.id = c.enrollment_id')
            ->innerJoin(('#__jlms_certificate_documents AS d ON d.id = c.current_document_id '
                . 'AND d.certificate_id = c.id'))
            ->where('c.id = :certificate')->bind(':certificate', $certificateId, ParameterType::INTEGER);
        return $this->db->setQuery($query)->loadObject() ?: null;
    }

    private function recordCertificateAudit(?int $certificateId, ?int $documentId, int $actorId, string $action, array $context = []): void
    {
        $row = (object) ['certificate_id' => $certificateId, 'document_id' => $documentId, 'actor_user_id' => $actorId,
            'action' => $action, 'context_json' => json_encode($context, JSON_THROW_ON_ERROR |
                JSON_UNESCAPED_UNICODE), 'created_at' => gmdate('Y-m-d H:i:s')];
        try {
            $this->db->insertObject('#__jlms_certificate_audit', $row, 'id');
        } catch (\Throwable $error) {
            throw new \RuntimeException('COM_JLMS_CERTIFICATE_AUDIT_FAILED', 503, $error);
        }
    }
}
