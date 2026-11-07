<?php

declare(strict_types=1);


\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-admin-dashboard">
    <header class="jlms-admin-dashboard__header">
        <h1><?= Text::_('COM_JLMS_DASHBOARD_TITLE') ?></h1>
        <p><?= Text::_('COM_JLMS_DASHBOARD_INTRO') ?></p>
    </header>
    <p class="alert alert-info"><?= Text::_('COM_JLMS_FOUNDATION_NOTICE') ?></p>
    <section class="jlms-admin-dashboard__primary" aria-labelledby="jlms-dashboard-courses-heading">
        <div>
            <h2 id="jlms-dashboard-courses-heading"><?= Text::_('COM_JLMS_DASHBOARD_COURSES_HEADING') ?></h2>
            <p><?= Text::_('COM_JLMS_DASHBOARD_COURSES_HELP') ?></p>
        </div>
        <a class="btn btn-primary" href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_DASHBOARD_OPEN_COURSES') ?></a>
    </section>
    <nav class="jlms-admin-dashboard__links" aria-label="<?= $this->escape(Text::_('COM_JLMS_DASHBOARD_TOOLS')) ?>">
        <h2><?= Text::_('COM_JLMS_DASHBOARD_TOOLS') ?></h2>
        <ul>
            <li><a href="<?= Route::_('index.php?option=com_categories&extension=com_jlms.course') ?>"><?= Text::_('COM_JLMS_DASHBOARD_COURSE_CATEGORIES') ?></a></li>
            <li><a href="<?= Route::_('index.php?option=com_jlms&view=questionbank') ?>"><?= Text::_('COM_JLMS_QUESTION_BANK') ?></a></li>
            <?php if (\Joomla\CMS\Factory::getApplication()->getIdentity()->authorise('core.manage','com_jlms')) : ?><li><a href="<?= Route::_('index.php?option=com_jlms&view=teachers') ?>"><?= Text::_('COM_JLMS_TEACHERS') ?></a></li><?php endif; ?>
        </ul>
    </nav>
    <details class="jlms-admin-dashboard__providers">
        <summary><?= Text::_('COM_JLMS_DASHBOARD_PROVIDER_STATUS') ?></summary>
        <?php if (!$this->providers) : ?>
            <p><?= Text::_('COM_JLMS_PROVIDERS_DISABLED') ?></p>
        <?php else : ?>
            <ul>
                <?php foreach ($this->providers as $provider) : ?>
                    <li><span><?= $this->escape(Text::_($provider->labelKey)) ?></span><span class="badge bg-<?= $provider->configured ? 'success' : 'secondary' ?>"><?= Text::_($provider->configured ? 'COM_JLMS_READY' : 'COM_JLMS_NOT_CONFIGURED') ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </details>
</section>
