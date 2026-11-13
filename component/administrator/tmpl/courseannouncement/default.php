<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$courseId = (int) $this->preview['course_id'];
?>
<div class="container-fluid">
 <p><a class="btn btn-secondary" href="<?= Route::_('index.php?option=com_jlms&view=course&layout=edit&id=' . $courseId) ?>"><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_BACK') ?></a></p>
 <h1><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_TITLE') ?>: <?= htmlspecialchars((string) $this->preview['course_title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
 <?php if (empty($this->preview['eligible'])) : ?>
  <div class="alert alert-warning" role="status"><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_NOT_ELIGIBLE') ?></div>
 <?php else : ?>
  <p><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_HELP') ?></p>
  <div class="alert alert-info" role="status"><?= Text::sprintf('COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_RECIPIENTS', (int) $this->preview['eligible_count']) ?></div>
  <section class="card mb-3" aria-labelledby="jlms-announcement-subject">
   <div class="card-header"><strong id="jlms-announcement-subject"><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_SUBJECT') ?></strong></div>
   <div class="card-body"><?= htmlspecialchars((string) $this->preview['subject'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
  </section>
  <section class="card mb-3" aria-labelledby="jlms-announcement-html">
   <div class="card-header"><strong id="jlms-announcement-html"><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_HTML') ?></strong></div>
   <div class="card-body"><?= $this->preview['body_html'] ?></div>
  </section>
  <details class="mb-3">
   <summary><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_PREVIEW_TEXT') ?></summary>
   <pre class="p-3 border"><?= htmlspecialchars((string) $this->preview['body_text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
  </details>
  <?php if (!empty($this->preview['already_notified'])) : ?>
   <div class="alert alert-success" role="status"><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_ALREADY_QUEUED') ?></div>
  <?php elseif ((int) $this->preview['eligible_count'] > 0) : ?>
   <p><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_QUEUE_HELP') ?></p>
   <form action="<?= Route::_('index.php?option=com_jlms&task=courseannouncement.notify') ?>" method="post" class="card card-body">
    <input type="hidden" name="id" value="<?= $courseId ?>">
    <div class="form-check mb-3">
     <input class="form-check-input" type="checkbox" name="confirm" id="jlms-announcement-confirm" value="1" required>
     <label class="form-check-label" for="jlms-announcement-confirm"><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_CONFIRM') ?></label>
    </div>
    <button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_CONFIRM_BUTTON') ?></button>
    <?= HTMLHelper::_('form.token') ?>
   </form>
  <?php else : ?>
   <div class="alert alert-secondary" role="note"><?= Text::_('COM_JLMS_COURSE_ANNOUNCEMENT_NO_RECIPIENTS') ?></div>
  <?php endif; ?>
 <?php endif; ?>
</div>
