<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Jlms\Component\Jlms\Administrator\Application\MailQueueAuditService;

final class MailqueueController extends BaseController
{
    public function execute($task)
    {
        if (strtolower((string) $task) !== 'reconcile') {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    public function reconcile(): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException(Text::_('COM_JLMS_METHOD_NOT_ALLOWED'), 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException(Text::_('JINVALID_TOKEN_NOTICE'), 403);
        }

        $data = $this->input->post->get('jform', [], 'array');
        $id = (int) ($data['id'] ?? 0);
        $action = is_string($data['action'] ?? null) ? $data['action'] : '';
        $note = is_string($data['note'] ?? null) ? $data['note'] : '';
        $confirmed = ($data['confirm'] ?? '') === '1';
        try {
            $service = Factory::getContainer()->get(MailQueueAuditService::class);
            $service->reconcile($this->app->getIdentity(), $id, $action, $note, $confirmed);
            $this->app->enqueueMessage(Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_SUCCESS'), 'message');
        } catch (\Throwable $error) {
            $key = $error->getMessage();
            $message = str_starts_with($key, 'COM_JLMS_') || str_starts_with($key, 'JERROR_')
                ? Text::_($key) : Text::_('COM_JLMS_MAIL_QUEUE_RECONCILE_FAILED');
            $this->app->enqueueMessage($message, 'warning');
        }
        $this->setRedirect(Route::link('administrator', 'index.php?option=com_jlms&view=mailqueue', false));
    }
}
