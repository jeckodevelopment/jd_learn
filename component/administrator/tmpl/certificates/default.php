<?php

declare(strict_types=1);
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$this->getDocument()->getWebAssetManager()->useScript('table.columns');
$order = $this->escape((string) $this->state->get('list.ordering', 'd.issued_at'));
$direction = $this->escape((string) $this->state->get('list.direction', 'DESC'));
?>
<form action="<?= Route::_('index.php?option=com_jlms&view=certificates') ?>" method="post" id="adminForm" name="adminForm">
 <div id="j-main-container" class="j-main-container">
  <section id="jlms-admin-certificates" aria-label="<?= Text::_('COM_JLMS_CERTIFICATES') ?>">
   <p><?= Text::_('COM_JLMS_CERTIFICATE_LIST_INTRO') ?></p>
   <?= LayoutHelper::render('joomla.searchtools.default', ['view' => $this]) ?>
   <?php if (!$this->items) : ?>
    <p id="jlms-certificates-empty" class="alert alert-info"><?= Text::_('JGLOBAL_NO_MATCHING_RESULTS') ?></p>
   <?php else : ?>
    <table class="table itemList" id="jlms-certificates-list">
     <caption class="visually-hidden"><?= Text::_('COM_JLMS_CERTIFICATES') ?></caption>
     <thead><tr>
      <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'COM_JLMS_CERTIFICATE_STUDENT', 'student_name', $direction, $order) ?></th>
      <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'COM_JLMS_CERTIFICATE_COURSE', 'course_title', $direction, $order) ?></th>
      <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'COM_JLMS_CERTIFICATE_CODE', 'd.serial', $direction, $order) ?></th>
      <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'JSTATUS', 'certificate_state', $direction, $order) ?></th>
      <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'COM_JLMS_CERTIFICATE_ISSUED', 'd.issued_at', $direction, $order) ?></th>
      <th scope="col"><?= HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'c.id', $direction, $order) ?></th>
     </tr></thead>
     <tbody>
      <?php foreach ($this->items as $i => $item) : ?>
       <tr class="row<?= $i % 2 ?>">
        <th scope="row"><a href="<?= Route::_('index.php?option=com_jlms&view=certificate&id=' . (int) $item['id']) ?>"><?= $this->escape($item['student_name']) ?></a></th>
        <td><?= $this->escape($item['course_title']) ?></td>
        <td><?= $this->escape($item['serial']) ?></td>
        <td><?= Text::_($item['status'] === 'revoked' ? 'COM_JLMS_CERTIFICATE_STATE_REVOKED' : ($item['status'] === 'ready' ? 'COM_JLMS_CERTIFICATE_STATE_READY' : 'COM_JLMS_CERTIFICATE_STATE_PENDING')) ?></td>
        <td><?= $this->escape($item['issued_at']) ?></td>
        <td><?= (int) $item['id'] ?></td>
       </tr>
      <?php endforeach; ?>
     </tbody>
    </table>
   <?php endif; ?>
   <?= $this->pagination->getListFooter() ?>
  </section>
 </div>
 <input type="hidden" name="task" value="">
 <input type="hidden" name="boxchecked" value="0">
 <?= HTMLHelper::_('form.token') ?>
</form>
