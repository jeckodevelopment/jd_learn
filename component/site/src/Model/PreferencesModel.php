<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/** Personal preferences backed exclusively by the existing consent service. */
final class PreferencesModel extends BaseDatabaseModel
{
    public function getAnnouncementConsent(): string
    {
        $app = Factory::getApplication();
        $identity = $app->getIdentity();
        if (!$identity || $identity->guest || (int) $identity->id < 1) {
            throw new \RuntimeException(Text::_('COM_JLMS_LOGIN_REQUIRED'), 401);
        }
        return $app->bootComponent('com_jlms')->getCourseAnnouncementConsent()->state((int) $identity->id);
    }
}
