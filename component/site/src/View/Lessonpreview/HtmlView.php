<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Lessonpreview;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $item;

    public function display($tpl = null): void
    {
        $this->item = $this->get('Item');
        $this->getDocument()->setTitle(Text::sprintf('COM_JLMS_PUBLIC_LESSON_PREVIEW_TITLE', $this->item['lesson']->title));
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        Factory::getApplication()->setHeader('Cache-Control', 'private, no-store', true);
        parent::display($tpl);
    }
}
