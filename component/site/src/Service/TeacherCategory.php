<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Service;

use Joomla\CMS\Categories\Categories;

final class TeacherCategory extends Categories
{
    public function __construct($options = [])
    {
        $options['table'] = '#__jlms_teachers';
        $options['extension'] = 'com_jlms.teacher';
        $options['statefield'] = 'state';
        parent::__construct($options);
    }
}
