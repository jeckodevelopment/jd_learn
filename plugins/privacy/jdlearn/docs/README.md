# JD Learn privacy plugin

The plugin contributes course enrolments, enrolment requests, lesson progress, quiz attempts and answers, and any issued certificate identity snapshots to Joomla `com_privacy` exports.

Removal preserves learning evidence needed for course history while unlinking the Joomla user ID from enrolments, deleting ephemeral progress sessions, clearing submitted answer payloads and enrolment request reasons, and removing the account ID from enrolment-level audit actors. Joomla's user privacy plugin remains responsible for pseudonymising the Joomla account and ending its sessions.

The plugin blocks removal when certificate documents with identifying snapshots, JD Learn jobs owned by the account, or enrollment notification records are present. The site operator must resolve those records under the site's retention policy before retrying. This guard prevents orphaned identifying documents or payloads; it does not define retention periods or purge backups.

The installed package registers the plugin, but site administrators must confirm it is enabled under System → Manage → Plugins. Joomla's privacy component and its request workflow must also be enabled.

## Manual Joomla checks

1. Confirm the plugin is installed and enabled, and that Joomla Privacy capabilities list JD Learn.
2. Create a user with a course enrolment, lesson progress, and (if quiz fixtures are available) a quiz attempt; run a privacy export and confirm the corresponding domains and answer data appear without tokens or session identifiers.
3. Process a removal request for a user with enrolment history but no blocking certificate document or owned job. Confirm the Joomla account is pseudonymised, the enrolment/progress/score history remains, direct account links and free-text answers are removed, and the user can no longer access the course.
4. Add an issued certificate document, owned job, or undelivered notification, request removal, and confirm Joomla reports the localized blocker before any records change.
5. Repeat with a user who has no JD Learn data and confirm the plugin allows Joomla's normal removal flow.

The database integration suite covers schema/package installation and removal/export behavior against the Joomla runtime; these site-level checks remain necessary for the deployed privacy workflow and retention policy.
