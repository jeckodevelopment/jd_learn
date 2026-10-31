<?php

declare(strict_types=1);


\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<section id="jlms-admin-dashboard">
    <h1>JD Learn</h1>
    <p class="alert alert-info"><?= Text::_('COM_JLMS_FOUNDATION_NOTICE') ?></p>
    <p><a class="btn btn-primary" href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_COURSES') ?></a>
       <a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_categories&extension=com_jlms.course') ?>"><?= Text::_('JCATEGORIES') ?></a></p>
    <h2><?= Text::_('COM_JLMS_VIDEO_PROVIDERS') ?></h2>
    <?php if (!$this->providers) : ?>
        <p><?= Text::_('COM_JLMS_PROVIDERS_DISABLED') ?></p>
    <?php endif; ?>
    <ul>
    <?php foreach ($this->providers as $provider) : ?>
        <li><?= $this->escape(Text::_($provider->labelKey)) ?> — <?= Text::_($provider->configured ? 'COM_JLMS_READY' : 'COM_JLMS_NOT_CONFIGURED') ?></li>
    <?php endforeach; ?>
    </ul>
</section>
