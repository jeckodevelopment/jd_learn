<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;

final class EnrollmentsModel extends ListModel
{
    protected function populateState($ordering = 'e.id', $direction = 'DESC')
    {
        parent::populateState($ordering, $direction);
        $input = Factory::getApplication()->getInput();
        $this->setState('filter.course', $input->getInt('id', 0));
        $this->setState('filter.roster', $input->get('filter', [], 'array'));
        $this->setState('list.limit', max(1, min(50, (int) $this->state->get('list.limit', 20))));
    }

    private function service()
    {
        return Factory::getApplication()->bootComponent('com_jlms')->getEnrollmentRosterService();
    }

    public function getCourse(): object
    {
        try { return $this->service()->course(Factory::getApplication()->getIdentity(), (int) $this->getState('filter.course')); }
        catch (\Throwable $error) { $this->fail($error); }
    }

    protected function getListQuery()
    {
        return $this->service()->query(Factory::getApplication()->getIdentity(), (int) $this->getState('filter.course'), $this->getState('filter.roster', []));
    }

    protected function getStoreId($id = '')
    {
        return parent::getStoreId($id . ':' . (int) Factory::getApplication()->getIdentity()->id . ':' . $this->getState('filter.course') . ':' . json_encode($this->getState('filter.roster', [])));
    }

    public function getItems()
    {
        try {
            // Validate before ListModel catches query failures and returns false.
            $this->getListQuery();
            $items = parent::getItems();
            if (!is_array($items)) { throw new \RuntimeException('COM_JLMS_SAVE_FAILED', 500); }
            return $items;
        } catch (\Throwable $error) { $this->fail($error); }
    }

    public function getTotal()
    {
        try {
            $this->getListQuery();
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

    public function getRosterForm(): Form
    {
        $form = Form::getInstance('com_jlms.enrollments.filters', JPATH_ADMINISTRATOR . '/components/com_jlms/forms/enrollments.xml', ['control' => 'filter']);
        $form->bind($this->getState('filter.roster', []));
        return $form;
    }
}
