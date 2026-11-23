<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<form action="<?= Route::_('index.php?option=com_jlms&view=pathways') ?>" method="post" id="adminForm" name="adminForm">
 <section id="jlms-admin-pathways">
  <label for="jlms-pathway-state-filter"><?= Text::_('JSTATUS') ?></label>
  <select id="jlms-pathway-state-filter" name="filter_pathway_state" class="form-select w-auto" onchange="this.form.submit()">
   <option value="active"<?= $this->stateFilter === 'active' ? ' selected' : '' ?>><?= Text::_('JGLOBAL_FILTER_SELECT_STATUS') ?></option>
   <option value="trashed"<?= $this->stateFilter === 'trashed' ? ' selected' : '' ?>><?= Text::_('JTRASHED') ?></option>
  </select>
  <table class="table">
   <caption class="visually-hidden"><?= Text::_('COM_JLMS_PATHWAYS') ?></caption>
   <thead><tr><th scope="col" class="w-1 text-center"><?= HTMLHelper::_('grid.checkall') ?></th><th scope="col"><?= Text::_('JGLOBAL_TITLE') ?></th><th scope="col"><?= Text::_('JSTATUS') ?></th><th scope="col"><?= Text::_('COM_JLMS_REVISION') ?></th></tr></thead>
   <tbody><?php foreach ($this->items as $i => $item) : ?>
    <tr><td class="text-center"><?= HTMLHelper::_('grid.id', $i, (int) $item
        ->id) ?></td><td><a href="<?= Route::_('index.php?option=com_jlms&task=pathway.edit&id=' . (int) $item
            ->id) ?>"><?= $this->escape($item->title) ?></a></td>
    <td><?= Text::_(match ((int) $item->state) {
        1 => 'JPUBLISHED', 2 => 'JARCHIVED', -2 => 'JTRASHED', default => 'JUNPUBLISHED'
        }) ?></td><td><?= (int) $item->revision ?></td></tr>
          <?php endforeach; ?></tbody>
  </table>
  <?= $this->pagination->getListFooter() ?>
 </section>
 <input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0"><?= HTMLHelper::_('form.token') ?>
</form>
