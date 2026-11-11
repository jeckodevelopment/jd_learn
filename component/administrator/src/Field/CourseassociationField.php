<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;

/** Bounded native select; the save model independently verifies every submitted ID. */
final class CourseassociationField extends ListField
{
    protected $type = 'Courseassociation';

    protected function getOptions()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $actor = new User((int) Factory::getApplication()->getIdentity()->id);
        $actor->clearAccessRights();
        $language = (string) $this->element['language'];
        $query = $db->createQuery()->select('id, title, created_by, checked_out')->from('#__jlms_courses')
            ->where('language = :language')->where('((state = 0 AND current_version_id IS NULL) OR (state = 1 AND current_version_id IS NOT NULL) OR id = :selected)')
            ->bind(':language', $language)->order('id DESC');
        $selected = (int) $this->value;
        $query->bind(':selected', $selected, \Joomla\Database\ParameterType::INTEGER);
        $items = $db->setQuery($query, 0, 2001)->loadObjectList();
        if (count($items) > 2000) {
            throw new \RuntimeException(\Joomla\CMS\Language\Text::_('COM_JLMS_ASSOCIATION_SELECTION_LIMIT'));
        }
        $options = [HTMLHelper::_('select.option', 0, 'JNONE')];
        foreach ($items as $item) {
            $asset = 'com_jlms.course.' . (int) $item->id;
            if (!$actor->guest && !$actor->block && (!$item->checked_out || (int) $item->checked_out === (int) $actor->id)
                && ($actor->authorise('core.edit', $asset) || ((int) $item->created_by === (int) $actor->id && $actor->authorise('core.edit.own', $asset)))) {
                $options[] = HTMLHelper::_('select.option', (int) $item->id, $item->title . ' (#' . (int) $item->id . ')');
            }
        }
        return array_merge(parent::getOptions(), $options);
    }
}
