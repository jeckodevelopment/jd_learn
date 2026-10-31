<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Extension;

use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;

final class JlmsComponent extends MVCComponent implements CategoryServiceInterface
{
    use CategoryServiceTrait;

    protected function getTableNameForSection(?string $section = null)
    {
        return match ($section) {
            'question' => 'jlms_questions',
            'teacher' => 'jlms_teachers',
            'pathway' => 'jlms_pathways',
            'category' => 'categories',
            default => 'jlms_courses',
        };
    }

    protected function getStateColumnForSection(?string $section = null)
    {
        return $section === 'category' ? 'published' : 'state';
    }
}
