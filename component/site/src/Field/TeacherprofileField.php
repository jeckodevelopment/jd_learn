<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Site\Field;

final class TeacherprofileField extends \Joomla\CMS\Form\Field\ListField
{
    protected $type = 'Teacherprofile';
    protected function getOptions()
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $options = parent::getOptions();
        foreach (\Jlms\Component\Jlms\Administrator\Application\TeacherProfiles::available($db, $app->getIdentity()) as $profile) {
            $options[] = \Joomla\CMS\HTML\HTMLHelper::_('select.option', $profile->id, $profile->title);
        }
        return $options;
    }
}
