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
        if ($this->item['lesson']->kind === 'video') {
            $labels = [];
            foreach (['consent', 'ready', 'error', 'offline', 'denied', 'conflict', 'completed', 'duration'] as $key) {
                $labels[$key] = \Joomla\CMS\Language\Text::_(implode('_', ['COM', 'JLMS', 'VIDEO', strtoupper($key)]));
            }
            $urls = [];
            foreach (['playback', 'save', 'close'] as $task) {
                $urls[$task] = \Joomla\CMS\Router\Route::_('index.php?option=com_jlms&task=video.' . $task . '&format=json', false);
            }
            $this->getDocument()->addScriptOptions('com_jlms.video', ['enrollment' => $this->item['enrollment_id'],
                'lesson' => (int) $this->item['lesson']->id, 'provider' => $this->item['lesson']->provider_key,
                'policy' => \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('video_external_policy', 'blocked'),
                'token' => \Joomla\CMS\Session\Session::getFormToken(), 'urls' => $urls, 'labels' => $labels]);
            $this->getDocument()->getWebAssetManager()->useScript('com_jlms.video');
        }
        parent::display($tpl);
    }
}
