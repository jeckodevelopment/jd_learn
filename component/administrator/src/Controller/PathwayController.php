<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Controller;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\User\User;

final class PathwayController extends FormController
{
    protected $view_list = 'pathways';

    public function execute($task)
    {
        if (
            !in_array(strtolower($task), ['add', 'edit', 'apply', 'save', 'cancel', 'trash', 'restore'
            , 'archive', 'publish', 'unpublish', 'purge'], true)
        ) {
            throw new \RuntimeException('JERROR_LAYOUT_PAGE_NOT_FOUND', 404);
        }
        return parent::execute($task);
    }

    /** Return to the pathway list while preserving only validated Search Tools filters. */
    private function redirectToList(string $state): void
    {
        $raw = $this->input->post->get('filter', [], 'array');
        $filters = [];
        foreach (['search', 'category_id', 'access', 'language'] as $name) {
            $value = $raw[$name] ?? null;
            if (!is_scalar($value)) {
                continue;
            }
            $value = (string) $value;
            if ($name === 'search') {
                $value = substr(trim($value), 0, 200);
            } elseif ($name === 'language') {
                if ($value !== '' && !preg_match('/^(?:\*|[A-Za-z0-9_-]{1,32})$/D', $value)) {
                    continue;
                }
            } elseif ($value !== '' && !preg_match('/^[1-9][0-9]{0,9}$/D', $value)) {
                continue;
            }
            if ($value !== '') {
                $filters[$name] = $value;
            }
        }
        $filters['pathway_state'] = $state === 'trashed' ? 'trashed' : 'active';
        $url = 'index.php?' . http_build_query([
            'option' => 'com_jlms',
            'view' => 'pathways',
            'filter' => $filters,
        ]);
        $this->setRedirect(\Joomla\CMS\Router\Route::link('administrator', $url, false));
    }

    /** State-changing list actions are POST-only, CSRF-protected and atomic. */
    private function transition(int $target, string $filterAfter = 'active'): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException('JINVALID_TOKEN', 403);
        }
        $raw = $this->input->post->get('cid', [], 'array');
        if ($raw === [] || count($raw) > 100) {
            throw new \RuntimeException('COM_JLMS_PATHWAY_INVALID', 422);
        }
        $ids = [];
        foreach ($raw as $value) {
            if (
                (!is_int($value) && !is_string($value))
                || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $value)
            ) {
                throw new \RuntimeException('COM_JLMS_PATHWAY_INVALID', 422);
            }
            $ids[] = (int) $value;
        }
        $model = $this->app->bootComponent('com_jlms')->getMVCFactory()
            ->createModel('Pathway', 'Administrator', ['ignore_request' => true]);
        if (!$model->changeStates($ids, $target)) {
            throw new \RuntimeException((string) $model->getError(), 403);
        }
        $this->redirectToList($filterAfter);
    }

    public function trash(): void
    {
        $this->transition(-2, 'trashed');
    }
    public function restore(): void
    {
        $this->transition(0, 'trashed');
    }
    public function archive(): void
    {
        $this->transition(2);
    }
    public function publish(): void
    {
        $this->transition(1);
    }
    public function unpublish(): void
    {
        $this->transition(0);
    }

    /** Destructive action: exactly one selected, trashed, empty pathway. */
    public function purge(): void
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            throw new \RuntimeException('COM_JLMS_METHOD_NOT_ALLOWED', 405);
        }
        if (!$this->checkToken('post', false)) {
            throw new \RuntimeException('JINVALID_TOKEN', 403);
        }
        $raw = $this->input->post->get('cid', [], 'array');
        if (
            count($raw) !== 1 || (!is_int($raw[0]) && !is_string($raw[0]))
            || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $raw[0])
        ) {
            throw new \RuntimeException('COM_JLMS_PATHWAY_INVALID', 422);
        }
        $model = $this->app->bootComponent('com_jlms')->getMVCFactory()
            ->createModel('Pathway', 'Administrator', ['ignore_request' => true]);
        if (!$model->purgeEmpty((int) $raw[0])) {
            throw new \RuntimeException((string) $model->getError(), 403);
        }
        $this->redirectToList('trashed');
    }

    protected function allowAdd($data = [])
    {
        $actor = new User((int) $this->app->getIdentity()->id);
        $actor->clearAccessRights();
        return $actor->id && !$actor->block
            && $actor->authorise('jlms.pathways.manage', 'com_jlms')
            && $actor->authorise('core.create', 'com_jlms');
    }

    protected function allowEdit($data = [], $key = 'id')
    {
        $id = (int) ($data[$key] ?? 0);
        $actor = new User((int) $this->app->getIdentity()->id);
        $actor->clearAccessRights();
        return $id > 0 && $actor->id && !$actor->block
            && $actor->authorise('jlms.pathways.manage', 'com_jlms')
            && $actor->authorise('core.edit', 'com_jlms.pathway.' . $id);
    }
}
