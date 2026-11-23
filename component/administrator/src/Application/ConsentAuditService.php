<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Read-only, privilege-gated projection of current MAIL-11 consent.
 * This is not a replacement for Joomla's site-policy consent registry.
 */
final class ConsentAuditService
{
    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function page(User $identity, int $userId = 0, int $start = 0, int $limit = 25): array
    {
        $actor = CourseEditorAccess::fresh($identity);
        if (
            !$actor->authorise('core.manage', 'com_jlms')
            || !$actor->authorise('jlms.audit.view', 'com_jlms')
        ) {
            throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
        }

        if ($userId < 0 || $start < 0) {
            throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
        }
        $limit = max(1, min(50, $limit));
        $count = $this->db->createQuery()->select('COUNT(*)')->from('#__jlms_mail_consents');
        $query = $this->db->createQuery()
            ->select(['user_id', 'state', 'origin', 'policy_version', 'requested_at', 'confirmed_at', 'revoked_at'])
            ->from('#__jlms_mail_consents');
        if ($userId > 0) {
            $count->where('user_id = :userId')->bind(':userId', $userId, ParameterType::INTEGER);
            $query->where('user_id = :userId')->bind(':userId', $userId, ParameterType::INTEGER);
        }
        $total = (int) $this->db->setQuery($count)->loadResult();
        $records = $this->db->setQuery($query->order('requested_at DESC, user_id DESC'), $start, $limit)
            ->loadObjectList();

        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'user_id' => (int) $record->user_id,
                'state' => in_array($record->state, ['pending', 'confirmed', 'revoked'], true) ? $record
                    ->state : 'unknown',
                'origin' => in_array($record->origin, [
                    'dashboard', 'registration', 'enrollment'], true) ? $record->origin : 'unknown',
                'policy_version' => (string) $record->policy_version,
                'requested_at' => (string) $record->requested_at,
                'confirmed_at' => $record->confirmed_at === null ? null : (string) $record->confirmed_at,
                'revoked_at' => $record->revoked_at === null ? null : (string) $record->revoked_at,
            ];
        }

        return ['total' => $total, 'rows' => $rows, 'start' => $start, 'limit' => $limit, 'user_id' => $userId];
    }
}
