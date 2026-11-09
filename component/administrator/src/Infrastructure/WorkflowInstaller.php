<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Infrastructure;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Database\DatabaseInterface;

/** Core tables create their own native ACL assets. Extend only the workflow installed by JD Learn. */
final class WorkflowInstaller
{
    public static function install(CMSApplicationInterface $app, DatabaseInterface $db): void
    {
        $extension = 'com_jlms.course';
        $factory = $app->bootComponent('com_workflow')->getMVCFactory();
        $save = static function (string $name, array $data) use ($factory): int {
            $data += ['description' => ''];
            $table = $factory->createTable($name, 'Administrator');
            if (!$table->bind($data) || !$table->check() || !$table->store()) {
                throw new \RuntimeException('JD Learn native workflow setup failed.');
            }
            return (int) $table->id;
        };
        $workflowTitle = 'JD Learn';
        $workflowQuery = $db->createQuery()->select('id')->from($db->quoteName('#__workflows'))
            ->where('extension = :extension')->where('title = :title')
            ->bind(':extension', $extension)->bind(':title', $workflowTitle)->order('id');
        $workflow = (int) $db->setQuery($workflowQuery)->loadResult();
        if ($workflow) {
            $draft = self::stageId($db, $workflow, 'COM_JLMS_DRAFT');
            $published = self::stageId($db, $workflow, 'JPUBLISHED');
            if (!$draft || !$published) { return; }
        } else {
            $existing = $db->createQuery()->select('id')->from($db->quoteName('#__workflows'))
                ->where('extension = :extension')->bind(':extension', $extension);
            if ($db->setQuery($existing)->loadResult()) {
                // An administrator-owned workflow exists; do not alter or replace it.
                return;
            }
        }
        $db->transactionStart();
        try {
            if (!$workflow) {
                $workflow = $save('Workflow', ['title' => 'JD Learn', 'extension' => $extension,
                    'published' => 1, 'default' => 1, 'description' => 'JD Learn publication', 'ordering' => 0]);
                $draft = $save('Stage', ['workflow_id' => $workflow, 'title' => 'COM_JLMS_DRAFT', 'published' => 1, 'default' => 1, 'ordering' => 0]);
                $published = $save('Stage', ['workflow_id' => $workflow, 'title' => 'JPUBLISHED', 'published' => 1, 'default' => 0, 'ordering' => 1]);
                self::ensureTransition($db, $workflow, 'JPUBLISH', $draft, $published, 'publish', 1, $save);
                self::ensureTransition($db, $workflow, 'JUNPUBLISH', $published, $draft, 'unpublish', 0, $save);
            }
            $review = self::stageId($db, $workflow, 'COM_JLMS_IN_REVIEW');
            if (!$review) {
                $review = $save('Stage', ['workflow_id' => $workflow, 'title' => 'COM_JLMS_IN_REVIEW', 'published' => 1, 'default' => 0, 'ordering' => 2]);
            }
            self::ensureTransition($db, $workflow, 'COM_JLMS_SUBMIT_REVIEW_DRAFT', $draft, $review, 'review', 0, $save);
            self::ensureTransition($db, $workflow, 'COM_JLMS_SUBMIT_REVIEW_UPDATE', $published, $review, 'review', 1, $save);
            self::ensureTransition($db, $workflow, 'COM_JLMS_RETURN_INITIAL_DRAFT', $review, $draft, 'return', 0, $save);
            self::ensureTransition($db, $workflow, 'COM_JLMS_RETURN_PUBLISHED_DRAFT', $review, $published, 'return', 1, $save);
            self::ensureTransition($db, $workflow, 'COM_JLMS_APPROVE_REVIEW', $review, $published, 'publish', 1, $save);
            $db->transactionCommit();
        } catch (\Throwable $error) {
            $db->transactionRollback();
            throw $error;
        }
    }

    private static function stageId(DatabaseInterface $db, int $workflow, string $title): int
    {
        $query = $db->createQuery()->select('id')->from($db->quoteName('#__workflow_stages'))
            ->where('workflow_id = :workflow')->where('title = :title')
            ->bind(':workflow', $workflow)->bind(':title', $title);
        return (int) $db->setQuery($query)->loadResult();
    }

    private static function ensureTransition(DatabaseInterface $db, int $workflow, string $title, int $from, int $to,
        string $action, int $publishing, callable $save): void
    {
        $query = $db->createQuery()->select('id, options')->from($db->quoteName('#__workflow_transitions'))
            ->where('workflow_id = :workflow')->where('from_stage_id = :source')->where('to_stage_id = :target')
            ->where('title = :title')->bind(':workflow', $workflow)->bind(':source', $from)->bind(':target', $to)
            ->bind(':title', $title);
        foreach ($db->setQuery($query)->loadObjectList() as $transition) {
            $options = json_decode((string) $transition->options, true);
            if (is_array($options) && ($options['jlms_action'] ?? null) === $action
                && (int) ($options['publishing'] ?? -1) === $publishing) { return; }
        }
        $save('Transition', ['workflow_id' => $workflow, 'title' => $title, 'published' => 1,
            'from_stage_id' => $from, 'to_stage_id' => $to, 'ordering' => 10,
            'options' => ['publishing' => $publishing, 'jlms_action' => $action]]);
    }
}
