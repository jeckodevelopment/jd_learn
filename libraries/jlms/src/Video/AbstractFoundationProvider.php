<?php

declare(strict_types=1);


namespace Jlms\Contracts\Video;

/** Registration/validation foundation. Playback stays closed until delivery and current-access checks are integrated. */
abstract class AbstractFoundationProvider implements VideoProviderInterface
{
    abstract protected function key(): string;

    public function getDescriptor(): ProviderDescriptor
    {
        return new ProviderDescriptor($this->key(), 'PLG_JLMSVIDEO_' . strtoupper($this->key()), 1, false);
    }

    public function normaliseSource(SourceInput $input): VideoSource
    {
        return SourceNormalizer::normalise($this->key(), $input);
    }

    public function validateSource(VideoSource $source, AuthorContext $context): ValidationResult
    {
        try {
            if ($source->providerKey !== $this->key() || $source->revision < 1) {
                throw new \InvalidArgumentException('SOURCE_INVALID');
            }
            $this->normaliseSource(new SourceInput($source->reference, $source->options));
            return new ValidationResult();
        } catch (\InvalidArgumentException $error) {
            return new ValidationResult([new ValidationIssue('SOURCE_INVALID', 'COM_JLMS_SOURCE_INVALID', 'source_reference')]);
        }
    }

    public function getCapabilities(VideoSource $source): VideoCapabilities
    {
        // No optimistic capability promises before the actual SDK/delivery adapter is integrated and tested.
        return new VideoCapabilities(false, false, false, false, false, false, false, false);
    }

    public function fetchMetadata(VideoSource $source, MetadataContext $context): VideoMetadata
    {
        throw new \DomainException('METADATA_NOT_AVAILABLE_IN_FOUNDATION');
    }

    public function createPlaybackDescriptor(VideoSource $source, PlaybackContext $context): PlaybackDescriptor
    {
        throw new \DomainException('PLAYBACK_NOT_AVAILABLE_IN_FOUNDATION');
    }

    public function getFrontendAdapterAsset(): string
    {
        return 'plg_jlmsvideo_' . $this->key() . '.player';
    }
}
