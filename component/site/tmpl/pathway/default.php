<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Jlms\Component\Jlms\Administrator\Application\PathwayArtwork;

$pathway = $this->item['pathway'];
$cover = PathwayArtwork::fromParams((string) ($pathway->params_json ?? '{}'));
?>
<article id="jlms-pathway" class="jlms-course-page jlms-pathway-page">
 <header id="jlms-pathway-header">
  <h1><?= $this->escape($pathway->title) ?></h1>
  <?php if ($cover['image'] !== '') : ?>
   <div id="jlms-pathway-cover" class="jlms-pathway-cover"><img src="<?= $this->escape(Uri::root() . implode('/', array_map('rawurlencode', explode('/', $cover['image'])))) ?>" alt="<?= $this->escape($cover['alt']) ?>" loading="lazy" decoding="async"></div>
  <?php endif; ?>
  <?php if (trim(strip_tags((string) $pathway->description)) !== '') : ?>
   <div class="jlms-pathway-description content"><?= $pathway->description ?></div>
  <?php endif; ?>
  <p id="jlms-pathway-course-count"><?= Text::sprintf('COM_JLMS_PATHWAY_COURSE_COUNT', (int) $this->item['required_courses']) ?></p>
  <?php if (!$this->isGuest && $this->item['progress'] !== null) : ?>
   <label for="jlms-pathway-progress"><?= Text::_('COM_JLMS_PATHWAY_PROGRESS') ?>: <?= (int) $this->item['progress'] ?>%</label>
   <progress id="jlms-pathway-progress"
    max="100"
    value="<?= (int) $this->item['progress'] ?>"><?= (int) $this->item['progress'] ?>%</progress>
        <?php if ($this->item['has_unavailable_courses']) :
            ?><p id="jlms-pathway-unavailable"><?= Text::_('COM_JLMS_PATHWAY_HAS_UNAVAILABLE') ?></p><?php
        endif; ?>
  <?php endif; ?>
 </header>
 <section id="jlms-pathway-sequence" aria-labelledby="jlms-pathway-sequence-title">
  <h2 id="jlms-pathway-sequence-title"><?= Text::_('COM_JLMS_PATHWAY_COURSES') ?></h2>
  <p class="jlms-pathway-sequence__intro"><?= Text::_('COM_JLMS_PATHWAY_SEQUENCE_INFO') ?></p>
  <ol id="jlms-pathway-courses" class="jlms-pathway-timeline">
  <?php foreach ($this->item['courses'] as $index => $course) : ?>
   <li id="jlms-pathway-course-<?= (int) $index ?>" class="jlms-pathway-timeline__item">
    <div class="jlms-card jlms-pathway-timeline__card">
     <p class="jlms-pathway-timeline__step"><?= Text::sprintf('COM_JLMS_PATHWAY_STEP', (int) $index + 1) ?></p>
        <?php if ($course['id'] !== null) : ?>
      <h3 class="jlms-pathway-timeline__title"><a href="<?= Route::_('index.php?option=com_jlms&view=course&id=' . (int) $course['id']) ?>"><?= $this->escape($course['title']) ?></a></h3>
            <?php if (trim((string) ($course['summary'] ?? '')) !== '') :
                ?><p class="jlms-pathway-timeline__summary"><?= $this->escape(\Joomla\String\StringHelper::substr((string) $course['summary'], 0, 300)) ?></p><?php
            endif; ?>
            <?php if (!$this->isGuest) :
                $stateKey = match ($course['learning_state']) {
                    'completed' => 'COM_JLMS_PATHWAY_COURSE_STATE_COMPLETED',
                    'in_progress' => 'COM_JLMS_PATHWAY_COURSE_STATE_IN_PROGRESS',
                    'not_started' => 'COM_JLMS_PATHWAY_COURSE_STATE_NOT_STARTED',
                    default => 'COM_JLMS_PATHWAY_COURSE_STATE_NOT_ENROLLED',
                };
    ?>
       <p class="jlms-pathway-timeline__state"><?= Text::_($stateKey) ?></p>
                <?php if ($course['access'] === 'available' && $course['learning_state'] !== 'not_enrolled' && $course['percent'] !== null) : ?>
        <label for="jlms-pathway-course-progress-<?= (int) $index ?>"><?= Text::_('COM_JLMS_LEARNING_PROGRESS') ?>: <?= (int) $course['percent'] ?>%</label>
        <progress id="jlms-pathway-course-progress-<?= (int) $index ?>"
    max="100"
    value="<?= (int) $course['percent'] ?>"><?= (int) $course['percent'] ?>%</progress>
                <?php elseif ($course['access'] === 'unavailable' && !empty($course['delivery_suspended'])) : ?>
        <p><?= Text::_('COM_JLMS_COURSE_DELIVERY_SUSPENDED') ?></p>
                <?php elseif ($course['access'] !== 'available') : ?>
        <p><?= Text::_('COM_JLMS_PATHWAY_COURSE_RESTRICTED') ?></p>
                <?php endif; ?>
            <?php endif; ?>
        <?php else :
            ?><p><?= Text::_('COM_JLMS_PATHWAY_COURSE_UNAVAILABLE') ?></p><?php
        endif; ?>
    </div>
   </li>
  <?php endforeach; ?>
  </ol>
  <?php if ($this->item['required_courses'] === 0) :
        ?><p id="jlms-pathway-empty"><?= Text::_('COM_JLMS_PATHWAY_NO_COURSES') ?></p><?php
  endif; ?>
 </section>
 <a id="jlms-pathway-catalog"
    href="<?= Route::_('index.php?option=com_jlms&view=pathways') ?>"><?= Text::_('COM_JLMS_PATHWAYS') ?></a>
</article>
