<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\View\Certificates;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public array $result = [];

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $app->setHeader('Cache-Control', 'private, no-store', true);
        try {
            $this->result = $app->bootComponent('com_jlms')->getCertificateService()->adminList($app->getIdentity(), [
                'q' => $app->getInput()->getString('q', ''),
                'status' => $app->getInput()->getCmd('status', ''),
                'offset' => $app->getInput()->getInt('offset', 0),
            ]);
        } catch (\Throwable $error) {
            if (in_array($error->getMessage(), ['JERROR_ALERTNOAUTHOR', 'COM_JLMS_CERTIFICATE_AUDIT_FAILED'], true)) {
                throw new \RuntimeException(Text::_($error->getMessage()), (int) $error->getCode());
            }
            throw new \RuntimeException(Text::_('COM_JLMS_SAVE_FAILED'), 500);
        }
        $this->getDocument()->setTitle(Text::_('COM_JLMS_CERTIFICATES'));
        ToolbarHelper::title(Text::_('COM_JLMS_CERTIFICATES'), 'certificate');
        parent::display($tpl);
    }
}
