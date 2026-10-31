<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Field;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
final class QuestionrevisionField extends ListField
{
    protected $type = 'Questionrevision';
    protected function getOptions()
    {
        $app=Factory::getApplication();$options=parent::getOptions();
        foreach($app->bootComponent('com_jlms')->getQuestionBankService()->revisionOptions($app->getIdentity(),(int)$this->value)as$row){
            $options[]=HTMLHelper::_('select.option',(string)$row['id'],$row['label']);
        }
        return $options;
    }
}
