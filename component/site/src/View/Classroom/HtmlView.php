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
        $this->materials = !empty($this->item['lesson']->prerequisite_blocked)
            ? ['course' => ['items' => [], 'page' => 0, 'total' => 0], 'lesson' => ['items' => [], 'page' => 0, 'total' => 0]]
            : $app->bootComponent('com_jlms')->getMaterialService()->learnerLists($app->getIdentity(),
                $app->getLanguage()->getTag(), $this->item['enrollment_id'], (int) $this->item['lesson']->id,
                $app->getInput()->getInt('course_material_page', 0), $app->getInput()->getInt('lesson_material_page', 0));
        $this->mutationKey = bin2hex(random_bytes(16));
        $this->getDocument()->setTitle($this->item['title']);
        $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        if (empty($this->item['lesson']->prerequisite_blocked) && $this->item['lesson']->kind === 'audio') {
            $this->getDocument()->addScriptOptions('com_jlms.audio', [
                'enrollment' => (int) $this->item['enrollment_id'],
                'lesson' => (int) $this->item['lesson']->id,
                'resumeMs' => (int) ($this->item['lesson']->resume_ms ?? 0),
                'token' => \Joomla\CMS\Session\Session::getFormToken(),
                'url' => \Joomla\CMS\Router\Route::_('index.php?option=com_jlms&task=audio.save&format=json', false),
                'labels' => ['saved' => \Joomla\CMS\Language\Text::_('COM_JLMS_AUDIO_RESUME_SAVED'),
                    'error' => \Joomla\CMS\Language\Text::_('COM_JLMS_AUDIO_RESUME_ERROR')],
            ]);
            $this->getDocument()->getWebAssetManager()->useScript('com_jlms.audio');
        }
        if (empty($this->item['lesson']->prerequisite_blocked) && $this->item['lesson']->kind === 'video') {
            $labels = [];
            foreach (['consent', 'ready', 'error', 'offline', 'denied', 'conflict', 'completed', 'duration', 'renewed', 'policy', 'loading', 'startup_request', 'startup_module', 'startup_provider', 'youtube_csp', 'youtube_sdk', 'youtube_referrer', 'youtube_embed'] as $key) {
                $labels[$key] = \Joomla\CMS\Language\Text::_(implode('_', ['COM', 'JLMS', 'VIDEO', strtoupper($key === 'consent' ? 'consent_required' : $key)]));
            }
            $urls = [];
            foreach (['playback', 'save', 'renew', 'close'] as $task) {
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
