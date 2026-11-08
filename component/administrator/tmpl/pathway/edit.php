<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
HTMLHelper::_('behavior.formvalidator');
?>
<form action="<?= Route::_('index.php?option=com_jlms&layout=edit&id=' . (int) $this->item->id) ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
 <section id="jlms-pathway-editor">
  <p><?= Text::_('COM_JLMS_PATHWAY_NOTICE') ?></p>
  <?= $this->form->renderFieldset('details') ?>
  <fieldset><legend><?= Text::_('COM_JLMS_PATHWAY_COURSES') ?></legend><?= $this->form->renderFieldset('courses') ?></fieldset>
 </section>
 <input type="hidden" name="task" value=""><?= HTMLHelper::_('form.token') ?>
</form>
