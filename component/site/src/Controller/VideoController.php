<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Controller;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Log\Log;

/** Session authenticated endpoints. JSON responses never redirect to a login HTML page. */
final class VideoController extends BaseController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['playback', 'save', 'close'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    private function respond(string $operation): void
    {
        $this->app->setHeader('Cache-Control', 'private, no-store', true);
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $request = bin2hex(random_bytes(8));
        try {
            if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405); }
            if ((int) $this->input->server->get('CONTENT_LENGTH', 0, 'UINT') > 75000) { throw new \RuntimeException('COM_JLMS_VIDEO_EVENTS_INVALID', 413); }
            if (!$this->checkToken('post', false)) { throw new \RuntimeException('JINVALID_TOKEN', 403); }
            $service = $this->app->bootComponent('com_jlms')->getVideoProgressService();
            $args = [$this->app->getIdentity(), $this->app->getLanguage()->getTag(),
                $this->input->post->getInt('enrollment_id', 0), $this->input->post->getInt('lesson_id', 0)];
            $data = match ($operation) {
                'playback' => $service->playback(...[...$args, $this->input->post->getString('consent', '') === '1']),
                'save' => $service->save(...[...$args, $this->input->post->getString('session_id', ''),
                    $this->input->post->getInt('source_revision', 0), $this->input->post->getInt('sequence', 0),
                    $this->input->post->getInt('duration_ms', 0), $this->input->post->get('events_json', '', 'RAW')]),
                'close' => $this->closeSession($service, $args),
            };
            echo new JsonResponse($data, null, false, true);
        } catch (\Throwable $error) {
            $code = (int) $error->getCode();
            $key = $error->getMessage();
            $safe = in_array($code, [400,401,403,404,405,409,413,422,429,503], true)
                && (preg_match('/\ACOM_JLMS_[A-Z_]+\z/D', $key) || in_array($key, ['JINVALID_TOKEN', 'JERROR_LAYOUT_PAGE_NOT_FOUND'], true));
            if (!$safe) { Log::add('Video operation failed; correlation=' . $request, Log::ERROR, 'com_jlms'); $key = 'COM_JLMS_SAVE_FAILED'; $code = 500; }
            $this->app->setHeader('status', (string) $code, true);
            http_response_code($code);
            echo new JsonResponse(['code' => $key, 'request_id' => $request], Text::_($key), true, true);
        }
        $this->app->close();
    }

    private function closeSession(object $service, array $args): array
    {
        $service->close(...[...$args, $this->input->post->getString('session_id', '')]);
        return ['closed' => true];
    }

    public function playback(): void { $this->respond('playback'); }
    public function save(): void { $this->respond('save'); }
    public function close(): void { $this->respond('close'); }
}
