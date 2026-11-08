<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\FormModel;

final class QuestionModel extends FormModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_jlms.question','question',['control'=>'jform','load_data'=>false]);
    }

    public function getItem(): array
    {
        $app=Factory::getApplication();$id=$app->getInput()->getInt('id',0);
        if ($id) { return $app->bootComponent('com_jlms')->getQuestionBankService()->editor($app->getIdentity(),$id); }
        $actor=$app->getIdentity();
        if (!$actor->authorise('core.manage','com_jlms')||!$actor->authorise('jlms.quiz.manage','com_jlms')) { throw new \RuntimeException('JERROR_ALERTNOAUTHOR',403); }
        return ['id'=>0,'revision'=>1,'language'=>'*','state'=>0,'kind'=>'single','default_points'=>1,'options'=>[['label'=>'','correct'=>'0'],['label'=>'','correct'=>'0']]];
    }
}
