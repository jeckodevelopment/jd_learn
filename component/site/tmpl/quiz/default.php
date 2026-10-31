<?php

declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
$i=$this->item;$attempt=$i['attempt'];$open=$attempt&&$attempt['state']==='open';
?>
<section id="jlms-quiz" class="jlms-quiz" data-enrollment-id="<?= (int)$i['enrollment_id'] ?>">
 <header id="jlms-quiz-header"><h1><?= $this->escape($i['title']) ?></h1></header>
 <section id="jlms-quiz-rules">
  <p><?= Text::_('COM_JLMS_QUIZ_RULES') ?>: <?= (int)$i['question_count'] ?> · <?= Text::_('COM_JLMS_QUIZ_THRESHOLD') ?> <?= $this->escape((string)($i['threshold']/100)) ?>% · <?= Text::_('COM_JLMS_QUIZ_RUNTIME_TIME') ?> <?= (int)$i['time_seconds'] ?> s</p>
  <p><?= Text::_('COM_JLMS_QUIZ_ATTEMPTS') ?>: <?= (int)$i['used_attempts'] ?> / <?= $i['remaining_attempts']===null?Text::_('COM_JLMS_QUIZ_UNLIMITED'):(int)($i['used_attempts']+$i['remaining_attempts']) ?></p>
  <p><?= Text::_('COM_JLMS_QUIZ_SAVE_NOTICE') ?></p>
 </section>
 <p id="jlms-quiz-availability"><?= Text::_('COM_JLMS_QUIZ_NEXT') ?>: <?= $this->escape(gmdate('Y-m-d H:i:s',$i['next_attempt_at'])) ?> UTC · <?= Text::_('COM_JLMS_QUIZ_FEEDBACK') ?>: <?= Text::_(match($i['feedback_policy']){'never'=>'COM_JLMS_QUIZ_FEEDBACK_NEVER','after_submit'=>'COM_JLMS_QUIZ_FEEDBACK_PRACTICE',default=>'COM_JLMS_QUIZ_FEEDBACK_DEFAULT'}) ?></p>
 <p id="jlms-quiz-time-allowance"><?= Text::_('COM_JLMS_QUIZ_TIME_EXTRA') ?>: <?= (int)$i['extra_time_seconds'] ?> s · <?= Text::_('COM_JLMS_QUIZ_TIME_NEXT') ?>: <?= (int)$i['next_time_seconds'] ?> s</p>
 <p id="jlms-quiz-warning" role="alert" aria-live="assertive"></p>
 <p id="jlms-quiz-status" role="status" aria-live="polite"></p>
 <p id="jlms-quiz-timer" role="timer" aria-live="off"></p>
 <noscript><p><?= Text::_('COM_JLMS_QUIZ_JS_REQUIRED') ?></p></noscript>
 <?php if($attempt&&!$open): ?>
 <section id="jlms-quiz-result"><h2><?= Text::_('COM_JLMS_QUIZ_RESULT') ?></h2><p><?= Text::_($attempt['state']==='invalidated'?'COM_JLMS_QUIZ_CANCELLED':($attempt['passed']?'COM_JLMS_QUIZ_PASSED':'COM_JLMS_QUIZ_FAILED')) ?>: <?= $attempt['score_points']===null?'—':(int)$attempt['score_points'] ?>/<?= (int)$attempt['max_points'] ?></p>
 <?php if($attempt['state']==='invalidated'): ?><p id="jlms-quiz-invalidated"><?= Text::_('COM_JLMS_QUIZ_CANCELLED_NOTICE') ?></p><?php endif; ?>
 <?php if(!$i['feedback_available']): ?><p><?= Text::_('COM_JLMS_QUIZ_FEEDBACK_LOCKED') ?></p><?php endif; ?></section>
 <?php endif; ?>
 <form id="jlms-quiz-form">
 <div id="jlms-quiz-questions">
 <?php foreach($i['questions']as$index=>$q): ?>
 <fieldset id="jlms-quiz-question-<?= (int)$q['id'] ?>" class="jlms-quiz-question" data-question-id="<?= (int)$q['id'] ?>"<?= $open?'':' disabled' ?>>
  <legend><?= Text::_('COM_JLMS_QUIZ_QUESTION') ?> <?= (int)($index+1) ?> (<?= (int)$q['max_points'] ?>)</legend>
  <div class="jlms-quiz-question__prompt"><?= $q['prompt'] /* Sanitised core HTML at attempt creation. */ ?></div>
  <?php foreach($q['options']as$o): $control='jlms-quiz-option-'.(int)$q['id'].'-'.$o['id']; ?>
  <div class="jlms-quiz-question__option"><input id="<?= $this->escape($control) ?>" type="<?= $q['kind']==='multiple'?'checkbox':'radio' ?>" name="answer_<?= (int)$q['id'] ?>[]" value="<?= $this->escape($o['id']) ?>"<?= in_array($o['id'],$q['selected'],true)?' checked':'' ?>><label for="<?= $this->escape($control) ?>"><?= $this->escape($o['label']) ?></label></div>
  <?php endforeach; ?>
  <?php if($open): ?><button type="button" class="btn btn-link jlms-quiz-clear"><?= Text::_('COM_JLMS_QUIZ_CLEAR') ?></button><?php endif; ?>
  <?php if($i['feedback_available']): ?><div class="jlms-quiz-question__feedback"><p><?= Text::_('COM_JLMS_QUIZ_CORRECT') ?>: <?php $correctLabels=[];foreach($q['options']as$o)if(in_array($o['id'],$q['correct'],true))$correctLabels[]=$o['label'];echo $this->escape(implode(', ',$correctLabels)); ?></p><?= $q['explanation'] /* Sanitised core HTML. */ ?></div><?php endif; ?>
 </fieldset>
 <?php endforeach; ?>
 </div>
 <div id="jlms-quiz-actions">
 <?php if($open): ?><button id="jlms-quiz-save" class="btn btn-secondary" type="button"><?= Text::_('COM_JLMS_QUIZ_SAVE') ?></button><button id="jlms-quiz-submit" class="btn btn-primary" type="submit"><?= Text::_('COM_JLMS_QUIZ_SUBMIT') ?></button>
 <?php elseif($i['can_start']): ?><button id="jlms-quiz-start" class="btn btn-primary" type="button"><?= Text::_('COM_JLMS_QUIZ_START') ?></button>
 <?php else: ?><p><?= Text::_('COM_JLMS_QUIZ_UNAVAILABLE') ?></p><?php endif; ?>
 <?php if($i['active_attempt_id']&&(!$attempt||$i['active_attempt_id']!==$attempt['id'])): ?><a href="<?= Route::_('index.php?option=com_jlms&view=quiz&id='.(int)$i['enrollment_id'].'&lesson_id='.(int)$i['lesson_id'].'&attempt_id='.(int)$i['active_attempt_id']) ?>"><?= Text::_('COM_JLMS_QUIZ_RESUME') ?></a><?php endif; ?>
 </div></form>
 <section id="jlms-quiz-history"><h2><?= Text::_('COM_JLMS_QUIZ_HISTORY') ?></h2>
 <?php if($i['best']): ?><p><?= Text::_('COM_JLMS_QUIZ_BEST') ?>: <?= (int)$i['best']['score_points'] ?>/<?= (int)$i['best']['max_points'] ?></p><?php endif; ?>
 <ol><?php foreach($i['history']as$entry): ?><li><a href="<?= Route::_('index.php?option=com_jlms&view=quiz&id='.(int)$i['enrollment_id'].'&lesson_id='.(int)$i['lesson_id'].'&attempt_id='.(int)$entry['id']) ?>"><?= Text::_('COM_JLMS_QUIZ_ATTEMPT') ?> <?= (int)$entry['attempt_no'] ?></a><?php if($entry['status']==='invalidated'): ?> · <?= Text::_('COM_JLMS_QUIZ_CANCELLED') ?><?php endif; ?></li><?php endforeach; ?></ol></section>
 <a id="jlms-quiz-back" href="<?= Route::_('index.php?option=com_jlms&view=classroom&id='.(int)$i['enrollment_id'].'&lesson_id='.(int)$i['lesson_id']) ?>"><?= Text::_('COM_JLMS_QUIZ_CLASSROOM') ?></a>
</section>
