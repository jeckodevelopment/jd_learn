<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('behavior.formvalidator');$i=$this->item;$p=$i['impact'];
?>
<section id="jlms-quiz-extend"><h1><?= Text::_('COM_JLMS_QUIZ_EXTEND_TITLE') ?></h1>
 <h2><?= $this->escape($i['quiz']->course_title) ?> · <?= $this->escape($i['quiz']->lesson_title) ?></h2>
 <p><?= $this->escape($i['enrollment']->learner_name) ?> · #<?= (int)$i['attempt']->attempt_no ?></p>
 <p id="jlms-quiz-extend-policy" class="alert alert-info"><?= Text::_('COM_JLMS_QUIZ_EXTEND_POLICY') ?></p>
 <form id="jlms-quiz-extend-preview-form" method="get" action="<?= Route::_('index.php') ?>">
 <input type="hidden" name="option" value="com_jlms"><input type="hidden" name="view" value="quizextend">
 <input type="hidden" name="id" value="<?= (int)$i['course_id'] ?>"><input type="hidden" name="lesson_id" value="<?= (int)$i['lesson_id'] ?>">
 <input type="hidden" name="enrollment_id" value="<?= (int)$i['enrollment']->id ?>"><input type="hidden" name="attempt_id" value="<?= (int)$i['attempt']->id ?>">
 <label for="jlms-quiz-extend-minutes"><?= Text::_('COM_JLMS_QUIZ_EXTEND_MINUTES') ?></label><input id="jlms-quiz-extend-minutes" type="number" name="minutes" min="1" max="1440" step="1" value="<?= (int)$i['minutes'] ?>" required>
 <button type="submit" class="btn btn-secondary"><?= Text::_('COM_JLMS_QUIZ_REVIEW_PREVIEW') ?></button></form>
 <section id="jlms-quiz-extend-impact"><h3><?= Text::_('COM_JLMS_QUIZ_REVIEW_PREVIEW') ?></h3>
 <p><?= Text::_('COM_JLMS_QUIZ_EXTEND_DEADLINE') ?>: <?= $p['previous_deadline']===null?'—':$this->escape(gmdate('Y-m-d H:i:s',$p['previous_deadline'])) ?> → <?= $p['new_deadline']===null?'—':$this->escape(gmdate('Y-m-d H:i:s',$p['new_deadline'])) ?> UTC</p>
 <p><?= Text::_('COM_JLMS_QUIZ_EXTEND_MINUTES') ?>: <?= (int)$i['minutes'] ?> · <?= Text::_('COM_JLMS_QUIZ_RUNTIME_TIME') ?>: <?= (int)$p['effective_seconds'] ?> s</p>
 <?php if($p['blocked']!==null): ?><p id="jlms-quiz-extend-blocked" class="alert alert-warning"><?= Text::_($p['blocked']) ?></p>
 <?php else: ?><form id="jlms-quiz-extend-form" method="post" class="form-validate" action="<?= Route::_('index.php?option=com_jlms') ?>">
 <?= $this->form->renderFieldset('details') ?><input type="hidden" name="task" value="quizextend.apply"><?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_QUIZ_EXTEND_APPLY') ?></button></form><?php endif; ?>
 </section>
 <table id="jlms-quiz-extend-history" class="table"><caption><?= Text::_('COM_JLMS_QUIZ_EXTEND_HISTORY') ?></caption><thead><tr><th>#</th><th><?= Text::_('COM_JLMS_QUIZ_EXTEND_DEADLINE') ?></th><th><?= Text::_('COM_JLMS_QUIZ_TIME_REASON_LABEL') ?></th><th><?= Text::_('JAUTHOR') ?></th></tr></thead><tbody>
 <?php foreach($i['history']as$h): ?><tr><td><?= (int)$h->revision_no ?></td><td><?= $this->escape($h->previous_deadline) ?> → <?= $this->escape($h->new_deadline) ?> UTC</td><td><?= $this->escape($h->reason) ?></td><td><?= (int)$h->actor_user_id ?></td></tr><?php endforeach; ?></tbody></table>
</section>
