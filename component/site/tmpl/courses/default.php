<?php

declare(strict_types=1);

\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Jlms\Component\Jlms\Administrator\Application\CoursePresentation;
?>
<section id="jlms-courses" class="jlms-catalog">
 <header id="jlms-catalog-header"><h1><?= Text::_('COM_JLMS_COURSES') ?></h1><a id="jlms-catalog-categories" href="<?= Route::_('index.php?option=com_jlms&view=categories') ?>"><?= Text::_('COM_JLMS_CATEGORIES') ?></a> <a id="jlms-catalog-pathways" href="<?= Route::_('index.php?option=com_jlms&view=pathways') ?>"><?= Text::_('COM_JLMS_PATHWAYS') ?></a></header>
 <?php if ($this->loadFailed) : ?>
 <div id="jlms-catalog-load-error" class="alert alert-warning" role="alert">
  <p><?= Text::_('COM_JLMS_CATALOG_LOAD_ERROR') ?></p>
  <a id="jlms-catalog-retry" href="<?= $this->escape(Uri::getInstance()->toString(['path', 'query'])) ?>"><?= Text::_('COM_JLMS_CATALOG_RETRY') ?></a>
 </div>
 <?php else : ?>
 <?php if ($this->showFilters) : ?>
 <form id="jlms-catalog-filters" method="get" action="<?= Route::_('index.php?option=com_jlms&view=courses' . ($this->categoryId ? '&catid=' . $this->categoryId : '')) ?>">
  <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="courses">
  <?php if ($this->categoryId) :
?><input type="hidden" name="catid" value="<?= $this->categoryId ?>"><?php
  endif; ?>
  <?php if ($this->showCategory) : ?>
  <label for="jlms-catalog-category"><?= Text::_('COM_JLMS_CATALOG_CATEGORY') ?></label>
  <select id="jlms-catalog-category" name="filter[category_id]">
   <option value="0"><?= Text::_('COM_JLMS_CATALOG_ALL_CATEGORIES') ?></option>
   <?php foreach ($this->categories as $category) : ?>
   <option value="<?= (int) $category->id ?>"<?= $this->categoryChoice === (int) $category->id ? ' selected' : '' ?>><?= $this->escape($category->title) ?></option>
   <?php endforeach; ?>
  </select>
  <?php endif; ?>
  <label for="jlms-catalog-search"><?= Text::_('COM_JLMS_CATALOG_SEARCH') ?></label>
  <input id="jlms-catalog-search" type="search" name="filter[search]" value="<?= $this->escape($this->search) ?>" maxlength="100">
  <?php if ($this->showOrder) : ?>
  <label for="jlms-catalog-order"><?= Text::_('COM_JLMS_CATALOG_ORDER') ?></label>
  <select id="jlms-catalog-order" name="filter[order]"><option value="editorial"<?= $this->order === 'editorial' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_ORDER_EDITORIAL') ?></option><option value="recent"<?= $this->order === 'recent' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_ORDER_RECENT') ?></option><option value="title"<?= $this->order === 'title' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_ORDER_TITLE') ?></option><option value="duration"<?= $this->order === 'duration' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_ORDER_DURATION') ?></option><option value="published"<?= $this->order === 'published' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_ORDER_PUBLISHED') ?></option></select>
  <?php endif; ?>
  <label for="jlms-catalog-tag"><?= Text::_('COM_JLMS_CATALOG_TAG') ?></label>
  <select id="jlms-catalog-tag" name="filter[tag_id]"><option value="0"><?= Text::_('COM_JLMS_CATALOG_ALL_TAGS') ?></option><?php foreach ($this->tags as $tag) :
?><option value="<?= (int) $tag->id ?>"<?= $this->tagId === (int) $tag->id ? ' selected' : '' ?>><?= $this->escape($tag->title) ?></option><?php
                                                                        endforeach; ?></select>
<label for="jlms-catalog-difficulty"><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY') ?></label>
  <select id="jlms-catalog-difficulty" name="filter[difficulty]"><option value=""><?= Text::_('COM_JLMS_CATALOG_ANY_DIFFICULTY') ?></option><option value="beginner"<?= $this->difficulty === 'beginner' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY_BEGINNER') ?></option><option value="intermediate"<?= $this->difficulty === 'intermediate' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY_INTERMEDIATE') ?></option><option value="advanced"<?= $this->difficulty === 'advanced' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DIFFICULTY_ADVANCED') ?></option></select>
  <label for="jlms-catalog-duration"><?= Text::_('COM_JLMS_CATALOG_DURATION') ?></label>
  <select id="jlms-catalog-duration" name="filter[duration]"><option value=""><?= Text::_('COM_JLMS_CATALOG_ANY_DURATION') ?></option><option value="under-30"<?= $this->duration === 'under-30' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_UNDER_30') ?></option><option value="30-60"<?= $this->duration === '30-60' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_30_60') ?></option><option value="61-120"<?= $this->duration === '61-120' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_61_120') ?></option><option value="over-120"<?= $this->duration === 'over-120' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_DURATION_OVER_120') ?></option></select>
  <label for="jlms-catalog-available"><?= Text::_('COM_JLMS_CATALOG_AVAILABLE_ONLY') ?></label>
  <input id="jlms-catalog-available" type="checkbox" name="filter[available]" value="1"<?= $this->available === '1' ? ' checked' : '' ?>>
  <label for="jlms-catalog-layout"><?= Text::_('COM_JLMS_CATALOG_LAYOUT') ?></label>
  <select id="jlms-catalog-layout" name="filter[layout]"><option value="grid"<?= $this->layout === 'grid' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_LAYOUT_GRID') ?></option><option value="list"<?= $this->layout === 'list' ? ' selected' : '' ?>><?= Text::_('COM_JLMS_CATALOG_LAYOUT_LIST') ?></option></select>
  <label for="jlms-catalog-teacher"><?= Text::_('COM_JLMS_CATALOG_TEACHER') ?></label>
  <select id="jlms-catalog-teacher" name="filter[teacher_id]"><option value="0"><?= Text::_('COM_JLMS_CATALOG_ANY_TEACHER') ?></option><?php foreach ($this->teachers as $teacher) :
?><option value="<?= (int) $teacher->id ?>"<?= $this->teacherId === (int) $teacher->id ? ' selected' : '' ?>><?= $this->escape($teacher->title) ?></option><?php
                                                                                endforeach; ?></select>
  <label for="jlms-catalog-pathway"><?= Text::_('COM_JLMS_CATALOG_PATHWAY') ?></label>
  <select id="jlms-catalog-pathway" name="filter[pathway_id]"><option value="0"><?= Text::_('COM_JLMS_CATALOG_ANY_PATHWAY') ?></option><?php foreach ($this->pathways as $pathway) :
?><option value="<?= (int) $pathway->id ?>"<?= $this->pathwayId === (int) $pathway->id ? ' selected' : '' ?>><?= $this->escape($pathway->title) ?></option><?php
                                                                                endforeach; ?></select>
  <button type="submit"><?= Text::_('COM_JLMS_CATALOG_APPLY') ?></button>
 </form>
 <?php endif; ?>
 <?php if ($this->categoryId) :
?><p id="jlms-category-filter"><?= Text::_('COM_JLMS_CATEGORY_FILTER_ACTIVE') ?> <a href="<?= Route::_('index.php?option=com_jlms&view=courses') ?>"><?= Text::_('COM_JLMS_ALL_COURSES') ?></a></p><?php
 endif; ?>
 <div id="jlms-course-grid" class="jlms-course-grid<?= $this->layout === 'list' ? ' jlms-course-grid--list' : '' ?>" data-layout="<?= $this->escape($this->layout) ?>">
 <?php foreach ($this->items as $item) :
  $presentation = [];
  try {
      $presentation = CoursePresentation::fromSnapshot((string) ($item->catalog_presentation_json ?? ''));
  } catch (\InvalidArgumentException | \JsonException $exception) {
      // An invalid historical snapshot cannot break the whole catalogue.
  }
  $cover = (string) ($presentation['image'] ?? '');
  $duration = (int) ($presentation['estimated_minutes'] ?? 0);
  $difficulty = (string) ($presentation['difficulty'] ?? '');
  $difficultyKey = [
      'beginner' => 'COM_JLMS_COURSE_BEGINNER',
      'intermediate' => 'COM_JLMS_COURSE_INTERMEDIATE',
      'advanced' => 'COM_JLMS_COURSE_ADVANCED',
  ][$difficulty] ?? '';
 ?>
  <article id="jlms-course-card-<?= (int) $item->id ?>" class="jlms-card jlms-course-card" data-course-id="<?= (int) $item->id ?>">
   <?php if ($this->showCover) : ?>
    <div class="jlms-course-card__cover">
     <?php if ($cover !== '') : ?>
      <img src="<?= $this->escape(Uri::root() . implode('/', array_map('rawurlencode', explode('/', $cover)))) ?>" alt="<?= $this->escape((string) ($presentation['image_alt'] ?? '')) ?>" loading="lazy" decoding="async">
     <?php else :
?><span class="jlms-course-card__cover-placeholder" aria-hidden="true"></span><?php
     endif; ?>
    </div>
   <?php endif; ?>
   <div class="jlms-course-card__content">
    <a class="jlms-course-card__category" href="<?= Route::_('index.php?option=com_jlms&view=category&id=' . (int) $item->catid) ?>"><?= $this->escape($item->category_title) ?></a>
    <h2 class="jlms-course-card__title"><a href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $item->id) ?>"><?= $this->escape($item->title) ?></a></h2>
    <?php if ($this->showSummary && trim((string) $item->summary) !== '') : ?>
     <p class="jlms-course-card__summary"><?= $this->escape(\Joomla\String\StringHelper::substr((string) $item->summary, 0, 300)) ?></p>
    <?php endif; ?>
    <?php if (!empty($this->cardTeachers[(int) $item->id])) : ?>
     <p class="jlms-course-card__teachers"><span><?= Text::_('COM_JLMS_TEACHERS') ?>:</span>
      <?php foreach ($this->cardTeachers[(int) $item->id] as $teacher) : ?>
       <a href="<?= Route::_('index.php?option=com_jlms&view=teacher&id=' . (int) $teacher['id']) ?>"><?= $this->escape((string) $teacher['title']) ?></a>
      <?php endforeach; ?>
     </p>
    <?php endif; ?>
    <?php $enrollment = $this->cardEnrollmentStates[(int) $item->id] ?? null; ?>
    <?php if ($enrollment !== null) :
     $learningLabel = [
         'not_started' => 'COM_JLMS_DASHBOARD_NOT_STARTED',
         'in_progress' => 'COM_JLMS_DASHBOARD_IN_PROGRESS',
         'completed' => 'COM_JLMS_DASHBOARD_COMPLETED',
     ][$enrollment['learning_state']] ?? 'COM_JLMS_DASHBOARD_NOT_STARTED';
     $accessLabel = [
         'language' => 'COM_JLMS_DASHBOARD_ACCESS_LANGUAGE',
         'unavailable' => 'COM_JLMS_DASHBOARD_ACCESS_UNAVAILABLE',
         'access' => 'COM_JLMS_DASHBOARD_ACCESS_ACCESS',
         'suspended' => 'COM_JLMS_DASHBOARD_ACCESS_SUSPENDED',
         'expired' => 'COM_JLMS_DASHBOARD_ACCESS_EXPIRED',
         'cancelled' => 'COM_JLMS_DASHBOARD_ACCESS_CANCELLED',
         'pending' => 'COM_JLMS_DASHBOARD_ACCESS_PENDING',
         'not_open' => 'COM_JLMS_DASHBOARD_ACCESS_NOT_OPEN',
     ][$enrollment['access']] ?? 'COM_JLMS_DASHBOARD_ACCESS_UNAVAILABLE';
    ?>
     <p class="jlms-course-card__learning-state"><?= Text::_($learningLabel) ?><?php if ($enrollment['access'] !== 'available') :
?> · <?= Text::_($accessLabel) ?><?php
                                                 endif; ?></p>
    <?php endif; ?>
    <?php if ($enrollment === null && !empty($this->cardPendingApprovals[(int) $item->id])) : ?>
     <p class="jlms-course-card__pending" role="status"><?= Text::_('COM_JLMS_COURSE_APPROVAL_PENDING') ?></p>
    <?php endif; ?>
    <?php if ($enrollment === null && empty($this->cardPendingApprovals[(int) $item->id]) && (int) $item->admissions_open === 1 && (int) $item->delivery_enabled === 1 && in_array((string) $item->enrollment_mode, ['auto', 'approval'], true)) : ?>
     <p class="jlms-course-card__admission"><?= Text::_($item->enrollment_mode === 'approval' ? 'COM_JLMS_CATALOG_APPROVAL_REQUIRED' : 'COM_JLMS_CATALOG_AUTO_ENROLLMENT') ?></p>
    <?php endif; ?>
    <?php if ($duration > 0 || $difficultyKey !== '') : ?>
     <p class="jlms-course-card__meta">
      <?php if ($duration > 0) :
?><?= Text::sprintf('COM_JLMS_COURSE_DURATION_VALUE', $duration) ?><?php
      endif; ?>
      <?php if ($duration > 0 && $difficultyKey !== '') :
?> · <?php
      endif; ?>
      <?php if ($difficultyKey !== '') :
?><?= Text::_($difficultyKey) ?><?php
      endif; ?>
     </p>
    <?php endif; ?>
    <?php if ($enrollment !== null && $enrollment['access'] === 'available' && (int) $item->delivery_enabled === 1) : ?>
     <a class="jlms-course-card__action jlms-course-card__resume" href="<?= Route::_('index.php?option=com_jlms&task=enrollment.resume&id=' . (int) $enrollment['id']) ?>"><?= Text::_($enrollment['completed'] ? 'COM_JLMS_DASHBOARD_REVIEW' : 'COM_JLMS_DASHBOARD_RESUME') ?></a>
    <?php else : ?>
    <a class="jlms-course-card__action" href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $item->id) ?>"><?= Text::_('COM_JLMS_CATALOG_VIEW_COURSE') ?></a>
    <?php endif; ?>
   </div>
  </article>
 <?php endforeach; ?>
 <?php if (!$this->items) : ?>
  <p id="jlms-catalog-empty"><?= Text::_('COM_JLMS_NO_PUBLISHED_COURSES') ?></p>
  <?php if ($this->showFilters && ($this->search !== '' || $this->categoryChoice > 0 || $this->tagId > 0 || $this->difficulty !== '' || $this->duration !== '' || $this->available === '1' || $this->teacherId > 0 || $this->pathwayId > 0)) : ?>
   <p id="jlms-catalog-clear-filters"><a href="<?= Route::_('index.php?option=com_jlms&view=courses' . ($this->categoryId ? '&catid=' . (int) $this->categoryId : '')) ?>"><?= Text::_('COM_JLMS_CATALOG_CLEAR_FILTERS') ?></a></p>
  <?php endif; ?>
 <?php endif; ?>
 </div>
 <nav id="jlms-catalog-pagination" aria-label="<?= Text::_('COM_JLMS_PAGINATION') ?>"><?= $this->pagination->getPagesLinks() ?></nav>
 <?php endif; ?>
</section>
