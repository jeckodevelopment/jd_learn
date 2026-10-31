<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Site\View\Quiz;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

final class HtmlView extends BaseHtmlView
{
    public array $item;
    public function display($tpl=null)
    {
        $this->item=$this->get('Item');$doc=$this->getDocument();
        $doc->setTitle($this->item['title']);$doc->setMetaData('robots','noindex, nofollow');
        $urls=[];foreach(['start','save','submit','state']as$task)$urls[$task]=Route::_('index.php?option=com_jlms&task=quiz.'.$task.'&format=json',false);
        $labels=[];foreach(['saved','saving','error','conflict','expired','confirm','answered']as$key)$labels[$key]=Text::_(implode('_',['COM','JLMS','QUIZ',strtoupper($key)]));
        foreach(['five_minutes','one_minute']as$key)$labels[$key]=Text::_(implode('_',['COM','JLMS','QUIZ','WARNING',strtoupper($key)]));
        $doc->addScriptOptions('com_jlms.quiz',['enrollment'=>$this->item['enrollment_id'],'lesson'=>$this->item['lesson_id'],
            'attempt'=>$this->item['attempt'],'server_now'=>$this->item['server_now'],'token'=>\Joomla\CMS\Session\Session::getFormToken(),'urls'=>$urls,'labels'=>$labels]);
        $doc->getWebAssetManager()->useStyle('com_jlms.site')->useScript('com_jlms.quiz');
        parent::display($tpl);
    }
}
