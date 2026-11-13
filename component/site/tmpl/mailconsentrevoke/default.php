<?php

declare(strict_types=1);

\\defined('_JEXEC') or die;

use Joomla\\CMS\\HTML\\HTMLHelper;
use Joomla\\CMS\\Language\\Text;
use Joomla\\CMS\\Router\\Route;
?>
<main id="jlms-mail-consent-revoke" class="jlms-mail-consent-revoke">
 <h1><?= Text::_('COM_JLMS_MAIL_CONSENT_LINK_TITLE') ?></h1>
 <?php if ($this->completed) : ?>
  <p id="jlms-mail-consent-link-completed"><?= Text::_('COM_JLMS_MAIL_CONSENT_LINK_COMPLETED') ?></p>
 <?php elseif ($this->canSubmit) : ?>
  <p><?= Text::_('COM_JLMS_MAIL_CONSENT_LINK_EXPLANATION') ?></p>
  <form id="jlms-mail-consent-revoke-form" method="post" action="<?= Route::_('index.php?option=com_jlms&task=mailconsent.revokeLink', false) ?>">
   <input type="hidden" name="token" value="<?= $this->escape($this->token) ?>">
   <?= HTMLHelper::_('form.token') ?>
   <button type="submit" class="btn btn-primary"><?= Text::_('COM_JLMS_MAIL_CONSENT_LINK_CONFIRM') ?></button>
  </form>
 <?php else : ?>
  <p id="jlms-mail-consent-link-invalid"><?= Text::_('COM_JLMS_MAIL_CONSENT_LINK_INVALID') ?></p>
 <?php endif; ?>
</main>
