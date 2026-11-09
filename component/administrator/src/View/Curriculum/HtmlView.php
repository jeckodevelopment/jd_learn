<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\View\Curriculum;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Jlms\Component\Jlms\Administrator\Application\CourseSchedule;
use Jlms\Component\Jlms\Administrator\Domain\LessonAvailability;

final class HtmlView extends BaseHtmlView
{
    public array $outline;
    public $moduleForm;
    public $lessonForm;
    public $rulesForm;
    public ?array $teachingRules = null;
    public array $lessonsByModule = [];
    public bool $editable = false;
    public bool $canDuplicateCourse = false;
    public bool $canRestoreProgramme = false;
    public bool $canDelete = false;
    public array $transitions = [];
    public string $publicationNotice = '';
    public bool $inReview = false;
    public array $publicationReadiness = ['blockers' => [], 'warnings' => []];
    public array $videoProviders = [];
    public bool $canConfigureVideo = false;
    public bool $canManagePlugins = false;
    public bool $canManageFields = false;
    public bool $canMaterials = false;
    public bool $canQuiz = false;
    public string $editorMode = '';
    public string $itemAction = '';
    public ?object $selectedItem = null;
    public int $selectedModule = 0;


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
        $publication = $app->bootComponent('com_jlms')->getPublicationService();
        $this->inReview = $publication->isInReview((int) $course->id);
        $locked = ($course->checked_out && (int) $course->checked_out !== (int) $actor->id);
        $locked = $locked || ($version && $version->checked_out && (int) $version->checked_out !== (int) $actor->id);
        $this->editable = $version && $version->status === 'draft' && !$locked && !$this->inReview;
        $this->canRestoreProgramme = $version && !$locked && $course->current_version_id !== null
            && (int)$course->current_version_id !== (int)$version->id && in_array($version->status, ['published','retired'], true);
        if ($version) {
            try { $this->teachingRules = $app->bootComponent('com_jlms')->getCurriculumService()->teachingRules($actor, (int)$course->id, (int)$version->id); }
            catch (\DomainException $error) { if ($error->getMessage() !== 'COM_JLMS_PUBLICATION_UNSUPPORTED') { throw $error; } }
            if ($version->status === 'draft') {
                $this->publicationReadiness = \Jlms\Component\Jlms\Administrator\Domain\PublicationReadiness::inspect(
                    $course,
                    $this->outline['modules'],
                    $this->outline['lessons'],
                    $this->outline['attachments'],
                    (bool) \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('material_scan_required', false),
                );
            }
        }
        $this->canMaterials = $actor->authorise('jlms.materials.manage', 'com_jlms.course.' . (int) $course->id);
        $this->canQuiz = $actor->authorise('jlms.quiz.manage', 'com_jlms.course.' . (int) $course->id);
        $this->canManageFields = $actor->authorise('core.manage', 'com_fields');
        $this->getDocument()->getWebAssetManager()->getRegistry()->addExtensionRegistryFile('com_jlms');
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.curriculum');
        if ($this->editable) { $this->getDocument()->getWebAssetManager()->useScript('com_jlms.curriculum'); }
        $this->canDelete = $app->getIdentity()->authorise('core.delete', 'com_jlms.course.' . (int) $course->id);
        if (!$version) {
            $this->publicationNotice = 'COM_JLMS_PUBLICATION_NEEDS_VERSION';
        } elseif (!$app->getIdentity()->authorise('core.edit.state', 'com_jlms.course.' . (int) $course->id)) {
            $this->publicationNotice = 'COM_JLMS_PUBLICATION_PERMISSION_REQUIRED';
        } elseif (!\Joomla\CMS\Plugin\PluginHelper::isEnabled('workflow', 'publishing')) {
            $this->publicationNotice = 'COM_JLMS_PUBLICATION_PLUGIN_REQUIRED';
        } else {
            $this->transitions = $publication->transitions($app->getIdentity(), (int) $course->id, (int) $version->id);
            if (!$this->transitions) {
                $this->publicationNotice = 'COM_JLMS_PUBLICATION_NO_TRANSITIONS';
            }
        }
        if ($this->editable) {
            $actor = $app->getIdentity();
            $this->canConfigureVideo = $actor->authorise('core.options', 'com_jlms') || $actor->authorise('core.admin', 'com_jlms');
            $this->canManagePlugins = $actor->authorise('core.manage', 'com_plugins');
            $policy = \Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('video_external_policy', 'blocked');
            $providers = [];
            foreach ($app->bootComponent('com_jlms')->getCurriculumService()->providers() as $provider) {
                $providers[$provider->key] = $provider;
            }
            $coreProviders = ['youtube' => 'YouTube', 'vimeo' => 'Vimeo',
                'cloudflarestream' => 'Cloudflare Stream', 'html5' => 'MP4'];
            $rows = $coreProviders;
            foreach ($providers as $key => $provider) {
                if (!isset($coreProviders[$key])) {
                    $rows[$key] = \Joomla\CMS\Language\Text::_($provider->labelKey);
                }
            }
            foreach ($rows as $key => $fallbackLabel) {
                $provider = $providers[$key] ?? null;
                $label = $provider && !isset($coreProviders[$key])
                    ? \Joomla\CMS\Language\Text::_($provider->labelKey) : $fallbackLabel;
                if (!$provider) {
                    $status = 'COM_JLMS_VIDEO_EDITOR_PLUGIN_MISSING';
                } elseif (!$provider->configured) {
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
            $requestedKind = $app->getInput()->getCmd('lesson_kind');
            $lessonData = $common + ['kind' => in_array($requestedKind, ['video', 'document'], true) ? $requestedKind : 'text', 'required' => 1, 'required_lesson_ids' => [], 'publish_up' => '', 'publish_down' => '', 'release_after_days' => ''];
            $this->selectedModule = $app->getInput()->getInt('module_id', 0);
            $this->editorMode = $app->getInput()->getBool('new_module') || !$this->outline['modules'] ? 'module' : '';
            if ($this->outline['modules'] && $app->getInput()->getBool('new_lesson')) { $this->editorMode = 'lesson'; }
            if ($app->getInput()->getBool('edit_rules')) { $this->editorMode = 'rules'; }
            $editModule = $app->getInput()->getInt('edit_module');
            $editLesson = $app->getInput()->getInt('edit_lesson');
            if ($editModule) { $this->editorMode = 'module'; }
            if ($editLesson) { $this->editorMode = 'lesson'; }
            $action = $app->getInput()->getCmd('item_action', '');
            if ($action !== '' && !in_array($action, ['copy_module','copy_lesson','delete_module','delete_lesson','move_lesson','order_module','order_lesson'], true)) { throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404); }
            $this->itemAction = $action;
            if ($action !== '') { $this->editorMode = 'action'; }
            $actionId = $app->getInput()->getInt('item_id');
            $foundModule = !$editModule;
            $foundLesson = !$editLesson;
            $field = new \SimpleXMLElement('<field name="module_id" type="list" label="COM_JLMS_MODULE" required="true" />');
            foreach ($this->outline['modules'] as $module) {
                $field->addChild('option', htmlspecialchars($module->title, ENT_XML1))->addAttribute('value', (string) $module->id);
                if (!$this->selectedModule) { $this->selectedModule = (int) $module->id; }
                if ($actionId === (int) $module->id && str_ends_with($action, '_module')) { $this->selectedItem = $module; }
                if ((int) $module->id === $editModule) {
                    $moduleData = array_replace($common, ['id' => $module->id, 'revision' => $module->revision, 'title' => $module->title]);
                    $this->selectedItem = $module;
                    $foundModule = true;
                }
            }
            if ($this->selectedModule && !in_array($this->selectedModule, array_map(static fn($m): int => (int) $m->id, $this->outline['modules']), true)) { throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404); }
            $lessonData['module_id'] = $this->selectedModule;
            $prerequisiteField = new \SimpleXMLElement('<field name="required_lesson_ids" type="list" label="COM_JLMS_LESSON_PREREQUISITES" multiple="true" size="6" />');
            foreach ($this->outline['lessons'] as $candidate) {
                if ((int) $candidate->id === $editLesson) { continue; }
                $option = $prerequisiteField->addChild('option', htmlspecialchars($candidate->title, ENT_XML1));
                $option->addAttribute('value', (string) $candidate->id);
            }
            $this->lessonForm->setField($prerequisiteField);
            $this->lessonForm->setField($field);
            foreach ($this->outline['lessons'] as $lesson) {
                if ($actionId === (int) $lesson->id && str_ends_with($action, '_lesson')) { $this->selectedItem = $lesson; }
                if ((int) $lesson->id === $editLesson) {
                    $lesson = $app->bootComponent('com_jlms')->getCurriculumService()->lessonForEdit(
                        $app->getIdentity(), (int) $course->id, (int) $version->id, $editLesson,
                    );
                    $this->selectedItem = $lesson;
                    $this->selectedModule = (int) $lesson->module_id;
                    $customFields = [];
                    foreach (\Joomla\Component\Fields\Administrator\Helper\FieldsHelper::getFields('com_jlms.lesson', $lesson, true) as $customField) {
                        $customFields[$customField->name] = $customField->value;
                    }
                    $source = $lesson->source_json ? json_decode($lesson->source_json, true, 32, JSON_THROW_ON_ERROR) : [];
                    $lessonData = array_replace($common, ['id' => $lesson->id, 'revision' => $lesson->revision,
                        'title' => $lesson->title, 'module_id' => $lesson->module_id, 'body' => $lesson->body,
                        'kind' => $lesson->kind, 'required' => $lesson->required, 'provider_key' => $lesson->provider_key,
                        'source_reference' => $lesson->provider_key === 'html5' ? '' : ($source['reference'] ?? ''), 'privacy_hash' => $source['options']['privacy_hash'] ?? '',
                        'duration_mode'=>'auto', 'duration_seconds' => $lesson->duration_ms ? (string) ($lesson->duration_ms / 1000) : '',
                        'duration_confirmed' => (json_decode($lesson->params_json, true)['video_duration_confirmed'] ?? false) ? '1' : '0',
                        'required_lesson_ids' => $this->outline['prerequisites'][(int) $lesson->id] ?? [],
                        'publish_up' => LessonAvailability::editor($lesson->publish_up, CourseSchedule::read($course)['timezone']),
                        'publish_down' => LessonAvailability::editor($lesson->publish_down, CourseSchedule::read($course)['timezone']),
                        'release_after_days' => $lesson->release_after_days === null ? '' : (string) $lesson->release_after_days,
                        'com_fields' => $customFields]);
                    $foundLesson = true;
                }
            }
            if (!$foundModule || !$foundLesson) {
                throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404);
            }
            if ($action && !$this->selectedItem) { throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404); }
            if (str_starts_with($action, 'delete_') && !$this->canDelete) { throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403); }
            $stateKey = 'com_jlms.curriculum.' . (int) $course->id . '.' . (int) $version->id . '.' . $this->editorMode;
            if (in_array($this->editorMode, ['module','lesson'], true)) {
                $saved = $app->getUserState($stateKey, []);
                $app->setUserState($stateKey, null);
                $expectedId = $this->editorMode === 'module' ? (int) ($moduleData['id'] ?? 0) : (int) ($lessonData['id'] ?? 0);
                if (is_array($saved) && (int) ($saved['id'] ?? -1) === $expectedId) {
                    $saved = array_filter($saved, static fn($value): bool => is_string($value) || is_int($value)
                        || (is_array($value) && array_is_list($value) && count($value) <= 5000
                            && array_reduce($value, static fn(bool $valid, mixed $item): bool => $valid && (is_string($item) || is_int($item)), true)));
                    $safe = array_intersect_key($saved, array_flip(['title','body','provider_key','source_reference','privacy_hash','duration_mode','duration_seconds','duration_confirmed','required','kind','required_lesson_ids','publish_up','publish_down','release_after_days']));
                    if ($this->editorMode === 'module') { $moduleData = array_replace($moduleData, array_intersect_key($safe, ['title'=>true])); }
                    else { $lessonData = array_replace($lessonData, $safe); }
                }
            }
            if ($this->editorMode === 'rules' && $this->teachingRules !== null) {
                $this->rulesForm = $this->get('RulesForm');
                $data = $common + ['sequence_mode'=>$this->teachingRules['sequence_mode'], 'video_threshold'=>\Jlms\Component\Jlms\Administrator\Domain\TeachingRules::percent($this->teachingRules['video_threshold_basis_points'])];
                $key = 'com_jlms.curriculum.' . (int)$course->id . '.' . (int)$version->id . '.rules';
                $saved = $app->getUserState($key, []); $app->setUserState($key, null);
                if (is_array($saved)) {
                    if (is_string($saved['video_threshold'] ?? null) || is_int($saved['video_threshold'] ?? null)) { $data['video_threshold'] = $saved['video_threshold']; }
                    if (in_array($saved['sequence_mode'] ?? null, ['free', 'sequential'], true)) { $data['sequence_mode'] = $saved['sequence_mode']; }
                }
                $this->rulesForm->bind($data);
            }
            if ($this->editorMode === 'lesson') {
                $this->getDocument()->getWebAssetManager()->useScript('com_jlms.videoMetadata');
                $this->getDocument()->addScriptOptions('com_jlms.videoMetadata', [
                    'url'=>\Joomla\CMS\Router\Route::link('administrator', 'index.php?option=com_jlms&task=lesson.metadata&format=json', false),
                    'policy'=>\Joomla\CMS\Component\ComponentHelper::getParams('com_jlms')->get('video_external_policy', 'blocked'),
                    'labels'=>array_map(static fn($key):string=>Text::_($key), [
                        'detected'=>'COM_JLMS_VIDEO_DURATION_DETECTED','loading'=>'COM_JLMS_VIDEO_DURATION_LOADING','play'=>'COM_JLMS_VIDEO_DURATION_PLAY',
                        'failed'=>'COM_JLMS_VIDEO_METADATA_FAILED','consent'=>'COM_JLMS_VIDEO_DURATION_CONSENT','manual'=>'COM_JLMS_VIDEO_DURATION_MANUAL_HELP',
                        'local'=>'COM_JLMS_VIDEO_DURATION_LOCAL','source'=>'COM_JLMS_VIDEO_DURATION_SOURCE'])]);
            }
            $this->moduleForm->bind($moduleData);
            $this->lessonForm->bind($lessonData);
            if ($editLesson) { $this->lessonForm->setFieldAttribute('module_id', 'readonly', 'true'); }
            // Two Joomla forms on one page must not emit duplicate jform_* element IDs.
            $this->moduleForm->setFieldAttribute('title', 'id', 'jlms_module_title');
            foreach (['id','revision','course_id','version_id','expected_version_revision'] as $name) {
                $this->moduleForm->setFieldAttribute($name, 'id', 'jlms_module_' . $name);
            }
        }
        if (!$this->editable && $version && $app->getInput()->getInt('edit_lesson')) {
            foreach ($this->outline['lessons'] as $lesson) {
                if ((int) $lesson->id === $app->getInput()->getInt('edit_lesson')) { $this->selectedItem = $lesson; }
            }
            if (!$this->selectedItem) { throw new \RuntimeException(Text::_('COM_JLMS_RESOURCE_NOT_FOUND'), 404); }
        }
        ToolbarHelper::title(Text::_('COM_JLMS_CURRICULUM'), 'graduation-cap');
        parent::display($tpl);
    }
}
