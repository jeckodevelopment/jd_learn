#!/usr/bin/env python3
"""DOC-07: safe dashboard operational guidance is localized and ACL scoped.

This is a structural guard, not a claim that real Joomla users or third-party
provider credentials were manually tested.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
view = (ROOT / "component/administrator/src/View/Dashboard/HtmlView.php").read_text()
page = (ROOT / "component/administrator/tmpl/dashboard/default.php").read_text()
operations = ("STORAGE", "MAIL", "CERTIFICATES", "COURSE", "SCHEDULER")
assert "CourseEditorAccess::fresh(Factory::getApplication()->getIdentity())" in view
assert "$this->safeOperations = [];" in view
assert "if ($actor->authorise('core.manage', 'com_jlms'))" in view
assert "if ($actor->authorise('jlms.audit.view', 'com_jlms'))" in view
assert "if ($actor->authorise('jlms.certificates.manage', 'com_jlms'))" in view
assert "if ($actor->authorise('core.manage', 'com_scheduler'))" in view
assert 'id="jlms-admin-safe-operations"' in page
assert 'data-jlms-safe-operation=' in page
assert 'foreach ($this->safeOperations as $operation)' in page
assert "Text::_($safeOperationText[$operation][0])" in page
assert "Text::_($safeOperationText[$operation][1])" in page
assert 'method="post"' not in page[page.index('id="jlms-admin-safe-operations"'):]
assert 'task=' not in page[page.index('id="jlms-admin-safe-operations"'):]
for locale in ("it-IT", "en-GB"):
    lang = (ROOT / "component/administrator/language" / locale / "com_jlms.ini").read_text()
    for key in ("TITLE", "INTRO", "NOTE"):
        assert len(re.findall(r"^COM_JLMS_SAFE_OPERATIONS_" + key + "=", lang, re.M)) == 1, (locale, key)
    for op in operations:
        for suffix in ("TITLE", "STEPS"):
            key = f"COM_JLMS_SAFE_OPERATIONS_{op}_{suffix}"
            assert len(re.findall(r"^" + key + "=", lang, re.M)) == 1, (locale, key)
        line = next(x for x in lang.splitlines() if x.startswith(f"COM_JLMS_SAFE_OPERATIONS_{op}_STEPS="))
        assert all(f"{n}." in line for n in ("1", "2", "3")), (locale, op)
        assert len(line) > 140, (locale, op, "actionable procedures required")
# DOC-07 configuration tests are read-only, role-scoped, and render only
# a known localization key and a controlled severity, never secrets or paths.
assert "public array $configurationDiagnostics = [];" in view
assert "ConfigurationDiagnostics::summarize($this->schedulerHealth, $this->providers)" in view
assert "$actor->authorise('core.manage', 'com_jlms')" in view
assert 'id="jlms-admin-configuration-diagnostics"' in page
diagnostic = page.split('id="jlms-admin-configuration-diagnostics"', 1)[1].split('id="jlms-admin-safe-operations"', 1)[0]
assert 'data-jlms-configuration-kind=' in diagnostic
assert "Text::_($check['guidanceKey'])" in diagnostic
assert "Text::_($check['stateKey'])" in diagnostic
assert 'method="post"' not in diagnostic and 'task=' not in diagnostic
assert 'href=' not in diagnostic and 'echo $check[' not in diagnostic
for locale in ("it-IT", "en-GB"):
    lang = (ROOT / "component/administrator/language" / locale / "com_jlms.ini").read_text()
    for suffix in ("TITLE", "NOTE", "CHECK", "STATE", "REMEDY"):
        key = "COM_JLMS_CONFIG_DIAGNOSTICS_" + suffix
        assert len(re.findall(r"^" + key + r"=", lang, re.M)) == 1, (locale, key)
    note = next(x for x in lang.splitlines() if x.startswith("COM_JLMS_CONFIG_DIAGNOSTICS_NOTE=")).lower()
    assert ("sola lettura" in note if locale == "it-IT" else "read-only" in note)
    assert ("permessi" in note if locale == "it-IT" else "permissions" in note)
    for key_prefix in ("COM_JLMS_SCHEDULER_GUIDANCE_", "COM_JLMS_PROVIDER_GUIDANCE_"):
        variants = [x for x in lang.splitlines() if x.startswith(key_prefix)]
        assert len(variants) >= 6
        # An operator is pointed to safe management channels, not bypasses.
        assert all("disabilitare i controlli" not in line.lower() or "senza disabilitare" in line.lower()
                   for line in variants)
# Existing error-specific scheduler and video guidance stays available.
assert "SchedulerHealth::guidanceKey(" in page
assert "DashboardProviderStatus::guidanceKey(" in page
# SCR-16: private configuration values are accessed only locally and projected
# to fixed localized keys. No diagnostics POST, remote probe or mutable action.
diagnostics_code = (ROOT / "component/administrator/src/Application/ConfigurationDiagnostics.php").read_text()
assert "function operationalSettings(array $params): array" in diagnostics_code
assert "function mailTemplates(array $required, array $installed): array" in diagnostics_code
assert "string $documentRoot = ''" in diagnostics_code
assert "static function (string $label, bool $enabled" in diagnostics_code
assert "$params['material_clamd_socket']" in diagnostics_code
assert "array_push($this->configurationDiagnostics" in view
assert "is_string($_SERVER['DOCUMENT_ROOT'] ?? null)" in view
assert "->select($db->quoteName(['template_id', 'language']))" in view
for forbidden in ("->insert(", "->update(", "->delete(", "stream_socket_client(", "sendMail("):
    assert forbidden not in diagnostics_code, forbidden
for locale in ("it-IT", "en-GB"):
    lang = (ROOT / "component/administrator/language" / locale / "com_jlms.ini").read_text()
    keys = [
        "SCAN_LABEL", "SCAN_UNVERIFIED", "SCAN_MISSING", "SCAN_DISABLED",
        "SCAN_GUIDANCE_UNVERIFIED", "SCAN_GUIDANCE_MISSING", "SCAN_GUIDANCE_DISABLED",
        "VIDEO_LOCAL_LABEL", "VIDEO_LOCAL_ACTIVE", "VIDEO_LOCAL_DISABLED",
        "VIDEO_LOCAL_GUIDANCE_ACTIVE", "VIDEO_LOCAL_GUIDANCE_DISABLED",
        "CERTIFICATES_LABEL", "CERTIFICATES_ACTIVE", "CERTIFICATES_DISABLED",
        "CERTIFICATES_GUIDANCE_ACTIVE", "CERTIFICATES_GUIDANCE_DISABLED",
        "MAIL_FLAGS_LABEL", "MAIL_FLAGS_ACTIVE", "MAIL_FLAGS_DISABLED",
        "MAIL_FLAGS_GUIDANCE_ACTIVE", "MAIL_FLAGS_GUIDANCE_DISABLED",
        "CATALOG_LABEL", "CATALOG_READY", "CATALOG_INVALID",
        "CATALOG_GUIDANCE_READY", "CATALOG_GUIDANCE_INVALID",
    ]
    for key in keys:
        assert len(re.findall(r"^COM_JLMS_CONFIG_" + key + r"=", lang, re.M)) == 1, (locale, key)
assert "ConfigurationDiagnostics::localVideoPrerequisites(" in view
assert "public static function localVideoPrerequisites(" in diagnostics_code
assert "is_executable($probe)" in diagnostics_code
assert "is_link($probe)" in diagnostics_code
for locale in ("it-IT", "en-GB"):
    translations = (ROOT / "component/administrator/language" / locale / "com_jlms.ini").read_text()
    for name in ("VIDEO_ROOT_LABEL", "VIDEO_ROOT_READY", "VIDEO_ROOT_UNAVAILABLE",
                 "VIDEO_ROOT_GUIDANCE_READY", "VIDEO_ROOT_GUIDANCE_UNAVAILABLE",
                 "VIDEO_PROBE_LABEL", "VIDEO_PROBE_UNVERIFIED", "VIDEO_PROBE_UNAVAILABLE",
                 "VIDEO_PROBE_GUIDANCE_UNVERIFIED", "VIDEO_PROBE_GUIDANCE_UNAVAILABLE"):
        assert len(re.findall(r"^COM_JLMS_CONFIG_" + name + r"=", translations, re.M)) == 1
# SCR-16: third-party video provider labels/identifiers never reach a dashboard
# output unvalidated. The diagnostics surface remains deliberately non-mutating.
provider_projection = (ROOT / "component/administrator/src/Application/DashboardProviderStatus.php").read_text()
assert "COM_JLMS_CONFIG_DIAGNOSTICS_UNKNOWN_LABEL" in provider_projection
assert "PLG_JLMSVIDEO" in provider_projection
assert "preg_match(" in provider_projection
assert "'labelKey' => $safeLabel" in provider_projection
assert "'key' => $key" in provider_projection
assert "PluginHelper::importPlugin('jlmsvideo')" in view
assert "RegisterVideoProvidersEvent::NAME" in view
assert "sendMail(" not in view
assert "->insert(" not in view and "->update(" not in view and "->delete(" not in view
assert "->save(" not in view and "->store(" not in view
# SCR-16 completion candidate: real override diagnostics must remain bounded,
# ACL scoped and read-only, including malformed data and unknown local values.
assert "ConfigurationDiagnostics::inheritance(" in view
assert "public static function inheritance(" in diagnostics_code
assert "->setQuery(" not in diagnostics_code
assert "MailPreferences::EVENTS" in diagnostics_code
assert "array_slice($courseOptions, 0, 500)" in view
assert "array_slice($menuOptions, 0, 500)" in view
assert "$coursesComplete = count($courseOptions) <= 500;" in view
assert "$menusComplete = count($menuOptions) <= 500;" in view
assert "$coursesComplete, $menusComplete" in view
# Unrelated Joomla component menu links must not consume the 500-row limit.
assert "index.php?option=com_jlms&view=courses&%" in view
assert "index.php?option=com_jlms&view=category&%" in view
assert "Known invalid values must remain actionable" in diagnostics_code
assert "bool $coursesComplete, bool $menusComplete" in diagnostics_code
assert "$menuInvalid, $menusComplete" in diagnostics_code

for locale in ("it-IT", "en-GB"):
    translation = (ROOT / "component/administrator/language" / locale / "com_jlms.ini").read_text()
    for suffix in ("COURSE_LABEL", "MENU_LABEL", "INCOMPLETE", "INVALID",
                   "OVERRIDES", "INHERITED", "GUIDANCE_INCOMPLETE", "GUIDANCE_INVALID",
                   "GUIDANCE_OVERRIDES", "GUIDANCE_INHERITED"):
        key = "COM_JLMS_CONFIG_INHERIT_" + suffix
        assert len(re.findall(r"^" + key + r"=", translation, re.M)) == 1, (locale, key)
print("DOC-07 safe procedures: five ACL-scoped operations, IT/EN and non-mutating dashboard OK")
