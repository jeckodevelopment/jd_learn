<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\View\Dashboard;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Jlms\Contracts\Event\RegisterVideoProvidersEvent;
use Jlms\Contracts\Video\VideoProviderRegistry;

final class HtmlView extends BaseHtmlView
{
    public array $providers = [];

    public function display($tpl = null)
    {
        ToolbarHelper::title('JD Learn', 'graduation-cap');
        $registry = new VideoProviderRegistry();
        PluginHelper::importPlugin('jlmsvideo');
        Factory::getApplication()->getDispatcher()->dispatch(
            RegisterVideoProvidersEvent::NAME,
            new RegisterVideoProvidersEvent($registry),
        );
        $this->providers = $registry->descriptors();
        parent::display($tpl);
    }
}
