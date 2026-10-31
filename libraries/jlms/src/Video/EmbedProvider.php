<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

/** Official embed SDKs; no account API or claim of per-user media confidentiality. */
abstract class EmbedProvider extends AbstractFoundationProvider
{
    public function __construct(private readonly bool $enabled = false)
    {
    }

    public function getDescriptor(): ProviderDescriptor
    {
        return new ProviderDescriptor($this->key(), 'PLG_JLMSVIDEO_' . strtoupper($this->key()), 1, $this->enabled);
    }

    public function getCapabilities(VideoSource $source): VideoCapabilities
    {
        return new VideoCapabilities($this->enabled, $this->enabled, $this->enabled, false, false, false, false, false);
    }

    public function createPlaybackDescriptor(VideoSource $source, PlaybackContext $context): PlaybackDescriptor
    {
        if (!$this->enabled || $source->providerKey !== $this->key() || $context->userId < 1 || $context->enrollmentId < 1
            || $context->lessonId < 1 || $context->authorisationGrant === '') {
            throw new \DomainException('COM_JLMS_VIDEO_UNAVAILABLE');
        }
        $normal = $this->normaliseSource(new SourceInput($source->reference, $source->options));
        $options = ['reference' => $normal->reference, 'locale' => $context->locale, 'autoplay' => false,
            'controls' => true, 'privacy_enhanced' => true];
        if (isset($normal->options['privacy_hash'])) { $options['privacy_hash'] = $normal->options['privacy_hash']; }
        return new PlaybackDescriptor($this->key(), $source->revision, $this->getFrontendAdapterAsset(), $options, $this->getCapabilities($source));
    }
}
