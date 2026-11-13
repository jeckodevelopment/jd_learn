<?php

declare(strict_types=1);


\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
?>
<section id="jlms-admin-dashboard">
    <header class="jlms-admin-dashboard__header">
        <p><?= Text::_('COM_JLMS_DASHBOARD_INTRO') ?></p>
    </header>
    <p class="alert alert-info"><?= Text::_('COM_JLMS_FOUNDATION_NOTICE') ?></p>
    <nav class="jlms-admin-dashboard__shortcuts" aria-label="<?= $this->escape(Text::_('COM_JLMS_DASHBOARD_TOOLS')) ?>">
        <?php foreach ($this->shortcutGroups as $groupKey => $shortcuts) : ?>
            <?php if (!$shortcuts) { continue; } ?>
            <section class="jlms-admin-dashboard__group" aria-labelledby="jlms-admin-group-<?= $this->escape(strtolower(preg_replace('/^COM_JLMS_DASHBOARD_GROUP_/', '', $groupKey))) ?>">
                <h2 id="jlms-admin-group-<?= $this->escape(strtolower(preg_replace('/^COM_JLMS_DASHBOARD_GROUP_/', '', $groupKey))) ?>"><?= $this->escape(Text::_($groupKey)) ?></h2>
                <ul>
                    <?php foreach ($shortcuts as $shortcut) : ?>
                        <li>
                            <a id="jlms-admin-shortcut-<?= $this->escape(strtolower(str_replace('COM_JLMS_', '', $shortcut['key']))) ?>" class="jlms-admin-dashboard__shortcut" href="<?= $this->escape($shortcut['url']) ?>">
                                <span class="jlms-admin-dashboard__shortcut-icon <?= $this->escape($shortcut['icon']) ?>" aria-hidden="true"></span>
                                <span><?= $this->escape(Text::_($shortcut['key'])) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </nav>
    <details class="jlms-admin-dashboard__providers">
        <summary><?= Text::_('COM_JLMS_DASHBOARD_PROVIDER_STATUS') ?></summary>
        <?php if (!$this->providers) : ?>
            <p><?= Text::_('COM_JLMS_PROVIDERS_DISABLED') ?></p>
        <?php else : ?>
            <ul>
                <?php foreach ($this->providers as $provider) : ?>
                    <li data-jlms-provider="<?= $this->escape($provider['key']) ?>"><span><?= $this->escape(Text::_($provider['labelKey'])) ?></span><span class="badge bg-<?= $this->escape($provider['stateClass']) ?>"><?= $this->escape(Text::_($provider['stateKey'])) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </details>
</section>
