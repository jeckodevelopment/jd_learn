<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\Service;

use Joomla\CMS\Categories\Categories;

final class PathwayCategory extends Categories
{
    public function __construct($options = [])
    {
        $options['table'] = '#__jlms_pathways';
        $options['extension'] = 'com_jlms.pathway';
        $options['statefield'] = 'state';
        parent::__construct($options);
    }
}
