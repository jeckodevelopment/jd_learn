<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

use Joomla\Database\DatabaseInterface;
use Joomla\CMS\User\User;

/**
 * Captures native custom-field values that the current editor is allowed to restore.
 */
final class EditorialFieldHistory
{
    /**
     * Read the persisted Joomla field values after the owning model has saved.
     *
     * @return array<string, mixed>
     */
    public static function snapshot(
        DatabaseInterface $db,
        string $context,
        int $itemId,
        User $actor
    ): array {
        if ($itemId < 1) {
            return [];
        }

        $item = (string) $itemId;
        $query = $db->createQuery()
            ->select(['f.id', 'f.name', 'f.access', 'v.value'])
            ->from('#__fields_values AS v')
            ->innerJoin('#__fields AS f ON f.id = v.field_id')
            ->where('f.context = :context')
            ->where('v.item_id = :item')
            ->bind(':context', $context)
            ->bind(':item', $item);

        $viewLevels = $actor->getAuthorisedViewLevels();
        $values = [];

        foreach ($db->setQuery($query)->loadObjectList() as $fieldValue) {
            $fieldId = (int) $fieldValue->id;
            if (
                !$actor->authorise('core.edit.value', 'com_jlms.field.' . $fieldId)
                || !in_array((int) $fieldValue->access, $viewLevels, true)
            ) {
                continue;
            }

            $values[(string) $fieldValue->name] = $fieldValue->value;
        }

        return $values;
    }
}
