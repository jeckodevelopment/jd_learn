#!/usr/bin/env python3
"""SECDEV-03: repeatable software-scope coverage for the Joomla 1.0.0 runtime.

Verifies that independent regression families and cross-surface source inventories
remain wired to mandatory CI. These assertions concern packaged software; site
configuration, third-party overrides and manual role-based acceptance remain
separate release tests and are NEVER marked complete by this script.
"""
from __future__ import annotations

from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
RUNTIME = ("component", "libraries", "plugins")
CONTROLLER_FOLDERS = (
    ROOT / "component/site/src/Controller",
    ROOT / "component/administrator/src/Controller",
)

# The approved 1.0.0 application has no tabular CSV export. The file-level
# tripwire is tested separately in secdev03-input-output-gate.py.
TESTS = {
    "sql_injection": (
        "tests/catalog-literal-search.php",
        "tests/joomla-publication.php",
        "tests/joomla-pathways.php",
    ),
    "stored_xss_output": (
        "tests/joomla-course-presentation.php",
        "tests/joomla-teachers.php",
        "tests/joomla-pathways.php",
    ),
    "mass_assignment": (
        "tests/joomla-course-presentation.php",
        "tests/joomla-teachers.php",
        "tests/joomla-pathways.php",
        "tests/joomla-curriculum.php",
    ),
    "csv_formula_export_boundary": ("tests/secdev03-input-output-gate.py",),
    "untrusted_execution": ("tests/sec-p1-systematic-source-gate.py", "tests/secdev07-sast.py"),
    "controller_method_csrf": ("tests/sec-p1-controller-gates.py",),
    "plugin_task_inventory": ("tests/sec-p1-cross-surface-gate.py",),
}


def verify(check_root: Path = ROOT) -> dict[str, int]:
    code = [
        p for directory in RUNTIME
        for p in (check_root / directory).rglob("*.php")
    ]
    if len(code) < 300:
        raise AssertionError("SECDEV-03: unexpectedly small shipped PHP review surface")
    controllers = [p for folder in CONTROLLER_FOLDERS
                   for p in (check_root / folder.relative_to(ROOT)).glob("*Controller.php")]
    plugins = list((check_root / "plugins").glob("*/*/src/Extension/*.php"))
    if len(controllers) != 37 or len(plugins) != 10:
        raise AssertionError("SECDEV-03: a controller or plugin was added/removed without review")
    preflight = (check_root / "tools/check.py").read_text(encoding="utf-8")
    workflow = (check_root / ".github/workflows/ci.yml").read_text(encoding="utf-8")
    # Every software threat family must retain automated exercises, and the
    # inventory/sink/CSV gates must run for every candidate.
    for family, sources in TESTS.items():
        for relative in sources:
            path = check_root / relative
            if not path.is_file():
                raise AssertionError(f"SECDEV-03 {family}: missing evidence {relative}")
            runner = preflight if relative.endswith(".py") else workflow
            if relative not in runner:
                raise AssertionError(f"SECDEV-03 {family}: not exercised in CI: {relative}")
    # No direct request superglobal may become an SQL query or rendered HTML.
    # Semgrep and the controller inventory supply additional, distinct checks.
    direct_sink = re.compile(
        r"(?:->setQuery\s*\(|->where\s*\(|->values\s*\(|"
        r"\becho\s+|<\?=\s*)\s*\$_(?:GET|POST|REQUEST|COOKIE)\b",
        re.I,
    )
    offenders = [
        p.relative_to(check_root).as_posix()
        for p in code if direct_sink.search(p.read_text(encoding="utf-8"))
    ]
    if offenders:
        raise AssertionError("SECDEV-03 direct taint sink: " + ", ".join(sorted(offenders)))
    # The installed templates are part of the package, not arbitrary hosting
    # overrides. Require source review for newly introduced public PHP views.
    views = list((check_root / "component").glob("*/tmpl/**/*.php"))
    if len(views) < 10:
        raise AssertionError("SECDEV-03: missing packaged output templates")
    return {
        "runtime_php": len(code),
        "controllers": len(controllers),
        "plugins": len(plugins),
        "templates": len(views),
        "regression_families": len(TESTS),
    }


def negative_fixtures() -> None:
    # Scanner detects direct SQL/HTML taint and avoids rejecting safe binding.
    pattern = re.compile(
        r"(?:->setQuery\s*\(|->where\s*\(|->values\s*\(|"
        r"\becho\s+|<\?=\s*)\s*\$_(?:GET|POST|REQUEST|COOKIE)\b",
        re.I,
    )
    for bad in (
        "$db->setQuery($_GET['sql']);",
        "$query->where($_POST['filter']);",
        "$query->values($_REQUEST['values']);",
        "echo $_COOKIE['content'];",
        "<?= $_GET['title'] ?>",
    ):
        assert pattern.search(bad), "SECDEV-03 taint sink not detected"
    for safe in (
        "$query->where('id = :id')->bind(':id', $id);",
        "echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8');",
        "<?= $this->escape($title) ?>",
    ):
        assert not pattern.search(safe), "SECDEV-03 safe code rejected"


if __name__ == "__main__":
    negative_fixtures()
    metrics = verify()
    print("SECDEV-03 software scope verified: " +
          ", ".join(f"{key}={value}" for key, value in metrics.items()) +
          "; manual Joomla acceptance is a separate gate")
