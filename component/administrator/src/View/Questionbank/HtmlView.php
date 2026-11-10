<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\View\Questionbank;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $bank;
    public $filterForm;
    public $state;
    public array $activeFilters=[];
    public array $questionCategories=[];

    public function display($tpl = null)
    {
        $actor=Factory::getApplication()->getIdentity();
        if (!$actor->id||$actor->block||!$actor->authorise('core.manage','com_jlms')||!$actor->authorise('jlms.quiz.manage','com_jlms')) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'),403); }
        $this->bank=$this->get('Bank');$this->filterForm=$this->get('FilterForm');$this->state=$this->get('State');
        $db=Factory::getContainer()->get(DatabaseInterface::class);
        $this->questionCategories=$db->createQuery()->select('id,title')->from('#__categories')->where('extension='.$db->quote('com_jlms.question'))->where('published=1')->order('lft ASC')->setLimit(500)->execute()->fetchAll(PDO::FETCH_OBJ);
        foreach (['search','published','catid','language'] as $key) { $value=$this->state->get('filter.'.$key);if ($value!==null&&$value!=='') { $this->activeFilters[$key]=$value; } }
        ToolbarHelper::title(Text::_('COM_JLMS_QUESTION_BANK'),'list');
        ToolbarHelper::link('index.php?option=com_jlms&view=question','JTOOLBAR_NEW','new');
        ToolbarHelper::link('index.php?option=com_categories&view=categories&extension=com_jlms.question','JCATEGORIES','folder');
        parent::display($tpl);
    }
}
