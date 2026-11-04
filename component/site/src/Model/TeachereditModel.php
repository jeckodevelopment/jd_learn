<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Site\Model;

final class TeachereditModel extends \Jlms\Component\Jlms\Administrator\Model\TeacherModel
{
    protected $name = 'teacher';
    public function getTable($name = '', $prefix = '', $options = [])
    {
        return parent::getTable('Teacher', 'Administrator', $options);
    }
}
