<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Database\DatabaseInterface;

/** Core tables create their own native ACL assets. Never overwrite an existing workflow. */
final class WorkflowInstaller
{
    public static function install(CMSApplicationInterface $app, DatabaseInterface $db): void
    {
        $extension = 'com_jlms.course';
        $q = $db->createQuery()->select('id')->from($db->quoteName('#__workflows'))
            ->where('extension = :extension')->bind(':extension', $extension);
        if ($db->setQuery($q)->loadResult()) {
            return;
        }
        $factory = $app->bootComponent('com_workflow')->getMVCFactory();
        $save = static function (string $name, array $data) use ($factory): int {
            $table = $factory->createTable($name, 'Administrator');
            if (!$table->bind($data) || !$table->check() || !$table->store()) {
                throw new \RuntimeException('JD Learn native workflow setup failed.');
            }
            return (int) $table->id;
        };
        $db->transactionStart();
        try {
            $workflow = $save('Workflow', ['title' => 'JD Learn', 'extension' => $extension,
                'published' => 1, 'default' => 1, 'description' => 'JD Learn publication', 'ordering' => 0]);
            $draft = $save('Stage', ['workflow_id' => $workflow, 'title' => 'COM_JLMS_DRAFT', 'published' => 1, 'default' => 1, 'ordering' => 0]);
            $published = $save('Stage', ['workflow_id' => $workflow, 'title' => 'JPUBLISHED', 'published' => 1, 'default' => 0, 'ordering' => 1]);
            $save('Transition', ['workflow_id' => $workflow, 'title' => 'JPUBLISH', 'published' => 1,
                'from_stage_id' => $draft, 'to_stage_id' => $published, 'ordering' => 0, 'options' => ['publishing' => 1]]);
            $save('Transition', ['workflow_id' => $workflow, 'title' => 'JUNPUBLISH', 'published' => 1,
                'from_stage_id' => $published, 'to_stage_id' => $draft, 'ordering' => 1, 'options' => ['publishing' => 0]]);
            $db->transactionCommit();
        } catch (\Throwable $error) {
            $db->transactionRollback();
            throw $error;
        }
    }
}
