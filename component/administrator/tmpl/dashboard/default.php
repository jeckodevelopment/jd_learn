<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Jlms\Component\Jlms\Administrator\Infrastructure\SchedulerHealth;
$safeOperationText = [
    'STORAGE' => ['COM_JLMS_SAFE_OPERATIONS_STORAGE_TITLE', 'COM_JLMS_SAFE_OPERATIONS_STORAGE_STEPS'],
    'MAIL' => ['COM_JLMS_SAFE_OPERATIONS_MAIL_TITLE', 'COM_JLMS_SAFE_OPERATIONS_MAIL_STEPS'],
    'CERTIFICATES' => ['COM_JLMS_SAFE_OPERATIONS_CERTIFICATES_TITLE', 'COM_JLMS_SAFE_OPERATIONS_CERTIFICATES_STEPS'],
    'COURSE' => ['COM_JLMS_SAFE_OPERATIONS_COURSE_TITLE', 'COM_JLMS_SAFE_OPERATIONS_COURSE_STEPS'],
    'SCHEDULER' => ['COM_JLMS_SAFE_OPERATIONS_SCHEDULER_TITLE', 'COM_JLMS_SAFE_OPERATIONS_SCHEDULER_STEPS'],
];
$schedulerHealthKeys = [
    'missing' => 'COM_JLMS_SCHEDULER_HEALTH_MISSING',
    'invalid_rules' => 'COM_JLMS_SCHEDULER_HEALTH_INVALID_RULES',
    'disabled' => 'COM_JLMS_SCHEDULER_HEALTH_DISABLED',
    'duplicates' => 'COM_JLMS_SCHEDULER_HEALTH_DUPLICATES',
    'failed' => 'COM_JLMS_SCHEDULER_HEALTH_FAILED',
    'overdue' => 'COM_JLMS_SCHEDULER_HEALTH_OVERDUE',
    'waiting' => 'COM_JLMS_SCHEDULER_HEALTH_WAITING',
    'operational' => 'COM_JLMS_SCHEDULER_HEALTH_OPERATIONAL',
    'unknown' => 'COM_JLMS_SCHEDULER_HEALTH_UNKNOWN',
];
?>
<section id="jlms-admin-dashboard">
    <header class="jlms-admin-dashboard__header">
        <p><?= Text::_('COM_JLMS_DASHBOARD_INTRO') ?></p>
    </header>
    <p class="alert alert-info"><?= Text::_('COM_JLMS_FOUNDATION_NOTICE') ?></p>
    <nav class="jlms-admin-dashboard__shortcuts" aria-label="<?= $this->escape(Text::_('COM_JLMS_DASHBOARD_TOOLS')) ?>">
        <?php foreach ($this->shortcutGroups as $groupKey => $shortcuts) : ?>
            <?php if (!$shortcuts) {
                continue;
            } ?>
            <section class="jlms-admin-dashboard__group"
    aria-labelledby="jlms-admin-group-<?= $this->escape(strtolower(substr($groupKey, strrpos($groupKey, '_') + 1))) ?>">
                <h2 id="jlms-admin-group-<?= $this->escape(strtolower(substr($groupKey, strrpos($groupKey, '_') + 1))) ?>"><?= $this->escape(Text::_($groupKey)) ?></h2>
                <ul>
                    <?php foreach ($shortcuts as $shortcut) : ?>
                        <li>
                            <a id="jlms-admin-shortcut-<?= $this->escape(strtolower(str_replace('COM_JLMS_', '', $shortcut['key']))) ?>" class="jlms-admin-dashboard__shortcut" href="<?= $shortcut['url'] ?>">
                                <span class="jlms-admin-dashboard__shortcut-icon <?= $this->escape($shortcut['icon']) ?>" aria-hidden="true"></span>
                                <span><?= $this->escape(Text::_($shortcut['key'])) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($groupKey === 'COM_JLMS_DASHBOARD_GROUP_COMMUNICATION' && $this->mailScheduler && ($this->mailScheduler['state'] !== 'operational' && $this->mailScheduler['state'] !== 'waiting' || $this->mailScheduler['duplicates'])) : ?>
                    <p class="small alert alert-warning mt-2 mb-0" role="status">
                        <?= $this->escape(Text::_('COM_JLMS_MAIL_SCHEDULER_WARNING')) ?>
                        <?php if ($this->canManageScheduler) :
                            ?><a href="<?= \Joomla\CMS\Router\Route::_('index.php?option=com_scheduler&view=tasks') ?>"><?= $this->escape(Text::_('COM_JLMS_MAIL_SCHEDULER_CONFIGURE')) ?></a><?php
                        endif; ?>
                    </p>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </nav>

    <?php if ($this->schedulerHealth) : ?>
    <details id="jlms-admin-scheduler-health" class="mt-3">
        <summary><?= $this->escape(Text::_('COM_JLMS_SCHEDULER_HEALTH_TITLE')) ?></summary>
        <p class="small"><?= $this->escape(Text::_('COM_JLMS_SCHEDULER_HEALTH_NOTE')) ?></p>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th scope="col"><?= Text::_('COM_JLMS_SCHEDULER_HEALTH_TASK') ?></th>
                    <th scope="col"><?= Text::_('COM_JLMS_SCHEDULER_HEALTH_STATE') ?></th>
                    <th scope="col"><?= Text::_('COM_JLMS_SCHEDULER_HEALTH_LAST') ?></th>
                    <th scope="col"><?= Text::_('COM_JLMS_SCHEDULER_HEALTH_NEXT') ?></th>
                    <th scope="col"><?= Text::_('COM_JLMS_SCHEDULER_HEALTH_GUIDANCE') ?></th></tr></thead>
                <tbody>
                <?php foreach ($this->schedulerHealth as $task) : ?>
                    <tr data-jlms-scheduler="<?= $this->escape($task['type']) ?>">
                        <th scope="row"><?= $this->escape(Text::_($task['label'])) ?></th>
                        <td><?= $this->escape(Text::_($schedulerHealthKeys[$task['state']] ?? 'COM_JLMS_SCHEDULER_HEALTH_UNKNOWN')) ?><?php if ($task['duplicates'] && $task['state'] !== 'duplicates') :
                            ?> · <?= $this->escape(Text::_('COM_JLMS_SCHEDULER_HEALTH_DUPLICATES')) ?><?php
                            endif; ?></td>
                        <td><?= $this->escape((string) ($task['last_execution'] ?: Text::_('JNEVER'))) ?></td>
                        <td><?= $this->escape((string) ($task['next_execution'] ?: '—')) ?></td>
                        <td><?= $this->escape(Text::_(SchedulerHealth::guidanceKey((string) $task['state'], (bool) $task['duplicates']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($this->canManageScheduler) : ?>
        <p><a href="<?= \Joomla\CMS\Router\Route::_('index.php?option=com_scheduler&view=tasks') ?>"><?= $this->escape(Text::_('COM_JLMS_MAIL_SCHEDULER_CONFIGURE')) ?></a></p>
        <?php endif; ?>
    </details>
    <?php endif; ?>
    <?php if ($this->canViewProviders) : ?>
    <details class="jlms-admin-dashboard__providers">
        <summary><?= Text::_('COM_JLMS_DASHBOARD_PROVIDER_STATUS') ?></summary>
        <?php if (!$this->providers) : ?>
            <p><?= Text::_('COM_JLMS_PROVIDERS_DISABLED') ?></p>
        <?php else : ?>
            <ul>
                <?php foreach ($this->providers as $provider) : ?>
                    <li data-jlms-provider="<?= $this->escape($provider['key']) ?>"><span><?= $this->escape(Text::_($provider['labelKey'])) ?></span> <span class="badge bg-<?= $this->escape($provider['stateClass']) ?>"><?= $this->escape(Text::_($provider['stateKey'])) ?></span><p class="small mb-1"><?= $this->escape(Text::_(\Jlms\Component\Jlms\Administrator\Application\DashboardProviderStatus::guidanceKey((string) $provider['stateKey']))) ?></p></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </details>
    <?php endif; ?>

    <?php if ($this->configurationDiagnostics) : ?>
    <details id="jlms-admin-configuration-diagnostics" class="mt-3">
        <summary><?= $this->escape(Text::_('COM_JLMS_CONFIG_DIAGNOSTICS_TITLE')) ?></summary>
        <p class="small"><?= $this->escape(Text::_('COM_JLMS_CONFIG_DIAGNOSTICS_NOTE')) ?></p>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr>
                    <th scope="col"><?= $this->escape(Text::_('COM_JLMS_CONFIG_DIAGNOSTICS_CHECK')) ?></th>
                    <th scope="col"><?= $this->escape(Text::_('COM_JLMS_CONFIG_DIAGNOSTICS_STATE')) ?></th>
                    <th scope="col"><?= $this->escape(Text::_('COM_JLMS_CONFIG_DIAGNOSTICS_REMEDY')) ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($this->configurationDiagnostics as $check) : ?>
                    <tr data-jlms-configuration-kind="<?= $this->escape($check['kind']) ?>"
    data-jlms-configuration-severity="<?= $this->escape($check['severity']) ?>">
                        <th scope="row"><?= $this->escape(Text::_($check['labelKey'])) ?></th>
                        <td><?= $this->escape(Text::_($check['stateKey'])) ?></td>
                        <td><?= $this->escape(Text::_($check['guidanceKey'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </details>
    <?php endif; ?>

    <?php if ($this->safeOperations) : ?>
    <details id="jlms-admin-safe-operations" class="mt-3">
        <summary><?= $this->escape(Text::_('COM_JLMS_SAFE_OPERATIONS_TITLE')) ?></summary>
        <p class="small"><?= $this->escape(Text::_('COM_JLMS_SAFE_OPERATIONS_INTRO')) ?></p>
        <ol>
        <?php foreach ($this->safeOperations as $operation) : ?>
            <li data-jlms-safe-operation="<?= $this->escape($operation) ?>">
                <strong><?= $this->escape(Text::_($safeOperationText[$operation][0])) ?></strong>
                <p><?= $this->escape(Text::_($safeOperationText[$operation][1])) ?></p>
            </li>
        <?php endforeach; ?>
        </ol>
        <p class="small"><?= $this->escape(Text::_('COM_JLMS_SAFE_OPERATIONS_NOTE')) ?></p>
    </details>
    <?php endif; ?>
</section>
