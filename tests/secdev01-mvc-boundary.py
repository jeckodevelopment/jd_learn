#!/usr/bin/env python3
"""SECDEV-01: preserve Joomla MVC boundary for packaged 1.0.0 PHP.

A structural regression check, not a manual acceptance test. Business rules and
permission decisions belong in shared services/models, not in templates. The
controller method/dispatch inventory is separately enforced by SEC-P1.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
VIEWS = [ROOT / "component/site/tmpl", ROOT / "component/administrator/tmpl"]
FORBIDDEN = {
    "raw_SQL_or_database_access": re.compile(
        r"(?:\b(?:SELECT|UPDATE|INSERT|DELETE)\s+.{0,120}\b(?:FROM|INTO|WHERE)\b|"
        r"->(?:setQuery|createQuery|loadResult|loadObject|execute)\s*\(|"
        r"(?:Factory::getDbo|->getDatabase|DatabaseInterface|->getDbo)\s*\(?)",
        re.I | re.S,
    ),
    "authorisation_in_template": re.compile(
        r"(?:->authorise\s*\(|->getAuthorisedViewLevels\s*\(|"
        r"->checkToken\s*\(|\b(?:ACL|AccessRules)::(?:check|allow)\s*\()",
        re.I,
    ),
    "domain_mutation_in_template": re.compile(
        r"->(?:transactionStart|transactionCommit|transactionRollback|"
        r"insertObject|updateObject|deleteObject)\s*\(",
        re.I,
    ),
}


def audit(text: str) -> list[str]:
    return [label for label, pattern in FORBIDDEN.items() if pattern.search(text)]


def check() -> tuple[int, int]:
    templates = sorted(path for directory in VIEWS for path in directory.rglob("*.php"))
    if len(templates) < 10:
        raise AssertionError("SECDEV-01: unexpected template inventory shrink")
    problems = {str(path.relative_to(ROOT)): audit(path.read_text(encoding="utf-8"))
                for path in templates}
    problems = {path: hits for path, hits in problems.items() if hits}
    if problems:
        raise AssertionError(f"SECDEV-01: MVC violations in shipped templates: {problems}")
    gates = (ROOT / "tools/check.py").read_text(encoding="utf-8")
    assert "tests/sec-p1-controller-gates.py" in gates, "Controller inventory gate missing"
    assert "tests/secdev01-mvc-boundary.py" in gates, "MVC boundary gate not mandatory"
    provider = (ROOT / "component/administrator/services/provider.php").read_text(encoding="utf-8")
    assert "MVCFactory" in provider and "ComponentDispatcherFactory" in provider
    models = list((ROOT / "component").glob("*/src/Model/*.php"))
    return len(templates), len(models)


def fixture_tests() -> None:
    bad = (
        "$db->setQuery($query)->execute();",
        "$db = $this->getDatabase();",
        "SELECT id FROM #__jlms_courses WHERE access = 1",
        "$actor->authorise('core.edit', 'com_jlms')",
        "$db->transactionStart();",
    )
    for snippet in bad:
        assert audit(snippet), f"SECDEV-01 MVC violation undetected: {snippet}"
    safe = (
        "<?= $this->escape($item->title) ?>",
        "<?= $this->form->renderFieldset('details') ?>",
        "<?= (int) $item['enrollment_id'] ?>",
    )
    for snippet in safe:
        assert not audit(snippet), "SECDEV-01 false positive in presentational template"


if __name__ == "__main__":
    fixture_tests()
    templates, models = check()
    print(f"SECDEV-01 Joomla MVC boundary: {templates} packaged templates, {models} model classes, no business SQL/authorization in layouts")
