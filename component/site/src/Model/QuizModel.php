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
        return $app->bootComponent('com_jlms')->getQuizAttemptService()->state($app->getIdentity(),$app->getLanguage()->getTag(),
            (int)($pk??$app->getInput()->getInt('id',0)),$app->getInput()->getInt('lesson_id',0),$app->getInput()->getInt('attempt_id',0));
    }
}
