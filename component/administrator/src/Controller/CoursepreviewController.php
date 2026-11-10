<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;

final class CoursepreviewController extends BaseController
{
    public function execute($task)
    {
        if (strtolower($task) !== 'playback') { throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404); }
        return parent::execute($task);
    }

    public function playback(): void
    {
        $code = 200; $data = null; $message = '';
        try {
            if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405); }
            if (!$this->checkToken('post', false)) { throw new \RuntimeException('JINVALID_TOKEN', 403); }
            $data = $this->app->bootComponent('com_jlms')->getMVCFactory()
                ->createModel('Coursepreview', 'Administrator', ['ignore_request' => true])->getPlaybackDescriptor();
        } catch (\Throwable $error) {
            $key = $error->getMessage();
            $code = match (true) {
                $key === 'JINVALID_TOKEN' || $key === 'JERROR_ALERTNOAUTHOR', (int) $error->getCode() === 403 => 403,
                $key === 'COM_JLMS_RESOURCE_NOT_FOUND', (int) $error->getCode() === 404 => 404,
                $key === 'COM_JLMS_METHOD_NOT_ALLOWED' => 405,
                (int) $error->getCode() === 409 => 409,
                default => 503,
            };
            $message = Text::_($key === 'JINVALID_TOKEN' ? 'JINVALID_TOKEN' : ($code === 403 ? 'JERROR_ALERTNOAUTHOR'
                : ($code === 404 ? 'COM_JLMS_RESOURCE_NOT_FOUND' : ($code === 405 ? 'COM_JLMS_METHOD_NOT_ALLOWED' : 'COM_JLMS_VIDEO_UNAVAILABLE'))));
        }
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        http_response_code($code);
        echo new JsonResponse($data, $message, $code !== 200, true);
        $this->app->close();
    }
}
