<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

class CurriculumController extends BaseController
{
    protected function perform(string $operation, string $kind = ''): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        $this->checkToken('post');
        $input = $this->input->post->get('jform', [], 'array');
        $courseId = 0;
        $versionId = 0;
        try {
            $courseId = CurriculumService::id($input['course_id'] ?? null);
            $versionId = CurriculumService::id($input['version_id'] ?? 0, true);
            $service = $this->app->bootComponent('com_jlms')->getCurriculumService();
            $actor = $this->app->getIdentity();
            switch ($operation) {
                case 'version':
                    $versionId = $service->createDraft($actor, $courseId, CurriculumService::id($input['revision'] ?? null));
                    break;
                case 'save':
                    $kind === 'module' ? $service->saveModule($actor, $input) : $service->saveLesson($actor, $input);
                    break;
                case 'delete':
                    $service->deleteItem($actor, $kind, $input);
                    break;
                case 'reorder':
                    $service->reorder($actor, $kind, $input);
                    break;
                default:
                    throw new \InvalidArgumentException('COM_JLMS_INVALID_INPUT');
            }
            $this->setMessage(Text::_('COM_JLMS_CURRICULUM_SAVED'));
        } catch (\DomainException | \InvalidArgumentException $error) {
            $key = $error->getMessage();
            $known = ['JERROR_ALERTNOAUTHOR', 'COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_VERSION_IMMUTABLE',
                'COM_JLMS_REVISION_CONFLICT', 'COM_JLMS_INVALID_INPUT', 'COM_JLMS_INVALID_COURSE',
                'COM_JLMS_OUTLINE_LIMIT', 'COM_JLMS_VERSION_COPY_NOT_AVAILABLE', 'COM_JLMS_SOURCE_INVALID',
                'COM_JLMS_LESSON_KIND_NOT_AVAILABLE', 'COM_JLMS_MOVE_MODULE_NOT_AVAILABLE', 'COM_JLMS_ITEM_HAS_DEPENDENCIES'];
            $this->setMessage(Text::_(in_array($key, $known, true) ? $key : 'COM_JLMS_INVALID_INPUT'), 'error');
        } catch (\Throwable $error) {
            Log::add('Curriculum mutation failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            $this->setMessage(Text::_('COM_JLMS_SAVE_FAILED'), 'error');
        }
        $target = $courseId ? 'index.php?option=com_jlms&view=curriculum&id=' . $courseId . '&version_id=' . $versionId
            : 'index.php?option=com_jlms&view=courses';
        $this->setRedirect(Route::link('administrator', $target, false));
    }

    public function execute($task)
    {
        if (static::class === self::class && strtolower($task) !== 'createversion') {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    public function createVersion(): void
    {
        $this->perform('version');
    }
}
