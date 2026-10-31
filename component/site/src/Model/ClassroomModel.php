<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Language\Text;

final class ClassroomModel extends ItemModel
{
    public function getItem($pk = null)
    {
        $app = Factory::getApplication();
        try {
            return $app->bootComponent('com_jlms')->getLearningService()->classroom($app->getIdentity(),
                $app->getLanguage()->getTag(), (int) ($pk ?? $app->getInput()->getInt('id', 0)),
                $app->getInput()->getInt('lesson_id', 0));
        } catch (\RuntimeException $error) {
            $allowed = ['COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_LEARNING_WINDOW',
                'COM_JLMS_RESOURCE_NOT_FOUND', 'JERROR_LAYOUT_PAGE_NOT_FOUND'];
            if (in_array($error->getMessage(), $allowed, true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), $error->getCode());
            }
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
