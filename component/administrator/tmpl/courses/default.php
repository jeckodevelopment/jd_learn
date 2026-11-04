<?php

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

$this->getDocument()->getWebAssetManager()->useScript('table.columns')->useScript('multiselect');
$order = $this->escape($this->state->get('list.ordering', 'a.id'));
$direction = $this->escape($this->state->get('list.direction', 'DESC'));
$actor = Factory::getApplication()->getIdentity();
?>
<form action="<?= Route::_('index.php?option=com_jlms&view=courses') ?>" method="post" id="adminForm" name="adminForm">
 <div id="j-main-container" class="j-main-container">
  <section id="jlms-admin-courses" aria-label="<?= Text::_('COM_JLMS_COURSES') ?>">
   <?= LayoutHelper::render('joomla.searchtools.default', ['view'=>$this]) ?>
   <?php if (!$this->items) : ?>
    <p id="jlms-courses-empty" class="alert alert-info"><?= Text::_('JGLOBAL_NO_MATCHING_RESULTS') ?></p>
   <?php else : ?>
   <table class="table itemList" id="jlms-course-list">
    <caption class="visually-hidden"><?= Text::_('COM_JLMS_COURSES') ?></caption>
    <thead><tr>
     <td class="w-1 text-center"><?= HTMLHelper::_('grid.checkall') ?></td>
     <th scope="col" class="w-10"><?= HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $direction, $order) ?></th>
     <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $direction, $order) ?></th>
     <th scope="col" class="d-none d-md-table-cell"><?= HTMLHelper::_('searchtools.sort', 'JCATEGORY', 'category_title', $direction, $order) ?></th>
     <th scope="col" class="d-none d-lg-table-cell"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'access_title', $direction, $order) ?></th>
     <th scope="col" class="d-none d-lg-table-cell"><?= HTMLHelper::_('searchtools.sort', 'JAUTHOR', 'author_name', $direction, $order) ?></th>
     <th scope="col" class="d-none d-md-table-cell"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $direction, $order) ?></th>
     <th scope="col" class="d-none d-lg-table-cell"><?= HTMLHelper::_('searchtools.sort', 'COM_JLMS_COURSES_MODIFIED', 'a.modified_at', $direction, $order) ?></th>
     <th scope="col" class="d-none d-lg-table-cell"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $direction, $order) ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($this->items as $i=>$item) :
      $programme = Route::_('index.php?option=com_jlms&view=curriculum&id=' . (int) $item->id);
    ?>
     <tr class="row<?= $i % 2 ?> jlms-admin-course" data-course-id="<?= (int) $item->id ?>">
      <td class="text-center"><?= HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title) ?></td>
      <td><a class="badge <?= (int) $item->state === 1 ? 'bg-success' : 'bg-secondary' ?> text-decoration-none" href="<?= $programme ?>#jlms-publication-status" aria-label="<?= $this->escape(Text::sprintf('COM_JLMS_COURSES_PUBLICATION_FOR', $item->title)) ?>"><?= Text::_((int) $item->state === 1 ? 'JPUBLISHED' : 'JUNPUBLISHED') ?></a></td>
      <th scope="row" class="has-context">
       <?php if ($item->checked_out) : ?><?= HTMLHelper::_('jgrid.checkedout', $i, $item->editor ?? Text::_('JNONE'), $item->checked_out_time, 'courses.', false) ?><?php endif; ?>
       <a class="jlms-admin-course-title" href="<?= Route::_('index.php?option=com_jlms&task=course.edit&id=' . (int) $item->id) ?>"><?= $this->escape($item->title) ?></a>
       <div class="small text-body-secondary"><?= Text::_('JFIELD_ALIAS_LABEL') ?>: <?= $this->escape($item->alias) ?></div>
       <div class="small mt-1 jlms-admin-course-actions">
        <a href="<?= $programme ?>"><?= Text::_('COM_JLMS_CURRICULUM') ?></a>
        <?php if ($actor->authorise('jlms.enrollments.manage', 'com_jlms.course.' . (int) $item->id)) : ?>
         <span aria-hidden="true"> · </span><a href="<?= Route::_('index.php?option=com_jlms&view=enrollments&id=' . (int) $item->id) ?>"><?= Text::_('COM_JLMS_ENROLLMENTS_TITLE') ?></a>
        <?php endif; ?>
       </div>
      </th>
      <td class="d-none d-md-table-cell"><?= $this->escape($item->category_title ?? '') ?></td>
      <td class="d-none d-lg-table-cell"><?= $this->escape($item->access_title ?? '') ?></td>
      <td class="d-none d-lg-table-cell"><?= $this->escape($item->author_name ?? Text::_('JNONE')) ?></td>
      <td class="d-none d-md-table-cell"><?= $item->language === '*' ? Text::_('JALL') : $this->escape($item->language_title ?? $item->language) ?></td>
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
 <input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0">
 <?= HTMLHelper::_('form.token') ?>
</form>
