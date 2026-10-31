<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Log\Log;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

final class VideofileController extends BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['upload', 'scan'], true)) { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return parent::execute($task);
    }
    private function perform(bool $scan): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405); }
        $postLimit = trim((string) ini_get('post_max_size'));
        $factor = match (strtolower(substr($postLimit, -1))) { 'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1 };
        $postBytes = (int) ((float) $postLimit * $factor);
        if ($postBytes > 0 && $this->input->server->getInt('CONTENT_LENGTH', 0) > $postBytes) { throw new \RuntimeException(Text::_('COM_JLMS_FILE_SIZE'), 413); }
        if (!$this->checkToken('post', false)) { throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403); }
        try {
            $data = $this->input->post->get('jform', [], 'array');
            $course = CurriculumService::id($data['course_id'] ?? null); $version = CurriculumService::id($data['version_id'] ?? null);
            $lesson = CurriculumService::id($data['lesson_id'] ?? null); $revision = CurriculumService::id($data['expected_version_revision'] ?? null);
            $service = $this->app->bootComponent('com_jlms')->getLocalVideoService();
            if ($scan) { $service->scan($this->app->getIdentity(), $course, $version, $lesson, $revision); }
            else {
                $files = $this->input->files->get('jform', [], 'array');
                $service->upload($this->app->getIdentity(), $course, $version, $lesson, $revision, $files['upload'] ?? []);
            }
            $this->setMessage(Text::_('COM_JLMS_VIDEO_FILE_SAVED'));
            $this->setRedirect(Route::link('administrator', 'index.php?option=com_jlms&view=curriculum&id=' . $course . '&version_id=' . $version, false));
        } catch (\Throwable $error) {
            $code = (int) $error->getCode(); $key = $error->getMessage();
            if (in_array($code, [400,403,404,409,413,422,503], true) && preg_match('/\ACOM_JLMS_[A-Z_]+\z/D', $key)) { throw new \RuntimeException(Text::_($key), $code); }
            if ($error instanceof \InvalidArgumentException) { throw new \RuntimeException(Text::_('COM_JLMS_INVALID_INPUT'), 400); }
            Log::add('Local video mutation failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
    public function upload(): void { $this->perform(false); }
    public function scan(): void { $this->perform(true); }
}
