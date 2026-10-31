<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;

final class QuizModel extends ItemModel
{
    public function getItem($pk=null)
    {
        $app=Factory::getApplication();
        try { return $app->bootComponent('com_jlms')->getQuizAttemptService()->state($app->getIdentity(),$app->getLanguage()->getTag(),
            (int)($pk??$app->getInput()->getInt('id',0)),$app->getInput()->getInt('lesson_id',0),$app->getInput()->getInt('attempt_id',0));
        } catch (\RuntimeException $error) {
            if (in_array($error->getMessage(), ['COM_JLMS_LOGIN_REQUIRED','COM_JLMS_LEARNING_DENIED','COM_JLMS_LEARNING_WINDOW','COM_JLMS_RESOURCE_NOT_FOUND','COM_JLMS_QUIZ_INVALID'], true)) {
                throw new \RuntimeException(\Joomla\CMS\Language\Text::_($error->getMessage()), $error->getCode());
            }
            \Joomla\CMS\Log\Log::add('Quiz view failed; correlation='.bin2hex(random_bytes(8)), \Joomla\CMS\Log\Log::ERROR, 'com_jlms');
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
