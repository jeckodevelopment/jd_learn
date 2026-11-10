<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Pathway;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Jlms\Component\Jlms\Site\Helper\PublicMetadataHelper;

final class HtmlView extends BaseHtmlView
{
    public array $item = [];
    public bool $isGuest = true;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $actor = Factory::getApplication()->getIdentity();
        $this->isGuest = !(int) $actor->id;
        $pathway = $this->item['pathway'];
        if (!$this->isGuest) {
            $this->getDocument()->setTitle((string) $pathway->title);
            $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
        } else {
            PublicMetadataHelper::apply(
                $this->getDocument(),
                (string) $pathway->title,
                (string) ($pathway->description ?? ''),
                'index.php?option=com_jlms&view=pathway&id=' . (int) $pathway->id
            );
        }
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
