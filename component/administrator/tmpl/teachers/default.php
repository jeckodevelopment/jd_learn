<?php
\defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$this->getDocument()->getWebAssetManager()->useScript('table.columns')->useScript('multiselect');
$order = $this->escape($this->state->get('list.ordering', 'a.title'));
$direction = $this->escape($this->state->get('list.direction', 'ASC'));
?>
<form action="<?= Route::_('index.php?option=com_jlms&view=teachers') ?>" method="post" id="adminForm" name="adminForm">
 <div id="j-main-container" class="j-main-container">
  <section id="jlms-admin-teachers" aria-label="<?= Text::_('COM_JLMS_TEACHERS') ?>">
   <?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]) ?>
   <?php if (!$this->items) : ?>
    <p class="alert alert-info"><?= Text::_('JGLOBAL_NO_MATCHING_RESULTS') ?></p>
   <?php else : ?>
   <table class="table itemList" id="jlms-teacher-list">
    <caption class="visually-hidden"><?= Text::_('COM_JLMS_TEACHERS') ?></caption>
    <thead><tr>
     <th scope="col" class="w-10"><?= HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $direction, $order) ?></th>
     <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $direction, $order) ?></th>
     <th scope="col" class="d-none d-md-table-cell"><?= HTMLHelper::_('searchtools.sort', 'JAUTHOR', 'a.created_by', $direction, $order) ?></th>
     <th scope="col" class="d-none d-md-table-cell"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $direction, $order) ?></th>
     <th scope="col" class="text-center"><?= Text::_('COM_JLMS_TEACHER_COURSE_COUNT') ?></th>
     <th scope="col" class="d-none d-lg-table-cell"><?= HTMLHelper::_('searchtools.sort', 'COM_JLMS_COURSES_MODIFIED', 'a.modified_at', $direction, $order) ?></th>
     <th scope="col" class="d-none d-lg-table-cell"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $direction, $order) ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($this->items as $i => $item) : ?>
     <tr class="row<?= $i % 2 ?>" data-teacher-id="<?= (int) $item->id ?>">
      <td><span class="badge <?= (int) $item->state === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= Text::_((int) $item->state === 1 ? 'JPUBLISHED' : 'JUNPUBLISHED') ?></span></td>
      <th scope="row" class="has-context"><a href="<?= Route::_('index.php?option=com_jlms&task=teacher.edit&id=' . (int) $item->id) ?>"><?= $this->escape($item->title) ?></a>
       <div class="small text-body-secondary"><?= Text::_('COM_JLMS_REVISION') ?>: <?= (int) $item->revision ?></div>
      </th>
      <td class="d-none d-md-table-cell"><?= $this->escape($item->author_name ?? Text::_('JNONE')) ?></td>
      <td class="d-none d-md-table-cell"><?= $item->language === '*' ? Text::_('JALL') : $this->escape($item->language_title ?? $item->language) ?></td>
      <td class="text-center"><a class="badge bg-info text-dark" href="<?= Route::_('index.php?option=com_jlms&view=courses&filter[teacher_id]=' . (int) $item->id) ?>" aria-label="<?= $this->escape(Text::sprintf('COM_JLMS_TEACHER_COURSE_COUNT_FOR', $item->title, (int) $item->course_count)) ?>"><?= (int) $item->course_count ?></a></td>
      <td class="d-none d-lg-table-cell"><?= $item->modified_at ? HTMLHelper::_('date', $item->modified_at, Text::_('DATE_FORMAT_LC4')) : '' ?></td>
      <td class="d-none d-lg-table-cell"><?= (int) $item->id ?></td>
     </tr>
    <?php endforeach; ?>
    </tbody>
   </table>
   <?php endif; ?>
   <?= $this->pagination->getListFooter() ?>
  </section>
 </div>
 <input type="hidden" name="task" value="">
 <?= HTMLHelper::_('form.token') ?>
</form>
