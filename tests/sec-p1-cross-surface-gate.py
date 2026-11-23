#!/usr/bin/env python3
"""SEC-P1: reviewed packaged plugin/task and private-delivery entrypoint inventory.

This fails on surface drift or removal of selected guards. It is not a substitute
for HTTP negative tests, threat review of service data flow, or a penetration test.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
PLUGINS = ROOT / "plugins"
EXPECTED = {
    "actionlog/jdlearn": ("Jdlearn", ("onJlmsCourseTransition",)),
    "finder/jlms": ("Jlms", ("onFinderAfterSave", "onFinderAfterDelete", "onFinderChangeState")),
    "jlmsvideo/cloudflarestream": ("Cloudflarestream", ("RegisterVideoProvidersEvent::NAME",)),
    "jlmsvideo/html5": ("Html5", ("RegisterVideoProvidersEvent::NAME",)),
    "jlmsvideo/vimeo": ("Vimeo", ("RegisterVideoProvidersEvent::NAME",)),
    "jlmsvideo/youtube": ("Youtube", ("RegisterVideoProvidersEvent::NAME",)),
    "privacy/jdlearn": ("Jdlearn", ("onPrivacyCollectAdminCapabilities", "onPrivacyExportRequest",
                                       "onPrivacyCanRemoveData", "onPrivacyRemoveData")),
    "system/jlmsmenu": ("Jlmsmenu", ("onPreprocessMenuItems", "onAfterRoute")),
    "task/jlms": ("Jlms", ("onTaskOptionsList", "onExecuteTask", "onContentPrepareForm")),
    "user/jdlearnconsent": ("Jdlearnconsent", ("onContentPrepareForm", "onUserAfterSave")),
}
actual = {
    str(p.relative_to(PLUGINS).parts[0] + "/" + p.relative_to(PLUGINS).parts[1])
    for p in PLUGINS.glob("*/*/src/Extension/*.php")
}
assert actual == set(EXPECTED), ("Review new/removed shipped plugin class", sorted(actual ^ set(EXPECTED)))
for relative, (class_name, events) in EXPECTED.items():
    filename = PLUGINS / relative / "src/Extension" / (class_name + ".php")
    text = filename.read_text(encoding="utf-8")
    assert re.search(r"final class " + class_name + r"\b", text), relative
    assert "SubscriberInterface" in text and "getSubscribedEvents()" in text, relative
    for event in events:
        assert event in text.split("getSubscribedEvents()", 1)[1], (relative, event)
    # New public actions must receive an explicit reviewer decision.
    public = set(re.findall(r"public (?:static )?function ([A-Za-z0-9_]+)\(", text))
    expected = {
        "actionlog/jdlearn": {"getSubscribedEvents", "onJlmsCourseTransition"},
        "finder/jlms": {"getSubscribedEvents", "onFinderAfterSave", "onFinderAfterDelete", "onFinderChangeState"},
        "jlmsvideo/cloudflarestream": {"getSubscribedEvents", "registerProviders"},
        "jlmsvideo/html5": {"getSubscribedEvents", "registerProviders"},
        "jlmsvideo/vimeo": {"getSubscribedEvents", "registerProviders"},
        "jlmsvideo/youtube": {"getSubscribedEvents", "registerProviders"},
        "privacy/jdlearn": {"getSubscribedEvents", "onPrivacyCollectAdminCapabilities",
                             "onPrivacyExportRequest", "onPrivacyCanRemoveData", "onPrivacyRemoveData"},
        "system/jlmsmenu": {"getSubscribedEvents", "redirectFieldsContextSwitch", "filterAdministratorMenu"},
        "task/jlms": {"getSubscribedEvents"},
        "user/jdlearnconsent": {"getSubscribedEvents", "onContentPrepareForm", "onUserAfterSave"},
    }[relative]
    assert public == expected, (relative, "unexpected public methods", sorted(public ^ expected))

task = (PLUGINS / "task/jlms/src/Extension/Jlms.php").read_text(encoding="utf-8")
for kind, handler in {
    "jlms.quiz.expire": "expireQuizzes",
    "jlms.materials.cleanup": "cleanupMaterials",
    "jlms.mail.dispatch": "dispatchMail",
}.items():
    assert re.search(r"'" + re.escape(kind) + r"'\s*=>\s*\[[^\]]*'method'\s*=>\s*'" + handler + r"'", task), (kind, handler)
    assert re.search(r"protected\\s+function\\s+" + re.escape(handler) + r"\\(\\s*ExecuteTaskEvent\\s+\\$event\\s*\\)\\s*:\\s*int\\b", task), handler
assert task.count("logTask(") == 3, "Task logging requires review for PII/secrets"

# Verify callback-limited SMTP dispatch, quiz expiry and protected orphan cleanup.
for path, fragment in [
    ("component/administrator/src/Application/QuizAttemptService.php", "max(1,min(100,$limit))"),
    ("component/administrator/src/Application/MaterialService.php", "max(1, min(100, $limit))"),
    ("component/administrator/src/Application/CourseCommunicationService.php", "max(1, min(100, $limit))"),
    ("component/administrator/src/Infrastructure/MailQueue.php", "max(1, min(100, $limit))"),
]:
    assert re.search(r"max\(\s*1\s*,\s*min\(\s*100\s*,\s*\$limit\s*\)\s*\)",
                     (ROOT / path).read_text(encoding="utf-8")), path

cloudflare = (PLUGINS / "jlmsvideo/cloudflarestream/src/Extension/Cloudflarestream.php").read_text(encoding="utf-8")
assert "EnvironmentSecretProvider" in cloudflare, "Cloudflare credentials must be environment references"
assert "'follow_location' => false" in cloudflare, "Remote video transport must not follow redirects"
assert "max(1, min(10, (int)" in cloudflare, "Remote transport timeout must be bounded"
privacy = (PLUGINS / "privacy/jdlearn/src/Extension/Jdlearn.php").read_text(encoding="utf-8")
assert "onPrivacyCanRemoveData" in privacy and "onPrivacyRemoveData" in privacy
assert "transactionStart()" in privacy and "transactionRollback()" in privacy
assert "#__jlms_certificate_documents" in privacy and "#__jlms_mail_consents" in privacy

media = (ROOT / "component/site/src/Controller/MediaController.php").read_text(encoding="utf-8")
assert "['GET', 'HEAD']" in media and "Cache-Control" in media
assert "getLocalVideoService()->delivery(" in media and "getLocalVideoService()->previewDelivery(" in media
assert "CurriculumService::id(" in media and "MaterialDownload::send(" in media
print("SEC-P1 plugin/task/media review gate: 10 plugin classes, 3 Scheduler tasks, bounded workers, controlled streaming entrypoint")
