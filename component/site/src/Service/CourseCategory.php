<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Service;

use Joomla\CMS\Categories\Categories;

final class CourseCategory extends Categories
{
    public function __construct($options = [])
    {
        $options['table'] = '#__jlms_courses';
        $options['extension'] = 'com_jlms.course';
        $options['statefield'] = 'state';
        parent::__construct($options);
    }
}
