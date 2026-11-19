<?php
declare(strict_types=1);

namespace Jlms\Component\Jlms\Administrator\Application;

/**
 * One mutation ID per confirmed course-message preview.
 *
 * A page reload must reuse the persisted ID: the mail outbox already has
 * a unique event_key and must not create a second test message for a retry.
 */
final class CourseMessageMutation
{
    public static function create(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-'
            . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-'
            . substr($hex, 20);
    }

    public static function valid(mixed $id): bool
    {
        return is_string($id) && preg_match(
            '/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/iD',
            $id
        ) === 1;
    }
}
