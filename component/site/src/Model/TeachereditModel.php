<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Site\Model;

final class TeachereditModel extends \Jlms\Component\Jlms\Administrator\Model\TeacherModel
{
    protected $name = 'teacher';
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm('com_jlms.teacher', JPATH_ADMINISTRATOR . '/components/com_jlms/forms/teacher.xml',
            ['control' => 'jform', 'load_data' => $loadData]);
    }
    public function getTable($name = '', $prefix = '', $options = [])
    {
        return parent::getTable('Teacher', 'Administrator', $options);
    }
}
