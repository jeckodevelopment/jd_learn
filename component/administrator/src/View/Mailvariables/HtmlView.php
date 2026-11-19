<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Mailvariables;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Read-only guide to the canonical placeholders registered in Joomla Mail Templates.
 * Editing continues through the core Joomla com_mails component.
 */
final class HtmlView extends BaseHtmlView
{
    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        if (!$app->getIdentity()->authorise('core.manage', 'com_jlms')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        $app->setHeader('Cache-Control', 'private, no-store', true);
        ToolbarHelper::title(Text::_('COM_JLMS_MAIL_VARIABLE_GUIDE_TITLE'), 'envelope');
        $identity = $app->getIdentity();
        if ($identity->authorise('core.manage', 'com_mails') && $identity->authorise('core.edit', 'com_mails')) {
            ToolbarHelper::link(
                'index.php?option=com_mails&view=templates&filter[extension]=com_jlms',
                Text::_('COM_JLMS_MAIL_TEMPLATES'),
                'envelope'
            );
        }
        ToolbarHelper::help('mailvariables', true);
        parent::display($tpl);
    }
}
