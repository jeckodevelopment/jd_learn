<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Factory;
final class QuestionbankModel extends FormModel
{
    public function getForm($data = [], $loadData = true) { return $this->loadForm('com_jlms.question','question',['control'=>'jform','load_data'=>false]); }
    public function getBank(): array
    {
        $app=Factory::getApplication();return $app->bootComponent('com_jlms')->getQuestionBankService()->listing($app->getIdentity(),$app->getInput()->getInt('before',0),$app->getInput()->getInt('catid',0));
    }
    public function getItem(): array
    {
        $app=Factory::getApplication();$id=$app->getInput()->getInt('id',0);
        return $id ? $app->bootComponent('com_jlms')->getQuestionBankService()->editor($app->getIdentity(),$id)
            : ['id'=>0,'revision'=>1,'language'=>'*','state'=>0,'kind'=>'single','default_points'=>1,'options'=>[['label'=>'','correct'=>'0'],['label'=>'','correct'=>'0']]];
    }
}
