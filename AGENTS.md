# JD Learn development

- User preference: work through GitHub for every development increment. Publish the branch, open/update a pull request and inspect its actual GitHub Actions results; do not leave an increment only as a local patch.

- Use `docs/specifications/Specifica_funzionale_LMS_Joomla_1.0.md` (content version1.5), the technical design and the security/provider specifications as the scope baseline. Keep requirements and implementation status separate.
- Product name: JD Learn. Installed IDs remain com_jlms, pkg_jlms, lib_jlms and plugin group jlmsvideo. No client-specific branding and no competitor references.
- Joomla6 MVC, DI, native accounts, categories, assets/ACL, languages, forms, session/CSRF, WAM and other core features where applicable. Do not modify Joomla core or duplicate authentication/access levels.
- Subscription sales and Joomla group assignment are outside scope. Enrolment never grants groups. Recheck current access before each protected operation, including retries.
- Work in reviewable increments. Unimplemented publication, playback and student actions must fail closed. Never claim a schema/interface is a completed feature.
- Base distribution must include all four provider plugins: youtube, vimeo, cloudflarestream, html5. Plugins register a descriptor and lazy factory; the component owns authorisation, progress, completion and grading.
- No secrets, personal data, private media, answer keys or generated site configuration in git. Test credentials are limited to disposable CI fixtures. Keep runtime fixtures under ignored `.runtime/`.
- Bind SQL values, allowlist fields and sort columns, validate parent relations, escape output contextually, use core CSRF and ACL. Keep published teaching history immutable and database transitions short/atomic.
- Run `python3 tools/build.py`, `python3 tools/check.py`, relevant PHP tests and lint for code changes. Use the existing GitHub CI when the local environment lacks PHP/database; inspect actual job results. Never weaken a test just to hide a product defect.
- Database and Joomla smoke tests must run only with the explicit disposable-site opt-in. Do not execute them on a customer site.
- Update implementation status, requirement mapping, changelog and CSS/override documentation when changing the corresponding behaviour. Test release install/upgrade and current Joomla patch before production distribution.

- Documentation ownership: IMPLEMENTATION_STATUS.md is the current feature snapshot; NEXT_STEPS.md owns ordered remaining work; ROADMAP_TO_STABLE.md defines phase scope; M01_ACCEPTANCE.md separates missing implementation from pending validation. Put release-by-release notes only in CHANGELOG.md/IMPLEMENTATION_HISTORY.md, not appended to current summaries or guides. Replace superseded statements, update current evidence, and keep requirement descriptions/IDs/acceptance separate from implementation status.

- Operational efficiency: prepare and review a candidate locally before publishing changes; batch corrections and avoid redundant Actions runs. Keep the complete PR verification and manual workflow_dispatch available. A merge into main does not repeat the already-passed PR suite; use PRs for all changes, and manually verify any exceptional direct main change. Do not describe unexecuted or skipped checks as passed.

- Persistent user priorities: complete usable course management and operational YouTube, Vimeo and Cloudflare Stream publication/playback first. Treat base provider defects as immediate course-blocking work, distinct from later advanced M04 extensions. Update repository status, ordered next steps and relevant guides/evidence in every increment.
