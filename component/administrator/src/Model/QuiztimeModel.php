<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Model;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\FormModel;
final class QuiztimeModel extends FormModel
{
    public function getForm($data=[],$loadData=true){return $this->loadForm('com_jlms.quiztime','quiztime',['control'=>'jform','load_data'=>false]);}
    public function getItem():array
    {
        $app=Factory::getApplication();$i=$app->getInput();
        return $app->bootComponent('com_jlms')->getQuizTimeService()->editor($app->getIdentity(),$i->getInt('id',0),$i->getInt('lesson_id',0),$i->getInt('enrollment_id',0),$i->getInt('after',0));
    }
}
