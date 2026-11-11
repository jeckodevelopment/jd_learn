<?php

declare(strict_types=1);
namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\CMS\Table\ContentType;
use Joomla\Database\DatabaseInterface;

final class EditorialInstaller
{
    public static function install(DatabaseInterface $db): void
    {
        $query = $db->createQuery()->select('id')->from('#__categories')->where("extension='com_jlms.teacher' AND alias='jd-learn-teachers'");
        if (!$db->setQuery($query)->loadResult()) {
            $category = new \Joomla\CMS\Table\Category($db);
            $category->setLocation(1, 'last-child');
            $category->bind(['title' => 'JD Learn', 'alias' => 'jd-learn-teachers', 'extension' => 'com_jlms.teacher',
                'published' => 1, 'access' => 1, 'language' => '*', 'params' => '{}', 'metadata' => '{}', 'description' => '']);
            if (!$category->check() || !$category->store()) { throw new \RuntimeException('JD Learn teacher setup failed'); }
        }
        foreach (['course' => 'CourseTable', 'teacher' => 'TeacherTable'] as $type => $table) {
            $record = new ContentType($db);
            $exists = $record->load(['type_alias' => 'com_jlms.' . $type]);
            if (!$exists) {
                $record->type_title = 'JD Learn ' . $type;
                $record->type_alias = 'com_jlms.' . $type;
                $record->rules = '{}';
                $record->router = '';
                $record->content_history_options = json_encode(['formFile' => 'administrator/components/com_jlms/forms/' . $type . '.xml',
                    'hideFields' => ['asset_id', 'checked_out', 'checked_out_time'],
                    'ignoreChanges' => ['revision', 'modified_by', 'modified_at', 'checked_out', 'checked_out_time']], JSON_THROW_ON_ERROR);
            }
            if ($type === 'course') {
                $record->table = json_encode([
                    'special' => ['type' => $table, 'prefix' => 'Jlms\\Component\\Jlms\\Administrator\\Table', 'key' => 'id'],
                    'common' => ['dbtable' => '#__ucm_content', 'key' => 'ucm_id', 'type' => 'Corecontent',
                        'prefix' => 'Joomla\\CMS\\Table\\', 'config' => 'array()'],
                ], JSON_THROW_ON_ERROR);
                $record->field_mappings = json_encode(['common' => [
                    'core_content_item_id' => 'id', 'core_title' => 'title', 'core_state' => 'state',
                    'core_alias' => 'alias', 'core_created_user_id' => 'created_by', 'core_created_time' => 'created_at',
                    'core_modified_user_id' => 'modified_by', 'core_modified_time' => 'modified_at', 'core_body' => 'summary',
                    'core_access' => 'content_access', 'core_language' => 'language', 'core_params' => 'params_json',
                    'core_metadata' => 'metadata_json', 'core_catid' => 'catid', 'core_images' => 'image',
                    'core_publish_up' => 'publish_up', 'core_publish_down' => 'publish_down',
                    'core_version' => 'revision', 'asset_id' => 'asset_id',
                ]], JSON_THROW_ON_ERROR);
            } elseif (!$exists) {
                $record->table = json_encode(['special' => ['type' => $table,
                    'prefix' => 'Jlms\\Component\\Jlms\\Administrator\\Table', 'key' => 'id']], JSON_THROW_ON_ERROR);
                $record->field_mappings = '{}';
            } else {
                continue;
            }
            if (!$record->check() || !$record->store()) { throw new \RuntimeException('JD Learn content type setup failed'); }
        }
    }
}
