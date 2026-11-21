#!/usr/bin/env python3
"""SECDEV-07: require verified Semgrep findings and negative controls.

Local rules only, metrics disabled: source is not uploaded to a scanner service.
External Joomla/hosting CVE verification remains a separate release requirement.
"""
import argparse
import json
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CONFIG = ROOT / "security/secdev07-semgrep.yml"
TARGETS = ("component", "libraries", "plugins", "packages", "tools", "scripts", "tests")
EXPECTED = {
    "jdlearn.php.dynamic-evaluation",
    "jdlearn.javascript.dynamic-evaluation",
    "jdlearn.python.dynamic-evaluation",
}
FIXTURES = {
    "bad.php": "<?php eval($payload);\n",
    "bad.js": "eval(userInput);\n",
    "bad.py": "eval(value)\n",
    "good.php": "<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8');\n",
    "good.js": "element.textContent = value;\n",
    "good.py": "print(value)\n",
}


def scan(paths: list[str]) -> tuple[set[str], int]:
    command = [
        "semgrep", "scan", "--config", str(CONFIG),
        "--metrics=off", "--no-git-ignore", "--json", "--quiet",
        "--exclude", ".runtime", "--exclude", "dist",
        "--exclude", ".git", "--exclude", "tests/fixtures",
        *paths,
    ]
    response = subprocess.run(command, cwd=ROOT, capture_output=True, text=True, timeout=240)
    if response.returncode not in (0, 1):
        raise RuntimeError("SECDEV-07 scanner unavailable or configuration failed")
    try:
        report = json.loads(response.stdout)
    except json.JSONDecodeError as error:
        raise RuntimeError("SECDEV-07 scanner did not produce a JSON report") from error
    errors = report.get("errors", [])
    if errors:
        raise RuntimeError("SECDEV-07 encountered scanner errors; cannot waive incomplete coverage")
    findings = report.get("results", [])
    if not isinstance(findings, list):
        raise RuntimeError("SECDEV-07 invalid scanner results")
    return {str(row.get("check_id", "")).split(".")[-2] + "." + str(row.get("check_id", "")).split(".")[-1]
            for row in findings}, len(findings)


def fixture_test() -> None:
    with tempfile.TemporaryDirectory(prefix="jdlearn-secdev07-") as directory:
        folder = Path(directory)
        for name, source in FIXTURES.items():
            (folder / name).write_text(source, encoding="utf-8")
        matched, count = scan([str(folder)])
        if not EXPECTED.issubset(matched) or count != 3:
            raise RuntimeError("SECDEV-07 negative fixtures were not rejected precisely")
        matched_clean, count_clean = scan([str(folder / name) for name in FIXTURES if name.startswith("good")])
        if matched_clean or count_clean:
            raise RuntimeError("SECDEV-07 valid fixture was rejected")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    arguments = parser.parse_args()
    try:
        if arguments.self_test:
            fixture_test()
            print("SECDEV-07 Semgrep: 3 dangerous patterns rejected, 3 safe controls allowed")
        else:
            matches, count = scan([str(ROOT / item) for item in TARGETS])
            print(f"SECDEV-07 Semgrep local security rules: {count} findings")
            if matches or count:
                return 1
    except (RuntimeError, OSError, subprocess.TimeoutExpired) as error:
        print(f"SECDEV-07 gate failure: {error}", file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
