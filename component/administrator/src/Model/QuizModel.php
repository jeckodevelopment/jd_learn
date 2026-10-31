<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;
final class QuizModel extends FormModel
{
    public function getForm($data = [], $loadData = true) { return $this->loadForm('com_jlms.quiz','quiz',['control'=>'jform','load_data'=>false]); }
    public function getItem(): array
    {
        $app=Factory::getApplication();return $app->bootComponent('com_jlms')->getQuizAuthoringService()->editor($app->getIdentity(),$app->getInput()->getInt('id',0),$app->getInput()->getInt('version_id',0),$app->getInput()->getInt('lesson_id',0));
    }
}
