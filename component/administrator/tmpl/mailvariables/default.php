<?php

declare(strict_types=1);

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$mailLanguage = Factory::getApplication()->getLanguage()->getTag();
$mailCatalog = \Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateVariableCatalog::class;
$mailIds = \Jlms\Component\Jlms\Administrator\Infrastructure\MailTemplateInstaller::templateIds();
$canEdit = Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_mails')
    && Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_mails');
?>
<section id="jlms-mail-variable-guide">
 <h1><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_TITLE')) ?></h1>
 <p><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_INTRO')) ?></p>
 <p class="alert alert-info"><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_WARNING')) ?></p>
 <?php if ($canEdit) : ?>
 <p><a class="btn btn-primary" href="<?= Route::_('index.php?option=com_mails&view=templates&filter[extension]=com_jlms') ?>"><?= $this->escape(Text::_('COM_JLMS_MAIL_TEMPLATES')) ?></a></p>
 <?php endif; ?>
 <?php foreach ($mailIds as $mailId) : ?>
 <details class="card mb-2">
  <summary class="card-header"><code><?= $this->escape($mailId) ?></code></summary>
  <div class="card-body table-responsive">
   <table class="table table-sm table-striped">
    <caption><?= $this->escape($mailId) ?></caption>
    <thead><tr>
     <th scope="col"><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_TAG')) ?></th>
     <th scope="col"><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_TYPE')) ?></th>
     <th scope="col"><?= $this->escape(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_DESCRIPTION')) ?></th>
    </tr></thead>
    <tbody>
     <?php foreach ($mailCatalog::forTemplate($mailId, $mailLanguage) as $name => $details) : ?>
     <tr>
      <td><code><?= $this->escape('{' . $name . '}') ?></code></td>
      <td><?= $this->escape($details['type']) ?></td>
      <td><?= $this->escape($details['description']) ?></td>
     </tr>
     <?php endforeach; ?>
    </tbody>
   </table>
  </div>
 </details>
 <?php endforeach; ?>
</section>
