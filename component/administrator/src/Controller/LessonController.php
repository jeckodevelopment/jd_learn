<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

final class LessonController extends CurriculumController
{
    public function execute($task)
    {
        if (!in_array(strtolower($task), ['metadata', 'save', 'delete', 'reorder', 'duplicate', 'move'], true)) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    public function metadata(): void
    {
        $data = null; $code = 200; $message = '';
        try {
            if (strtoupper($this->input->getMethod()) !== 'POST') { throw new \DomainException('COM_JLMS_METHOD_NOT_ALLOWED'); }
            if (!$this->checkToken('post', false)) { throw new \DomainException('JINVALID_TOKEN'); }
            $input = $this->input->post->get('jform', [], 'array');
            $data = $this->app->bootComponent('com_jlms')->getCurriculumService()->authoringMetadata($this->app->getIdentity(), $input);
            $plans = $this->app->getUserState('com_jlms.video.duration', []);
            $plans = array_filter(is_array($plans) ? $plans : [], static fn($p): bool=>is_array($p) && ($p['expires'] ?? 0) >= time());
            $plans = array_slice($plans, -7, null, true); $ticket = bin2hex(random_bytes(32));
            $plans[$ticket] = ['fingerprint'=>$data['fingerprint'],'duration_ms'=>$data['duration_ms'],'origin'=>$data['origin'],'expires'=>time()+900];
            $this->app->setUserState('com_jlms.video.duration', $plans);
            unset($data['fingerprint']); $data['ticket'] = $ticket;
            $data['adapter_url'] = is_string($data['provider'] ?? null)
                && preg_match('/\\A[a-z][a-z0-9_]{1,63}\\z/D', $data['provider'])
                ? \Joomla\CMS\Uri\Uri::root(true) . '/media/plg_jlmsvideo_' . $data['provider'] . '/js/player.js' : null;
        } catch (\Throwable $error) {
            $key = $error->getMessage();
            $code = match ($key) {
                'COM_JLMS_METHOD_NOT_ALLOWED'=>405, 'JINVALID_TOKEN','JERROR_ALERTNOAUTHOR'=>403,
                'COM_JLMS_RESOURCE_NOT_FOUND'=>404, 'COM_JLMS_REVISION_CONFLICT','COM_JLMS_VERSION_IMMUTABLE'=>409,
                'INVALID_ID','COM_JLMS_INVALID_INPUT','SOURCE_INVALID','COM_JLMS_SOURCE_INVALID','COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED'=>422,
                'COM_JLMS_VIDEO_EDITOR_CONFIG_REQUIRED','COM_JLMS_VIDEO_EDITOR_POLICY_BLOCKED'=>422,
                default=>503,
            };
            $message = \Joomla\CMS\Language\Text::_($code === 503 ? 'COM_JLMS_VIDEO_METADATA_FAILED' : ($key === 'SOURCE_INVALID' ? 'COM_JLMS_SOURCE_INVALID' : $key));
            $data = null;
        }
        $this->app->setHeader('status', (string)$code, true); http_response_code($code);
        $this->app->setHeader('Content-Type','application/json; charset=utf-8',true);
        $this->app->setHeader('Cache-Control','private, no-store',true);
        echo new \Joomla\CMS\Response\JsonResponse($data,$message,$code!==200,true); $this->app->close();
    }

    public function move(): void { $this->perform('move', 'lesson'); }

    public function duplicate(): void { $this->perform('duplicate', 'lesson'); }

    public function save(): void
    {
        $this->perform('save', 'lesson');
    }

    public function delete(): void
    {
        $this->perform('delete', 'lesson');
    }

    public function reorder(): void
    {
        $this->perform('reorder', 'lesson');
    }
}
