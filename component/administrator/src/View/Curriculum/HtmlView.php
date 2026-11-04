<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\View\Curriculum;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $outline;
    public $moduleForm;
    public $lessonForm;
    public array $lessonsByModule = [];
    public bool $editable = false;
    public bool $canDuplicateCourse = false;
    public bool $canDelete = false;
    public array $transitions = [];
    public string $publicationNotice = '';
    public array $videoProviders = [];
    public bool $canConfigureVideo = false;
    public bool $canManagePlugins = false;

    public function display($tpl = null)
    {
        $this->outline = $this->get('Outline');
        $version = $this->outline['version'];
        $course = $this->outline['course'];
        foreach ($this->outline['lessons'] as $lesson) {
            $this->lessonsByModule[(int) $lesson->module_id][] = $lesson;
        }
        $app = Factory::getApplication();
        $actor = \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::fresh($app->getIdentity());
        $this->canDuplicateCourse = $version && \Jlms\Component\Jlms\Administrator\Application\CourseEditorAccess::canCreate(Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class), $actor, (int) $course->catid);
        $this->editable = $version && $version->status === 'draft';
        $this->canDelete = $app->getIdentity()->authorise('core.delete', 'com_jlms.course.' . (int) $course->id);
        if (!$version) {
            $this->publicationNotice = 'COM_JLMS_PUBLICATION_NEEDS_VERSION';
        } elseif (!$app->getIdentity()->authorise('core.edit.state', 'com_jlms.course.' . (int) $course->id)) {
            $this->publicationNotice = 'COM_JLMS_PUBLICATION_PERMISSION_REQUIRED';
        } elseif (!\Joomla\CMS\Plugin\PluginHelper::isEnabled('workflow', 'publishing')) {
            $this->publicationNotice = 'COM_JLMS_PUBLICATION_PLUGIN_REQUIRED';
        } else {
            $this->transitions = $app->bootComponent('com_jlms')->getPublicationService()->transitions($app->getIdentity(), (int) $course->id, (int) $version->id);
            if (!$this->transitions) {
                $this->publicationNotice = 'COM_JLMS_PUBLICATION_NO_TRANSITIONS';
            }
        }
        if ($this->editable) {
            $actor = $app->getIdentity();
            $this->canConfigureVideo = $actor->authorise('core.options', 'com_jlms') || $actor->authorise('core.admin', 'com_jlms');
            $this->canManagePlugins = $actor->authorise('core.manage', 'com_plugins');
            $policy = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('video_external_policy', 'blocked');
            $registered = [];
            foreach ($app->bootComponent('com_jlms')->getCurriculumService()->providers() as $provider) {
                $registered[$provider->key] = $provider->configured;
            }
            foreach (['youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'cloudflarestream' => 'Cloudflare Stream', 'html5' => 'MP4'] as $key => $label) {
                if (!array_key_exists($key, $registered)) {
                    $status = 'COM_JLMS_VIDEO_EDITOR_PLUGIN_MISSING';
                } elseif (!$registered[$key]) {
                    $status = 'COM_JLMS_VIDEO_EDITOR_CONFIG_REQUIRED';
                } elseif ($key !== 'html5' && !in_array($policy, ['embed', 'site_consent'], true)) {
                    $status = 'COM_JLMS_VIDEO_EDITOR_POLICY_BLOCKED';
                } else {
                    $status = 'COM_JLMS_VIDEO_EDITOR_CONFIGURED';
                }
                $this->videoProviders[$label] = $status;
            }
            $this->moduleForm = $this->get('Form');
            $this->lessonForm = $this->get('LessonForm');
            $common = ['course_id' => $course->id, 'version_id' => $version->id,
                'expected_version_revision' => $version->revision, 'id' => 0, 'revision' => 1];
            $moduleData = $common;
            $lessonData = $common + ['kind' => 'text', 'required' => 1];
            $editModule = $app->getInput()->getInt('edit_module');
            $editLesson = $app->getInput()->getInt('edit_lesson');
            $foundModule = !$editModule;
            $foundLesson = !$editLesson;
            $field = new \SimpleXMLElement('<field name="module_id" type="list" label="COM_JLMS_MODULE" required="true" />');
            foreach ($this->outline['modules'] as $module) {
                $field->addChild('option', htmlspecialchars($module->title, ENT_XML1))->addAttribute('value', (string) $module->id);
                if ((int) $module->id === $editModule) {
                    $moduleData = array_replace($common, ['id' => $module->id, 'revision' => $module->revision, 'title' => $module->title]);
                    $foundModule = true;
                }
            }
            $this->lessonForm->setField($field);
            foreach ($this->outline['lessons'] as $lesson) {
                if ((int) $lesson->id === $editLesson) {
                    $lesson = $app->bootComponent('com_jlms')->getCurriculumService()->lessonForEdit(
                        $app->getIdentity(), (int) $course->id, (int) $version->id, $editLesson,
                    );
                    $source = $lesson->source_json ? json_decode($lesson->source_json, true, 32, JSON_THROW_ON_ERROR) : [];
                    $lessonData = array_replace($common, ['id' => $lesson->id, 'revision' => $lesson->revision,
                        'title' => $lesson->title, 'module_id' => $lesson->module_id, 'body' => $lesson->body,
                        'kind' => $lesson->kind, 'required' => $lesson->required, 'provider_key' => $lesson->provider_key,
                        'source_reference' => $lesson->provider_key === 'html5' ? '' : ($source['reference'] ?? ''), 'privacy_hash' => $source['options']['privacy_hash'] ?? '',
                        'duration_seconds' => $lesson->duration_ms ? (string) ($lesson->duration_ms / 1000) : '',
                        'duration_confirmed' => (json_decode($lesson->params_json, true)['video_duration_confirmed'] ?? false) ? '1' : '0']);
                    $foundLesson = true;
                }
            }
            if (!$foundModule || !$foundLesson) {
                throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404);
            }
            $this->moduleForm->bind($moduleData);
            $this->lessonForm->bind($lessonData);
            // Two Joomla forms on one page must not emit duplicate jform_* element IDs.
            $this->moduleForm->setFieldAttribute('title', 'id', 'jlms_module_title');
            foreach (['id','revision','course_id','version_id','expected_version_revision'] as $name) {
                $this->moduleForm->setFieldAttribute($name, 'id', 'jlms_module_' . $name);
            }
        }
        ToolbarHelper::title(Text::_('COM_JLMS_CURRICULUM'), 'graduation-cap');
        parent::display($tpl);
    }
}
