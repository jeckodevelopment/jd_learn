<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Service;

use Joomla\CMS\Categories\Categories;

final class QuestionCategory extends Categories
{
    public function __construct($options = [])
    {
        $options['table'] = '#__jlms_questions';
        $options['extension'] = 'com_jlms.question';
        $options['statefield'] = 'state';
        parent::__construct($options);
    }
}
