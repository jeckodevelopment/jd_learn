<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Log\Log;

/** Creates one immutable certificate issuance record for a persisted course completion. */
final class CertificateIssuer
{
    public function __construct(private readonly DatabaseInterface $db) {}

    public function issueForCompletedEnrollment(int $enrollmentId, int $courseVersionId, string $completedAt): bool
    {
        $q = $this->db->createQuery()->select('e.id, e.user_id, e.course_id, e.course_version_id, e.completed_at, u.name, u.email, u.params AS user_params, c.title, c.params_json, c.language, v.title_snapshot, v.rules_json')
            ->from('#__jlms_enrollments AS e')->innerJoin('#__users AS u ON u.id = e.user_id')
            ->innerJoin('#__jlms_courses AS c ON c.id = e.course_id')->innerJoin('#__jlms_course_versions AS v ON v.id = e.course_version_id')
            ->where('e.id = :enrollment')->where('e.course_version_id = :version')->where('e.completed_at = :completed')
            ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)->bind(':version', $courseVersionId, ParameterType::INTEGER)
            ->bind(':completed', $completedAt);
        $row = $this->db->setQuery($q)->loadObject();
        if (!$row) { return false; }
        $rules = json_decode((string) $row->rules_json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($rules) || ($rules['mode'] ?? 'learning') !== 'learning'
            || ($rules['certificate_enabled'] ?? false) !== true) { return false; }
        $params = ComponentHelper::getParams('com_jlms');
        if (!CertificatePreferences::enabled((bool) $params->get('certificate_enabled', 0), (string) $row->params_json)) {
            return false;
        }

        $existing = $this->db->createQuery()->select('id')->from('#__jlms_certificates')
            ->where('enrollment_id = :enrollment')->where('course_version_id = :version')
            ->bind(':enrollment', $enrollmentId, ParameterType::INTEGER)->bind(':version', $courseVersionId, ParameterType::INTEGER);
        if ((int) $this->db->setQuery($existing)->loadResult() > 0) { return false; }

        $template = $this->db->createQuery()->select('id, template_json, hash')->from('#__jlms_certificate_templates')
            ->where('state = 1')->order('id ASC');
        $templateRow = $this->db->setQuery($template)->loadObject();
        if (!$templateRow) { return false; }

        $templateSnapshot = json_decode((string) $templateRow->template_json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($templateSnapshot)) { return false; }
        foreach (['certificate_heading' => 'heading', 'certificate_subheading' => 'subheading', 'certificate_footer' => 'footer'] as $setting => $key) {
            $value = trim((string) $params->get($setting, ''));
            if ($value !== '') { $templateSnapshot[$key] = mb_substr($value, 0, 255); }
        }
        $accent = (string) $params->get('certificate_accent_color', '#315A70');
        $templateSnapshot['accent_color'] = preg_match('/\\A#?[a-f0-9]{6}\\z/iD', $accent) ? $accent : '#315A70';
        $templateSnapshot['logo_path'] = (string) $params->get('certificate_logo', '');
        $templateSnapshotJson = json_encode($templateSnapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $certificate = (object) ['enrollment_id' => $enrollmentId, 'course_version_id' => $courseVersionId,
            'template_id' => (int) $templateRow->id, 'current_document_id' => null, 'issued_at' => $completedAt, 'revision' => 1];
        $this->db->insertObject('#__jlms_certificates', $certificate, 'id');
        $certificateId = (int) $certificate->id;
        $serial = strtoupper(substr(hash('sha256', 'jdlearn-certificate:' . $certificateId . ':' . bin2hex(random_bytes(16))), 0, 32));
        $document = (object) ['certificate_id' => $certificateId, 'document_no' => 1, 'replaces_document_id' => null,
            'serial' => $serial, 'verification_hash' => null, 'verify_enabled' => 0,
            'identity_snapshot_json' => json_encode(['user_id' => (int) $row->user_id, 'name' => (string) $row->name,
                'course_id' => (int) $row->course_id, 'course_title' => (string) ($row->title_snapshot ?: $row->title),
                'course_version_id' => $courseVersionId, 'completed_at' => $completedAt, 'language' => (string) $row->language], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'template_snapshot_json' => $templateSnapshotJson, 'status' => 'pending_render', 'pdf_asset_id' => null,
            'issued_at' => $completedAt, 'revoked_at' => null, 'revocation_reason' => null, 'revision' => 1];
        $this->db->insertObject('#__jlms_certificate_documents', $document, 'id');
        $link = (object) ['id' => $certificateId, 'current_document_id' => (int) $document->id];
        $this->db->updateObject('#__jlms_certificates', $link, 'id');

        // Joomla keeps the frontend language in the user profile parameters.
        $userSettings = json_decode((string) ($row->user_params ?? '{}'), true);
        $userLanguage = is_array($userSettings) ? (string) ($userSettings['language'] ?? $row->language) : (string) $row->language;

        // Certificate issuance and course completion are authoritative even if the mail
        // outbox cannot be written. The worker sends later through Joomla's MailTemplate.
        try {
            (new \Jlms\Component\Jlms\Administrator\Infrastructure\MailQueue($this->db))->enqueue(
                (int) $row->course_id,
                hash('sha256', 'certificate.available:' . $certificateId . ':' . (int) $document->id),
                'com_jlms.certificate.available',
                $userLanguage,
                (string) $row->email,
                (string) $row->name,
                ['NAME' => (string) $row->name, 'COURSE' => (string) ($row->title_snapshot ?: $row->title),
                    'SITENAME' => (string) \Joomla\CMS\Factory::getConfig()->get('sitename', ''),
                    'CERTIFICATE_URL' => Uri::root() . 'index.php?option=com_jlms&view=dashboard']
            );
        } catch (\Throwable $error) {
            Log::add('Could not enqueue a certificate availability email.', Log::WARNING, 'com_jlms');
        }
        return true;
    }
}
