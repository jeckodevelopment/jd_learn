<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
$order = $this->escape($this->state->get('list.ordering', 'p.title'));
$direction = $this->escape($this->state->get('list.direction', 'ASC'));
?>
<form action="<?= Route::_('index.php?option=com_jlms&view=pathways') ?>" method="post" id="adminForm" name="adminForm">
 <section id="jlms-admin-pathways">
  <?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]) ?>
  <table class="table">
   <caption class="visually-hidden"><?= Text::_('COM_JLMS_PATHWAYS') ?></caption>
   <thead><tr>
     <th scope="col" class="w-1 text-center"><?= HTMLHelper::_('grid.checkall') ?></th>
     <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'p.title', $direction, $order) ?></th>
     <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'JCATEGORY', 'category_title', $direction, $order) ?></th>
     <th scope="col" class="w-10"><?= HTMLHelper::_('searchtools.sort', 'JSTATUS', 'p.state', $direction, $order) ?></th>
     <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_title', $direction, $order) ?></th>
     <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'p.language', $direction, $order) ?></th>
     <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'COM_JLMS_REVISION', 'p.revision', $direction, $order) ?></th>
    </tr></thead>
   <tbody><?php foreach ($this->items as $i => $item) : ?>
    <tr>
     <td class="text-center"><?= HTMLHelper::_('grid.id', $i, (int) $item->id) ?></td>
     <td><a href="<?= Route::_('index.php?option=com_jlms&task=pathway.edit&id=' . (int) $item->id) ?>"><?= $this->escape($item->title) ?></a><div class="small text-muted">#<?= (int) $item->id ?></div></td>
     <td><?= $this->escape((string) ($item->category_title ?? '')) ?></td>
     <td><?= Text::_(match ((int) $item->state) { 1 => 'JPUBLISHED', 2 => 'JARCHIVED', -2 => 'JTRASHED', default => 'JUNPUBLISHED' }) ?></td>
     <td><?= $this->escape((string) ($item->access_title ?? '')) ?></td>
     <td><?= $this->escape((string) $item->language) ?></td>
     <td><?= (int) $item->revision ?></td>
    </tr>
   <?php endforeach; ?></tbody>
  </table>
  <?= $this->pagination->getListFooter() ?>
 </section>
 <input type="hidden" name="task" value="">
 <input type="hidden" name="boxchecked" value="0"><?= HTMLHelper::_('form.token') ?>
</form>
