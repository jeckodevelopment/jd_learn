<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Certificate;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $item = [];

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $app->setHeader('Cache-Control', 'private, no-store', true);
        try {
            $this->item = $app->bootComponent('com_jlms')->getCertificateService()->adminDetail(
                $app->getIdentity(), $app->getInput()->getInt('id', 0));
        } catch (\Throwable $error) {
            if (in_array($error->getMessage(), ['JERROR_ALERTNOAUTHOR', 'COM_JLMS_RESOURCE_NOT_FOUND', 'COM_JLMS_INVALID_INPUT',
                'COM_JLMS_CERTIFICATE_UNAVAILABLE', 'COM_JLMS_CERTIFICATE_AUDIT_FAILED'], true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), (int) $error->getCode());
            }
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
        $this->getDocument()->setTitle(Text::_('COM_JLMS_CERTIFICATE_DETAIL'));
        ToolbarHelper::title(Text::_('COM_JLMS_CERTIFICATE_DETAIL'), 'certificate');
        parent::display($tpl);
    }
}
