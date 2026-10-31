<?php

declare(strict_types=1);

namespace Jlms\Plugin\Jlmsvideo\Html5\Provider;

use Jlms\Contracts\Video\AbstractFoundationProvider;
use Jlms\Contracts\Video\ProviderDescriptor;
use Jlms\Contracts\Video\VideoCapabilities;
use Jlms\Contracts\Video\VideoSource;
use Jlms\Contracts\Video\SourceInput;
use Jlms\Contracts\Video\PlaybackContext;
use Jlms\Contracts\Video\PlaybackDescriptor;

final class Provider extends AbstractFoundationProvider
{
    public function __construct(private readonly bool $enabled = false) {}
    protected function key(): string { return 'html5'; }
    public function getDescriptor(): ProviderDescriptor { return new ProviderDescriptor('html5', 'PLG_JLMSVIDEO_HTML5', 1, $this->enabled); }
    public function getCapabilities(VideoSource $source): VideoCapabilities
    {
        return new VideoCapabilities($this->enabled, $this->enabled, $this->enabled, false, false, false, false, false);
    }
    public function createPlaybackDescriptor(VideoSource $source, PlaybackContext $context): PlaybackDescriptor
    {
        if (!$this->enabled || $source->providerKey !== 'html5' || $context->userId < 1 || $context->enrollmentId < 1 || $context->lessonId < 1 || $context->authorisationGrant === '') {
            throw new \RuntimeException('COM_JLMS_VIDEO_UNAVAILABLE', 503);
        }
        $this->normaliseSource(new SourceInput($source->reference));
        $url = \Joomla\CMS\Router\Route::_('index.php?option=com_jlms&task=media.stream&format=raw&enrollment_id=' . $context->enrollmentId
            . '&lesson_id=' . $context->lessonId . '&source_revision=' . $source->revision, false);
        return new PlaybackDescriptor('html5', $source->revision, $this->getFrontendAdapterAsset(), ['url' => $url, 'autoplay' => false, 'preload' => 'metadata'], $this->getCapabilities($source));
    }
}
