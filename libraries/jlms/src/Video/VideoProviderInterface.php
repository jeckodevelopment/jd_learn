<?php

declare(strict_types=1);

namespace Jlms\Contracts\Video;

interface VideoProviderInterface
{
    public function getDescriptor(): ProviderDescriptor;
    public function getCapabilities(VideoSource $source): VideoCapabilities;
    public function normaliseSource(SourceInput $input): VideoSource;
    public function validateSource(VideoSource $source, AuthorContext $context): ValidationResult;
    public function fetchMetadata(VideoSource $source, MetadataContext $context): VideoMetadata;
    public function createPlaybackDescriptor(VideoSource $source, PlaybackContext $context): PlaybackDescriptor;
    public function getFrontendAdapterAsset(): string;
}
