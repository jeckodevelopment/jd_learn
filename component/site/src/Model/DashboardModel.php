<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;

final class DashboardModel extends ListModel
{
    protected function populateState($ordering = 'e.last_activity_at', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
        $filter = Factory::getApplication()->getInput()->get('filter', [], 'array');
        $progress = $filter['progress'] ?? 'all';
        $this->setState('filter.progress', is_string($progress) && in_array($progress, ['all', 'not_started', 'in_progress', 'completed'], true) ? $progress : 'all');
        $this->setState('list.limit', max(1, min(50, (int) $this->state->get('list.limit', 20))));
    }

    protected function getStoreId($id = '')
    {
        return parent::getStoreId($id . ':' . (int) Factory::getApplication()->getIdentity()->id . ':' . $this->getState('filter.progress', 'all'));
    }

    protected function getListQuery()
    {
        $app = Factory::getApplication();
        try {
            return $app->bootComponent('com_jlms')->getDashboardService()->query($app->getIdentity(), (string) $this->getState('filter.progress', 'all'));
        } catch (\Throwable $error) { $this->fail($error); }
    }

    public function getItems()
    {
        $app = Factory::getApplication();
        try {
            $component = $app->bootComponent('com_jlms');
            $actor = $component->getLearningService()->freshActor($app->getIdentity());
            $rows = parent::getItems();
            if (!is_array($rows)) { throw new \RuntimeException('COM_JLMS_SAVE_FAILED', 500); }
            return $component->getDashboardService()->cards($actor, $app->getLanguage()->getTag(), $rows);
        } catch (\Throwable $error) { $this->fail($error); }
    }

    public function getTotal()
    {
        $app = Factory::getApplication();
        try {
            $app->bootComponent('com_jlms')->getLearningService()->freshActor($app->getIdentity());
            $total = parent::getTotal();
            if ($total === false) { throw new \RuntimeException('COM_JLMS_SAVE_FAILED', 500); }
            return $total;
        } catch (\Throwable $error) { $this->fail($error); }
    }

    private function fail(\Throwable $error): never
    {
        if (in_array($error->getMessage(), ['COM_JLMS_LOGIN_REQUIRED', 'COM_JLMS_LEARNING_DENIED', 'COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_INVALID_INPUT'], true)) {
            throw new \RuntimeException(Text::_($error->getMessage()), $error->getCode());
        }
        throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
    }

    public function getProgressForm(): Form
    {
        $form = Form::getInstance('com_jlms.dashboard.filters', JPATH_SITE . '/components/com_jlms/forms/dashboard.xml', ['control' => 'filter']);
        $form->bind(['progress' => $this->getState('filter.progress', 'all')]);
        return $form;
    }
}
