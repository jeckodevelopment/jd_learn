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
    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token') ?>
</form>
