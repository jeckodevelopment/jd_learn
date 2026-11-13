<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Read-only mail outbox audit with explicit, audited reconciliation of stale SMTP claims. */
final class MailQueueAuditService
{
    private const STATES = ['pending', 'retry', 'sending', 'sent', 'failed', 'skipped'];
    private const TEMPLATES = [
        'com_jlms.enrollment.requested',
        'com_jlms.enrollment.confirmed',
        'com_jlms.course.completed',
        'com_jlms.certificate.available',
        'com_jlms.enrollment.suspended',
        'com_jlms.enrollment.reactivated',
        'com_jlms.course.message',
        'com_jlms.course.published',
        'com_jlms.course.expiring',
        'com_jlms.course.consent',
    ];
    private const STALE_MINUTES = 15;

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function page(User $identity, array $filters = [], int $start = 0, int $limit = 50): array
    {
        $actor = CourseEditorAccess::fresh($identity);
        if (!$actor->authorise('core.manage', 'com_jlms')) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }

        $state = (string) ($filters['state'] ?? '');
        $template = (string) ($filters['template'] ?? '');
        if ($state !== '' && !in_array($state, self::STATES, true)) {
            throw new \InvalidArgumentException('COM_JLMS_MAIL_QUEUE_FILTER_INVALID');
        }
        if ($template !== '' && !in_array($template, self::TEMPLATES, true)) {
            throw new \InvalidArgumentException('COM_JLMS_MAIL_QUEUE_FILTER_INVALID');
        }

        $start = max(0, $start);
        $limit = max(10, min(100, $limit));
        [$where, $bind] = $this->filters($state, $template);
        $count = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_mail_queue AS q')->leftJoin('#__jlms_courses AS c ON c.id=q.course_id');
        foreach ($where as $condition) { $count->where($condition); }
        foreach ($bind as $name => [$value, $type]) { $count->bind($name, $value, $type); }
        $total = (int) $this->db->setQuery($count)->loadResult();

        $query = $this->db->createQuery()->select([
            'q.id', 'q.template_id', 'q.course_id', 'c.title AS course_title', 'q.recipient_email',
            'q.state', 'q.attempts', 'q.available_at', 'q.claimed_at', 'q.sent_at',
            'q.created_at', 'q.reconciliation_action', 'q.reconciliation_by', 'q.reconciliation_at',
            'q.reconciliation_note',
            '(CASE WHEN q.state = ' . $this->db->quote('sending') . ' AND q.claimed_at <= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL ' . self::STALE_MINUTES . ' MINUTE) THEN 1 ELSE 0 END) AS is_stale_sending',
        ])->from('#__jlms_mail_queue AS q')->leftJoin('#__jlms_courses AS c ON c.id=q.course_id');
        foreach ($where as $condition) { $query->where($condition); }
        foreach ($bind as $name => [$value, $type]) { $query->bind($name, $value, $type); }
        $rows = $this->db->setQuery($query->order('q.created_at DESC, q.id DESC'), $start, $limit)->loadObjectList();
        foreach ($rows as $row) {
            $row->recipient_masked = self::maskEmail((string) $row->recipient_email);
            unset($row->recipient_email);
        }

        return ['rows' => $rows, 'total' => $total, 'start' => $start, 'limit' => $limit,
            'filters' => ['state' => $state, 'template' => $template], 'stale_minutes' => self::STALE_MINUTES];
    }

    public function reconcile(User $identity, int $queueId, string $action, string $note, bool $confirmed): void
    {
        $actor = CourseEditorAccess::fresh($identity);
        if (!$actor->authorise('core.manage', 'com_jlms')) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }
        $note = trim($note);
        if ($queueId < 1 || !in_array($action, ['sent', 'retry'], true) || !$confirmed || mb_strlen($note) < 10 || mb_strlen($note) > 1000) {
            throw new \InvalidArgumentException('COM_JLMS_MAIL_QUEUE_RECONCILE_INVALID');
        }

        $q = $this->db->createQuery()->select('id,attempts')->from('#__jlms_mail_queue')
            ->where('id = :id')->where("state = 'sending'")
            ->where('claimed_at <= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL ' . self::STALE_MINUTES . ' MINUTE)')
            ->bind(':id', $queueId, ParameterType::INTEGER);
        $row = $this->db->setQuery($q)->loadObject();
        if (!$row) {
            throw new \RuntimeException('COM_JLMS_MAIL_QUEUE_NOT_RECONCILABLE', 409);
        }
        if ($action === 'retry' && (int) $row->attempts >= 5) {
            throw new \RuntimeException('COM_JLMS_MAIL_QUEUE_RECONCILE_RETRY_LIMIT', 409);
        }

        $query = $this->db->createQuery()->update('#__jlms_mail_queue')
            ->set('state = :state')->set('reconciliation_action = :action')
            ->set('reconciliation_by = :actor')->set('reconciliation_at = UTC_TIMESTAMP(6)')
            ->set('reconciliation_note = :note')->set('last_error = :lastError')->set('claimed_at = NULL')
            ->where('id = :id')->where("state = 'sending'")
            ->where('claimed_at <= DATE_SUB(UTC_TIMESTAMP(6), INTERVAL ' . self::STALE_MINUTES . ' MINUTE)')
            ->bind(':state', $action === 'sent' ? 'sent' : 'retry')
            ->bind(':action', $action)->bind(':actor', (int) $actor->id, ParameterType::INTEGER)
            ->bind(':note', $note)->bind(':lastError', 'Manual reconciliation: ' . $note)
            ->bind(':id', $queueId, ParameterType::INTEGER);

        if ($action === 'retry') {
            $query->where('attempts < 5');
        }

        if ($action === 'sent') {
            $query->set('sent_at = COALESCE(sent_at, UTC_TIMESTAMP(6))')
                ->set("recipient_email = ''")->set("recipient_name = ''")->set("template_data = '{}'");
        } else {
            $query->set('available_at = UTC_TIMESTAMP(6)');
        }

        $this->db->setQuery($query)->execute();
        if ($this->db->getAffectedRows() !== 1) {
            throw new \RuntimeException('COM_JLMS_MAIL_QUEUE_NOT_RECONCILABLE', 409);
        }
    }

    private function filters(string $state, string $template): array
    {
        $where = [];
        $bind = [];
        if ($state !== '') {
            $where[] = 'q.state = :state';
            $bind[':state'] = [$state, ParameterType::STRING];
        }
        if ($template !== '') {
            $where[] = 'q.template_id = :template';
            $bind[':template'] = [$template, ParameterType::STRING];
        }
        return [$where, $bind];
    }

    private static function maskEmail(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return ''; }
        [$local, $domain] = explode('@', $email, 2);
        return mb_substr($local, 0, 1) . str_repeat('*', min(8, max(3, mb_strlen($local) - 1))) . '@' . $domain;
    }
}
