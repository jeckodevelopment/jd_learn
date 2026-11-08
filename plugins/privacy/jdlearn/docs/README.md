# JD Learn privacy plugin

The plugin contributes course enrolments, enrolment requests, lesson progress, quiz attempts and answers, and any issued certificate identity snapshots to Joomla `com_privacy` exports.

Removal preserves learning evidence needed for course history while unlinking the Joomla user ID from enrolments, deleting ephemeral progress sessions, clearing submitted answer payloads and enrolment request reasons, and removing the account ID from enrolment-level audit actors. Joomla's user privacy plugin remains responsible for pseudonymising the Joomla account and ending its sessions.

The plugin blocks removal when certificate documents with identifying snapshots, JD Learn jobs owned by the account, or enrollment notification records are present. The site operator must resolve those records under the site's retention policy before retrying. This guard prevents orphaned identifying documents or payloads; it does not define retention periods or purge backups.

Starting with package 0.41.21, the plugin's own Joomla installer enables it on a fresh install, after Joomla has created its extension record. Package updates preserve the administrator's current enabled/disabled choice. Package 0.41.20 does not reliably apply this default and should be upgraded before relying on automatic activation. Joomla's Privacy component and its request workflow must also be enabled. Administrators can still disable this integration under System → Manage → Plugins if the site's policy requires it.

## Manual Joomla checks

1. On a fresh package installation, open System → Manage → Plugins and confirm **JD Learn - Privacy** is enabled. Open Users → Privacy → Features and confirm the title and four capabilities are translated in the administrator language (Italian and English).
2. Disable the plugin, update the JD Learn package, and confirm the update preserves the disabled state. Re-enable it before continuing.
3. Create a user with a course enrolment, lesson progress, and (if quiz fixtures are available) a quiz attempt; run a privacy export and confirm the corresponding domains and answer data appear without tokens or session identifiers.
4. Process a removal request for a user with enrolment history but no blocking certificate document or owned job. Confirm the Joomla account is pseudonymised, the enrolment/progress/score history remains, direct account links and free-text answers are removed, and the user can no longer access the course.
5. Add an issued certificate document, owned job, or undelivered notification, request removal, and confirm Joomla reports the localized blocker before any records change.
6. Repeat with a user who has no JD Learn data and confirm the plugin allows Joomla's normal removal flow.

The database integration suite covers schema/package installation and removal/export behavior against the Joomla runtime; these site-level checks remain necessary for the deployed privacy workflow and retention policy.
