<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Classroom;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public array $item;
    public string $mutationKey;
    public array $materials;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $app = \Joomla\CMS\Factory::getApplication();
        $this->materials = $app->bootComponent('com_jlms')->getMaterialService()->learnerLists($app->getIdentity(),
            $app->getLanguage()->getTag(), $this->item['enrollment_id'], (int) $this->item['lesson']->id,
            $app->getInput()->getInt('course_material_page', 0), $app->getInput()->getInt('lesson_material_page', 0));
        $this->mutationKey = bin2hex(random_bytes(16));
        $this->getDocument()->setTitle($this->item['title']);
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
