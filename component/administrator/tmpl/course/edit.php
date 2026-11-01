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
    <section id="jlms-course-publication-help" class="alert alert-info" aria-labelledby="jlms-course-publication-heading">
        <h2 id="jlms-course-publication-heading" class="h4"><?= Text::_('COM_JLMS_PUBLICATION_HEADING') ?></h2>
        <p><?= Text::_('COM_JLMS_PUBLICATION_EDITOR_HELP') ?></p>
        <?php if ((int) $this->item->id > 0) : ?>
        <a id="jlms-course-programme-link" class="btn btn-secondary" href="<?= Route::link('administrator', 'index.php?option=com_jlms&view=curriculum&id=' . (int) $this->item->id) ?>"><?= Text::_('COM_JLMS_PUBLICATION_OPEN_PROGRAMME') ?></a>
        <?php else : ?><p><?= Text::_('COM_JLMS_PUBLICATION_SAVE_FIRST') ?></p><?php endif; ?>
    </section>
    <?php if ($this->readOnly) : ?><p class="alert alert-warning"><?= Text::_('COM_JLMS_VERSION_IMMUTABLE') ?></p><?php endif; ?>
    <?= $this->form->renderFieldset('details') ?>
    <?php if ($this->form->getFieldset('item_associations')) : ?>
    <section id="jlms-course-associations"><h2><?= Text::_('COM_JLMS_ASSOCIATIONS') ?></h2><p><?= Text::_('COM_JLMS_ASSOCIATIONS_HELP') ?></p><?= $this->form->renderFieldset('item_associations') ?></section>
    <?php endif; ?>
    <input type="hidden" name="task" value="">
    <?= HTMLHelper::_('form.token') ?>
</form>
