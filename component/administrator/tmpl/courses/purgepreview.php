<?php
declare(strict_types=1);
\defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
$impact = $this->purgeImpact;
?>
<section id="jlms-purge-impact" aria-labelledby="jlms-purge-heading">
 <h2 id="jlms-purge-heading"><?= Text::_('COM_JLMS_PURGE_PREVIEW') ?></h2>
 <p><?= $this->escape($impact['title']) ?> (ID <?= (int) $impact['id'] ?>)</p>
 <p class="alert alert-warning"><?= Text::_('COM_JLMS_PURGE_IRREVERSIBLE') ?></p>
 <table class="table">
 <caption><?= Text::_('COM_JLMS_PURGE_DEPENDENCIES') ?></caption>
 <thead><tr><th scope="col"><?= Text::_('COM_JLMS_PURGE_REFERENCE') ?></th><th scope="col"><?= Text::_('COM_JLMS_PURGE_COUNT') ?></th></tr></thead>
 <tbody>
 <?php foreach ($impact['references'] as $table => $count) : ?>
  <tr><th scope="row"><?= $this->escape($table) ?></th><td><?= (int) $count ?></td></tr>
 <?php endforeach; ?>
  <tr><th scope="row"><?= Text::_('COM_JLMS_PURGE_EDITORIAL_HISTORY') ?></th><td><?= (int) $impact['editorial_history'] ?></td></tr>
 </tbody></table>
 <?php if ($impact['eligible']) : ?>
 <p class="alert alert-warning"><?= Text::_('COM_JLMS_PURGE_ELIGIBLE') ?></p>
 <form action="<?= Route::_('index.php?option=com_jlms&task=course.purge') ?>" method="post">
  <input type="hidden" name="cid[]" value="<?= (int) $impact['id'] ?>">
  <input type="hidden" name="expected_revision" value="<?= (int) $impact['revision'] ?>">
  <input type="hidden" name="confirm_purge" value="1">
        <?= HTMLHelper::_('form.token') ?>
  <button class="btn btn-danger" type="submit"><?= Text::_('COM_JLMS_PURGE_CONFIRM_ACTION') ?></button>
 </form>
 <?php else : ?>
 <p class="alert alert-danger"><?= Text::_('COM_JLMS_PURGE_BLOCKED') ?></p>
 <?php endif; ?>
 <p><a href="<?= Route::_('index.php?option=com_jlms&view=courses&filter[published]=-2') ?>"><?= Text::_('COM_JLMS_BACK_TO_COURSES') ?></a></p>
</section>
