<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$categoryId = $this->inspection ? (int) $this->inspection['category']->id : 0;
?>
<div class="container-fluid">
 <h1><?= Text::_('COM_JLMS_CATEGORY_REASSIGN_TITLE') ?></h1>
 <p><?= Text::_('COM_JLMS_CATEGORY_REASSIGN_INTRO') ?></p>
 <form action="<?= Route::_('index.php?option=com_jlms&view=categoryreassignment', false) ?>" method="get" class="mb-3">
  <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="categoryreassignment">
  <label for="category_id" class="form-label"><?= Text::_('COM_JLMS_CATEGORY_REASSIGN_SOURCE') ?></label>
  <select class="form-select" id="category_id" name="category_id" required>
   <option value=""><?= Text::_('JOPTION_SELECT_CATEGORY') ?></option>
   <?php foreach ($this->categories as $category) : ?>
    <option value="<?= (int) $category->id ?>" <?= (int) $category->id === $categoryId ? 'selected' : '' ?>><?= $this->escape($category->title . ' (' . $category->extension . ')' . ((int) $category->published === -2 ? ' — ' . Text::_('JTRASHED') : '')) ?></option>
   <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-primary mt-2"><?= Text::_('JAPPLY') ?></button>
 </form>
 <?php if ($this->inspection) : ?>
  <h2><?= $this->escape($this->inspection['category']->title) ?></h2>
  <p><?= Text::_('COM_JLMS_CATEGORY_REASSIGN_DELETE_NOTE') ?></p>
  <form action="<?= Route::_('index.php?option=com_jlms&task=categoryreassignment.reassign', false) ?>" method="post">
   <input type="hidden" name="category_id" value="<?= $categoryId ?>">
   <table class="table table-striped">
    <thead><tr><th scope="col"><?= Text::_('COM_JLMS_CATEGORY_REASSIGN_REFERENCE') ?></th><th scope="col"><?= Text::_('COM_JLMS_CATEGORY_REASSIGN_COUNT') ?></th><th scope="col"><?= Text::_('COM_JLMS_CATEGORY_REASSIGN_TARGET') ?></th></tr></thead>
    <tbody>
    <?php foreach ($this->inspection['references'] as $key => $count) : ?>
     <tr><th scope="row"><?= Text::_($key === 'questions' ? 'COM_JLMS_QUESTION_BANK' : 'COM_JLMS_' . strtoupper($key)) ?></th><td><?= (int) $count ?></td><td>
      <?php if ($count > 0) : ?>
       <select class="form-select" name="destination[<?= $this->escape($key) ?>]" required>
        <option value=""><?= Text::_('COM_JLMS_CATEGORY_REASSIGN_CHOOSE_TARGET') ?></option>
        <?php foreach ($this->inspection['targets'][$key] as $target) : ?>
         <option value="<?= (int) $target->id ?>"><?= $this->escape($target->title) ?></option>
        <?php endforeach; ?>
       </select>
      <?php else : ?>—<?php endif; ?>
     </td></tr>
    <?php endforeach; ?>
    </tbody>
   </table>
   <button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_CATEGORY_REASSIGN_SUBMIT') ?></button>
   <?= HTMLHelper::_('form.token') ?>
  </form>
 <?php endif; ?>
</div>
