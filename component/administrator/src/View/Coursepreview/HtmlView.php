<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Coursepreview;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $preview;

    public function display($tpl = null)
    {
        Factory::getApplication()->setHeader('Cache-Control', 'private, no-store', true);
        $this->preview = $this->get('Preview');
        ToolbarHelper::title(Text::_('COM_JLMS_COURSE_PREVIEW'), 'eye');
        $lesson = $this->preview['selected'] ?? null;
        if ($lesson && $lesson->kind === 'video') {
            $app = Factory::getApplication();
            $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site')->useScript('com_jlms.video');
            $this->getDocument()->addScriptOptions('com_jlms.video', [
                'preview' => true, 'course' => (int) $this->preview['course']->id,
                'version' => (int) $this->preview['version']->id, 'lesson' => (int) $lesson->id,
                'provider' => $lesson->provider_key, 'policy' => \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('video_external_policy', 'blocked'),
                'token' => \Joomla\CMS\Session\Session::getFormToken(),
                'previewUrl' => \Joomla\CMS\Router\Route::_('index.php?option=com_jlms&task=coursepreview.playback&format=json', false),
                'labels' => ['ready' => Text::_('COM_JLMS_COURSE_PREVIEW_VIDEO_READY'), 'error' => Text::_('COM_JLMS_VIDEO_UNAVAILABLE'),
                    'offline' => Text::_('COM_JLMS_VIDEO_OFFLINE'), 'rate_unavailable' => Text::_('COM_JLMS_VIDEO_SPEED_UNAVAILABLE'),
                    'captions_off' => Text::_('COM_JLMS_VIDEO_CAPTIONS_OFF'), 'captions_unavailable' => Text::_('COM_JLMS_VIDEO_CAPTIONS_UNAVAILABLE'), 'denied' => Text::_('COM_JLMS_VIDEO_DENIED'),
                    'loading' => Text::_('COM_JLMS_VIDEO_LOADING'), 'startup_provider' => Text::_('COM_JLMS_VIDEO_UNAVAILABLE'),
                    'startup_request' => Text::_('COM_JLMS_VIDEO_UNAVAILABLE'), 'startup_module' => Text::_('COM_JLMS_VIDEO_UNAVAILABLE'),
                    'policy' => Text::_('COM_JLMS_VIDEO_POLICY_BLOCKED'), 'consent' => Text::_('COM_JLMS_VIDEO_CONSENT_REQUIRED')],
            ]);
        }

        return parent::display($tpl);
    }
}
