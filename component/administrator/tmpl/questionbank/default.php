<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$this->getDocument()->getWebAssetManager()->useScript('table.columns')->useScript('multiselect');
?>
<div id="j-main-container" class="j-main-container">
 <p class="alert alert-info"><?= Text::_('COM_JLMS_QUIZ_AUTHORING_NOTICE') ?></p>
 <form action="<?= Route::_('index.php') ?>" method="get" id="adminForm" name="adminForm">
  <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="questionbank">
  <div class="js-stools-container-bar row g-2 align-items-end mb-3">
   <div class="col-md-4"><label class="form-label" for="filter_search"><?= Text::_('COM_JLMS_QUESTION_SEARCH') ?></label><input class="form-control" type="search" id="filter_search" name="filter[search]" value="<?= $this->escape((string)$this->state->get('filter.search','')) ?>" maxlength="200"></div>
   <div class="col-md-2"><label class="form-label" for="filter_catid"><?= Text::_('JCATEGORY') ?></label><select class="form-select" id="filter_catid" name="filter[catid]"><option value=""><?= Text::_('JOPTION_SELECT_CATEGORY') ?></option><?php foreach ($this->questionCategories as $category) : ?><option value="<?= (int)$category->id ?>" <?= (string)$this->state->get('filter.catid','')===(string)$category->id?'selected':'' ?>><?= $this->escape($category->title) ?></option><?php endforeach; ?></select></div>
   <div class="col-md-2"><label class="form-label" for="filter_published"><?= Text::_('JSTATUS') ?></label><select class="form-select" id="filter_published" name="filter[published]"><option value=""><?= Text::_('JOPTION_SELECT_PUBLISHED') ?></option><option value="1" <?= (string)$this->state->get('filter.published','')==='1'?'selected':'' ?>><?= Text::_('JPUBLISHED') ?></option><option value="0" <?= (string)$this->state->get('filter.published','')==='0'?'selected':'' ?>><?= Text::_('JUNPUBLISHED') ?></option></select></div>
   <div class="col-md-2"><label class="form-label" for="filter_language"><?= Text::_('JGRID_HEADING_LANGUAGE') ?></label><select class="form-select" id="filter_language" name="filter[language]"><option value=""><?= Text::_('JOPTION_SELECT_LANGUAGE') ?></option><option value="*" <?= (string)$this->state->get('filter.language','')==='*'?'selected':'' ?>><?= Text::_('JALL') ?></option><option value="it-IT" <?= (string)$this->state->get('filter.language','')==='it-IT'?'selected':'' ?>>Italiano</option><option value="en-GB" <?= (string)$this->state->get('filter.language','')==='en-GB'?'selected':'' ?>>English</option></select></div>
   <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary" type="submit"><?= Text::_('JSEARCH_FILTER_SUBMIT') ?></button><a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_jlms&view=questionbank') ?>"><?= Text::_('JSEARCH_FILTER_CLEAR') ?></a></div>
  </div>
 </form>
 <?php if (!$this->bank['items']) : ?>
  <p class="alert alert-info"><?= Text::_('JGLOBAL_NO_MATCHING_RESULTS') ?></p>
 <?php else : ?>
 <table class="table itemList" id="jlms-question-list">
  <caption class="visually-hidden"><?= Text::_('COM_JLMS_QUESTION_BANK') ?></caption>
  <thead><tr>
   <th scope="col"><?= HTMLHelper::_('searchtools.sort','COM_JLMS_QUESTION_PROMPT','r.prompt',$this->state->get('list.direction','ASC'),$this->state->get('list.ordering','r.prompt')) ?></th>
   <th scope="col" class="d-none d-md-table-cell"><?= HTMLHelper::_('searchtools.sort','JCATEGORY','category_title',$this->state->get('list.direction','ASC'),$this->state->get('list.ordering','r.prompt')) ?></th>
   <th scope="col" class="d-none d-sm-table-cell"><?= HTMLHelper::_('searchtools.sort','JSTATUS','q.state',$this->state->get('list.direction','ASC'),$this->state->get('list.ordering','r.prompt')) ?></th>
   <th scope="col" class="d-none d-lg-table-cell"><?= HTMLHelper::_('searchtools.sort','JGRID_HEADING_LANGUAGE','q.language',$this->state->get('list.direction','ASC'),$this->state->get('list.ordering','r.prompt')) ?></th>
   <th scope="col" class="d-none d-lg-table-cell"><?= HTMLHelper::_('searchtools.sort','JGRID_HEADING_ID','q.id',$this->state->get('list.direction','ASC'),$this->state->get('list.ordering','r.prompt')) ?></th>
   <th scope="col" class="text-end"><?= Text::_('COM_JLMS_QUESTION_ACTIONS') ?></th>
  </tr></thead>
  <tbody>
  <?php foreach ($this->bank['items'] as $row) : $id=(int)$row->id; $inUse=(bool)$row->in_use; ?>
   <tr data-question-id="<?= $id ?>">
    <th scope="row"><a href="<?= Route::_('index.php?option=com_jlms&view=question&id='.$id) ?>"><?= $this->escape(\Joomla\String\StringHelper::substr(trim(strip_tags($row->prompt)),0,160)) ?></a><div class="small text-body-secondary"><?= $this->escape($row->kind) ?> · v<?= (int)$row->revision ?></div></th>
    <td class="d-none d-md-table-cell"><?= $this->escape($row->category_title) ?></td>
    <td class="d-none d-sm-table-cell"><span class="badge <?= (int)$row->state===1?'bg-success':'bg-secondary' ?>"><?= Text::_((int)$row->state?'JPUBLISHED':'JUNPUBLISHED') ?></span></td>
    <td class="d-none d-lg-table-cell"><?= $row->language==='*'?Text::_('JALL'):$this->escape($row->language) ?></td>
    <td class="d-none d-lg-table-cell"><?= (int)$row->revision_id ?> <span class="small text-body-secondary">(#<?= $id ?>)</span></td>
    <td class="text-end text-nowrap">
     <?php if ($row->can_duplicate) : ?><form method="post" action="<?= Route::_('index.php?option=com_jlms') ?>" class="d-inline"><input type="hidden" name="task" value="questionbank.duplicate"><input type="hidden" name="id" value="<?= $id ?>"><?= HTMLHelper::_('form.token') ?><button class="btn btn-sm btn-secondary" type="submit"><?= Text::_('COM_JLMS_QUESTION_DUPLICATE') ?></button></form><?php endif; ?>
     <?php if ($inUse) : ?><span class="badge bg-warning text-dark" title="<?= $this->escape(Text::_('COM_JLMS_QUESTION_IN_USE')) ?>"><?= Text::_('COM_JLMS_QUESTION_IN_USE_BRIEF') ?></span><?php endif; ?>
     <?php if ($row->can_delete) : ?><form method="post" action="<?= Route::_('index.php?option=com_jlms') ?>" class="d-inline"><input type="hidden" name="task" value="questionbank.delete"><input type="hidden" name="id" value="<?= $id ?>"><?= HTMLHelper::_('form.token') ?><button class="btn btn-sm btn-danger" type="submit" <?= $inUse?'disabled title="'.$this->escape(Text::_('COM_JLMS_QUESTION_IN_USE')).'"':'' ?> <?= $inUse?'':'data-confirm="'.$this->escape(Text::_('COM_JLMS_QUESTION_DELETE_CONFIRM')).'" onclick="return window.confirm(this.dataset.confirm)"' ?>><?= Text::_('JTOOLBAR_DELETE') ?></button></form><?php endif; ?>
    </td>
   </tr>
  <?php endforeach; ?>
  </tbody>
 </table>
 <?php endif; ?>
 <div class="d-flex justify-content-between align-items-center mt-3"><span><?= Text::sprintf('COM_JLMS_QUESTION_COUNT',(int)$this->bank['total']) ?></span><div><?= $this->bank['pagination']->getPagesLinks() ?></div></div>
</div>
