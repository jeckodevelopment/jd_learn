<?php

declare(strict_types=1);


namespace Jlms\Component\Jlms\Administrator\Domain;

/** Evaluate a fresh server-side snapshot. No group writes and no cached entitlement. */
final class AccessPolicy
{
    public function assertCanLearn(
        int $actorUserId,
        bool $actorEnabled,
        array $currentViewLevels,
        int $requiredViewLevel,
        int $ownerUserId,
        string $enrollmentState,
        bool $parentsAvailable,
        bool $withinWindow,
        bool $prerequisitesComplete,
    ): void {
        if ($actorUserId < 1 || !$actorEnabled || $actorUserId !== $ownerUserId
            || !in_array($requiredViewLevel, $currentViewLevels, true)
            || !in_array($enrollmentState, ['active', 'completed'], true)
            || !$parentsAvailable || !$withinWindow || !$prerequisitesComplete) {
            throw new \DomainException('ACCESS_DENIED');
        }
    }
}
