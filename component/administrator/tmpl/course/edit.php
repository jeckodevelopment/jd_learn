<?php

declare(strict_types=1);


\defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
HTMLHelper::_('behavior.formvalidator');
?>
<form action="<?= Route::_('index.php?option=com_jlms&layout=edit&id=' . (int) $this->item->id) ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <p class="alert alert-info"><?= Text::_('COM_JLMS_FOUNDATION_NOTICE') ?></p>
    <?php if ($this->readOnly) : ?><p class="alert alert-warning"><?= Text::_('COM_JLMS_VERSION_IMMUTABLE') ?></p><?php endif; ?>
    <?= $this->form->renderFieldset('details') ?>
    <?php if ($this->form->getFieldset('item_associations')) : ?>
    <section id="jlms-course-associations"><h2><?= Text::_('COM_JLMS_ASSOCIATIONS') ?></h2><p><?= Text::_('COM_JLMS_ASSOCIATIONS_HELP') ?></p><?= $this->form->renderFieldset('item_associations') ?></section>
    <?php endif; ?>
    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token') ?>
</form>
