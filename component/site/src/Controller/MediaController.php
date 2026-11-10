<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Jlms\Component\Jlms\Administrator\Infrastructure\MaterialDownload;
use Jlms\Component\Jlms\Administrator\Application\CurriculumService;

final class MediaController extends BaseController
{
    public function execute($task)
    {
        if (strtolower($task) !== 'stream') { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return parent::execute($task);
    }

    public function stream(): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        if (!in_array(strtoupper($this->input->getMethod()), ['GET', 'HEAD'], true)) { throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405); }
        try {
            if ($this->input->getBool('preview')) {
                $ids = [];
                foreach (['course_id', 'version_id', 'lesson_id', 'source_revision'] as $key) {
                    try { $ids[] = CurriculumService::id($this->input->get($key, null, 'RAW')); }
                    catch (\InvalidArgumentException $error) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
                }
                $file = $this->app->bootComponent('com_jlms')->getLocalVideoService()->previewDelivery($this->app->getIdentity(), ...$ids);
                if ($file['mode'] === 'php_bounded') { MaterialDownload::send($this->app, $file); return; }
                $this->app->setHeader('Content-Type', 'video/mp4', true);
                $this->app->setHeader('Content-Disposition', 'inline; filename="video.mp4"', true);
                $this->app->setHeader('X-Content-Type-Options', 'nosniff', true);
                $this->app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
                $this->app->setHeader('X-Accel-Redirect', $file['internal_uri'], true);
                $this->app->getSession()->close(); $this->app->sendHeaders(); $this->app->close();
            }
            $ids = [];
            foreach (['enrollment_id', 'lesson_id', 'source_revision'] as $key) {
                try { $ids[] = CurriculumService::id($this->input->get($key, null, 'RAW')); }
                catch (\InvalidArgumentException $error) { throw new \RuntimeException('COM_JLMS_INVALID_INPUT', 400); }
            }
            $file = $this->app->bootComponent('com_jlms')->getLocalVideoService()->delivery($this->app->getIdentity(), $this->app->getLanguage()->getTag(), ...$ids);
            if ($file['mode'] === 'php_bounded') { MaterialDownload::send($this->app, $file); return; }
            $this->app->setHeader('Content-Type', 'video/mp4', true);
            $this->app->setHeader('Content-Disposition', 'inline; filename="video.mp4"', true);
            $this->app->setHeader('X-Content-Type-Options', 'nosniff', true);
            $this->app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
            $this->app->setHeader('X-Accel-Redirect', $file['internal_uri'], true);
            $this->app->getSession()->close(); $this->app->sendHeaders(); $this->app->close();
        } catch (\Throwable $error) {
            $key = $error->getMessage(); $code = (int) $error->getCode();
            if (in_array($code, [400,401,403,404,405,409,416,422,503], true) && preg_match('/\ACOM_JLMS_[A-Z_]+\z/D', $key)) {
                throw new \RuntimeException(Text::_($key), $code);
            }
            Log::add('Local video delivery failed; correlation=' . bin2hex(random_bytes(8)), Log::ERROR, 'com_jlms');
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
    }
}
