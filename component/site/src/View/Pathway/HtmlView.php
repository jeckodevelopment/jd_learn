<?php

declare(strict_types=1);

namespace Jlms\Component\Jlms\Site\View\Pathway;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Jlms\Component\Jlms\Site\Helper\PublicMetadataHelper;
use Jlms\Component\Jlms\Site\Helper\PublicBreadcrumbHelper;
use Jlms\Component\Jlms\Site\Helper\PublicContentPreparation;

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
        // PathwayService::detail() has already checked current publication, ACL,
        // language and category visibility before editorial plugins are invoked.
        $pathway->description = PublicContentPreparation::html(
            'com_jlms.pathway',
            (int) $pathway->id,
            (string) ($pathway->description ?? ''),
            $pathway
        );
        if (!$this->isGuest) {
            $this->getDocument()->setTitle((string) $pathway->title);
            $this->getDocument()->setMetaData('robots', 'noindex, nofollow');
            Factory::getApplication()->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        } else {
            PublicMetadataHelper::apply(
                $this->getDocument(),
                (string) $pathway->title,
                (string) ($pathway->description ?? ''),
                'index.php?option=com_jlms&view=pathway&id=' . (int) $pathway->id
            );
        }
        PublicBreadcrumbHelper::addDetail('pathway', (int) $pathway->id, (string) $pathway->title);
        $this->getDocument()->getWebAssetManager()->useStyle('com_jlms.site');
        parent::display($tpl);
    }
}
