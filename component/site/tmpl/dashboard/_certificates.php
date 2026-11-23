<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>
<div class="jlms-my-course-grid">
 <?php foreach ($certificates as $certificate) : ?>
 <article class="jlms-card jlms-my-course">
  <h3><?= $this->escape($certificate['title']) ?></h3>
  <p><?= Text::_('COM_JLMS_CERTIFICATE_ISSUED') ?>: <?= $this->escape($certificate['issued_at']) ?></p>
  <p><?= Text::_('COM_JLMS_CERTIFICATE_CODE') ?>: <?= $this->escape($certificate['serial']) ?></p>
        <?php if ($certificate['status'] === 'ready' || $certificate['status'] === 'pending_render') : ?>
   <a class="jlms-my-course__action" href="<?= Route::_('index.php?option=com_jlms&task=certificate.download&enrollment_id=' . (int) $certificate['enrollment_id']) ?>"><?= Text::_('COM_JLMS_CERTIFICATE_DOWNLOAD') ?></a>
        <?php endif; ?>
 </article>
 <?php endforeach; ?>
</div>
