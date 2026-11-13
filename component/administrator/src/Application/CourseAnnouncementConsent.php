<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\CMS\Mail\MailTemplate;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Stores a single, double-opt-in preference for optional new-course notices. */
final class CourseAnnouncementConsent
{
    public const POLICY_VERSION = '2026-11-13';

    public function __construct(private readonly DatabaseInterface $db)
    {
    }

    public function state(int $userId): string
    {
        if ($userId < 1) { return 'none'; }
        $query = $this->db->createQuery()->select(['state', 'token_expires_at'])->from('#__jlms_mail_consents')
            ->where('user_id = :user')->bind(':user', $userId, ParameterType::INTEGER);
        $record = $this->db->setQuery($query)->loadObject();
        if (!$record) { return 'none'; }
        if ($record->state === 'pending' && (!$record->token_expires_at || strtotime((string) $record->token_expires_at . ' UTC') < time())) {
            return 'none';
        }
        return (string) $record->state;
    }

    /** Request consent; only a confirmed state can ever authorize course notices. */
    public function request(int $userId, string $email, string $name, string $origin): void
    {
        if ($userId < 1 || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || !in_array($origin, ['dashboard', 'enrollment'], true)) {
            throw new \InvalidArgumentException('COM_JLMS_MAIL_CONSENT_INVALID');
        }
        if ($this->state($userId) === 'confirmed') { return; }

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hash = hash('sha256', $token);
        $now = gmdate('Y-m-d H:i:s');
        $this->db->transactionStart();
        try {
            $remove = $this->db->createQuery()->delete('#__jlms_mail_consents')->where('user_id = :user')
                ->bind(':user', $userId, ParameterType::INTEGER);
            $this->db->setQuery($remove)->execute();
            $query = $this->db->createQuery()->insert('#__jlms_mail_consents')
                ->columns(['user_id', 'state', 'origin', 'policy_version', 'requested_at', 'confirmed_at', 'revoked_at', 'token_hash', 'token_expires_at'])
                ->values(implode(',', [
                    ':user', $this->db->quote('pending'), ':origin', $this->db->quote(self::POLICY_VERSION),
                    $this->db->quote($now), 'NULL', 'NULL', ':hash', 'DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR)',
                ]))
                ->bind(':user', $userId, ParameterType::INTEGER)->bind(':origin', $origin)->bind(':hash', $hash);
            $this->db->setQuery($query)->execute();
            $this->db->transactionCommit();
        } catch (\Throwable $error) {
            $this->db->transactionRollback();
            throw $error;
        }

        $url = \Joomla\CMS\Router\Route::_('index.php?option=com_jlms&task=mailconsent.confirm&token=' . rawurlencode($token), false, 0, true);
        $mail = new MailTemplate('com_jlms.course.consent', '*');
        $mail->addRecipient($email, $name);
        $mail->addTemplateData(['NAME' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'), 'CONFIRM_URL' => $url, 'SITE_NAME' => htmlspecialchars(\Joomla\CMS\Factory::getApplication()->get('sitename'), ENT_QUOTES, 'UTF-8')]);
        $mail->addUnsafeTags(['NAME', 'CONFIRM_URL', 'SITE_NAME']);
        if (!$mail->send()) { throw new \RuntimeException('COM_JLMS_MAIL_CONSENT_DELIVERY_FAILED'); }
    }

    public function confirm(string $token): bool
    {
        if (!preg_match('/^[A-Za-z0-9_-]{40,50}$/', $token)) { return false; }
        $q = $this->db->createQuery()->update('#__jlms_mail_consents')->set("state = 'confirmed'")
            ->set('confirmed_at = UTC_TIMESTAMP()')->set('token_hash = NULL')->set('token_expires_at = NULL')
            ->where("state = 'pending'")->where('token_hash = :hash')->where('token_expires_at >= UTC_TIMESTAMP()')
            ->bind(':hash', hash('sha256', $token));
        $this->db->setQuery($q)->execute();
        return $this->db->getAffectedRows() === 1;
    }

    public function revoke(int $userId): void
    {
        if ($userId < 1) { throw new \InvalidArgumentException('COM_JLMS_LOGIN_REQUIRED'); }
        $q = $this->db->createQuery()->update('#__jlms_mail_consents')->set("state = 'revoked'")
            ->set('revoked_at = UTC_TIMESTAMP()')->set('token_hash = NULL')->set('token_expires_at = NULL')
            ->where('user_id = :user')->bind(':user', $userId, ParameterType::INTEGER);
        $this->db->setQuery($q)->execute();
    }
}
