<?php
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('behavior.formvalidator');$i=$this->item;
$base='index.php?option=com_jlms&view=quizreview&id='.(int)$i['course_id'].'&lesson_id='.(int)$i['lesson_id'];
?>
<section id="jlms-quiz-review"><h1><?= Text::_('COM_JLMS_QUIZ_REVIEW_TITLE') ?></h1>
 <h2><?= $this->escape($i['quiz']->course_title) ?> · <?= $this->escape($i['quiz']->lesson_title) ?></h2>
 <p id="jlms-quiz-review-policy" class="alert alert-info"><?= Text::_('COM_JLMS_QUIZ_REVIEW_POLICY') ?></p>
 <ul id="jlms-quiz-review-enrollments"><?php foreach($i['candidates']as$e): ?><li><a href="<?= Route::_($base.'&enrollment_id='.(int)$e->id) ?>"><?= $this->escape($e->learner_name) ?> #<?= (int)$e->id ?></a></li><?php endforeach; ?></ul>
 <?php if($i['more']): $last=end($i['candidates']); ?><a href="<?= Route::_($base.'&after='.(int)$last->id) ?>"><?= Text::_('JNEXT') ?></a><?php endif; ?>
 <?php if($i['enrollment']): $enr=$base.'&enrollment_id='.(int)$i['enrollment']->id; ?>
 <h2><?= $this->escape($i['enrollment']->learner_name) ?> #<?= (int)$i['enrollment']->id ?></h2>
 <table id="jlms-quiz-review-attempts" class="table"><thead><tr><th><?= Text::_('COM_JLMS_QUIZ_ATTEMPT') ?></th><th><?= Text::_('JSTATUS') ?></th><th><?= Text::_('COM_JLMS_QUIZ_RESULT') ?></th><th><?= Text::_('COM_JLMS_QUIZ_REVIEW_COUNTED') ?></th></tr></thead><tbody>
 <?php foreach($i['attempts']as$a): ?><tr><td><a href="<?= Route::_($enr.'&attempt_id='.(int)$a->id) ?>">#<?= (int)$a->attempt_no ?></a></td><td><?= Text::_(match($a->status){'open'=>'COM_JLMS_QUIZ_STATUS_OPEN','submitted'=>'COM_JLMS_QUIZ_STATUS_SUBMITTED','expired'=>'COM_JLMS_QUIZ_STATUS_EXPIRED',default=>'COM_JLMS_QUIZ_CANCELLED'}) ?></td><td><?= $a->score_points===null?'—':(int)$a->score_points ?>/<?= (int)$a->max_points ?></td><td><?= Text::_((int)$a->counted_for_limit?'JYES':'JNO') ?></td></tr><?php endforeach; ?></tbody></table>
 <?php if($i['more_attempts']): $last=end($i['attempts']); ?><a href="<?= Route::_($enr.'&before='.(int)$last->id) ?>"><?= Text::_('JNEXT') ?></a><?php endif; ?>
 <?php if($i['selected']): $a=$i['selected']; ?>
 <?php if($a->status==='open'): ?><p><a id="jlms-quiz-extend-link" href="<?= Route::_('index.php?option=com_jlms&view=quizextend&id='.(int)$i['course_id'].'&lesson_id='.(int)$i['lesson_id'].'&enrollment_id='.(int)$i['enrollment']->id.'&attempt_id='.(int)$a->id) ?>"><?= Text::_('COM_JLMS_QUIZ_EXTEND_TITLE') ?></a></p><?php endif; ?>
 <?php if($a->status==='invalidated'): ?>
 <section id="jlms-quiz-review-record"><h3><?= Text::_('COM_JLMS_QUIZ_CANCELLED') ?></h3><p><?= $this->escape($a->invalidation_reason) ?></p><p><?= (int)$a->invalidated_by ?> · <?= $this->escape($a->invalidated_at) ?> UTC</p></section>
 <?php else: $p=$i['impact']; ?>
 <section id="jlms-quiz-review-preview"><h3><?= Text::_('COM_JLMS_QUIZ_REVIEW_PREVIEW') ?> #<?= (int)$a->attempt_no ?></h3>
 <p><?= Text::_('COM_JLMS_QUIZ_REVIEW_REFUND') ?>: <a href="<?= Route::_($enr.'&attempt_id='.(int)$a->id.'&return_attempt=0') ?>"><?= Text::_('JNO') ?></a> · <a href="<?= Route::_($enr.'&attempt_id='.(int)$a->id.'&return_attempt=1') ?>"><?= Text::_('JYES') ?></a> · <?= Text::_($i['refund']?'JYES':'JNO') ?></p>
 <dl><dt><?= Text::_('COM_JLMS_QUIZ_REVIEW_LESSON') ?></dt><dd><?= Text::_($p['before_lesson_completed']?'JYES':'JNO') ?> → <?= Text::_($p['after_lesson_completed']?'JYES':'JNO') ?></dd>
 <dt><?= Text::_('COM_JLMS_QUIZ_REVIEW_COURSE') ?></dt><dd><?= (int)$p['before_completed_total'] ?> → <?= (int)$p['after_completed_total'] ?> / <?= (int)$i['enrollment']->required_total ?></dd>
 <dt><?= Text::_('COM_JLMS_QUIZ_REVIEW_COURSE_DATE') ?></dt><dd><?= $this->escape($p['before_course_completed_at']??'—') ?> → <?= $this->escape($p['after_course_completed_at']??'—') ?> UTC</dd>
 <dt><?= Text::_('COM_JLMS_QUIZ_BEST') ?></dt><dd><?= $p['after_best']?(int)$p['after_best']['score_points'].'/'.(int)$p['after_best']['max_points']:'—' ?></dd>
 <dt><?= Text::_('COM_JLMS_QUIZ_REVIEW_USED_AFTER') ?></dt><dd><?= (int)$p['used_after'] ?></dd></dl>
 <?php if($p['blocked']): ?><p class="alert alert-warning" id="jlms-quiz-review-blocked"><?= Text::_('COM_JLMS_QUIZ_REVIEW_BLOCKED') ?> · <?= (int)$p['certificate_count'] ?>/<?= (int)$p['correction_count'] ?></p>
 <?php else: ?><form id="jlms-quiz-review-form" method="post" class="form-validate" action="<?= Route::_('index.php?option=com_jlms') ?>">
 <?= $this->form->renderFieldset('details') ?><input type="hidden" name="task" value="quizreview.cancel"><?= HTMLHelper::_('form.token') ?><button type="submit" class="btn btn-danger"><?= Text::_('COM_JLMS_QUIZ_REVIEW_CANCEL') ?></button></form><?php endif; ?>
 </section><?php endif; ?>
 <?php endif; endif; ?>
</section>
