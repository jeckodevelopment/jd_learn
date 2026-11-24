#!/usr/bin/env python3
"""Identify and roll back only formatting candidates that break source gates.

Run after an initial build and formatter pass. Execute project source checks.
If a check fails, temporarily restore each changed PHP file independently,
prioritizing filenames mentioned by the failing assertion. Permanently
restore only if the failure disappears or advances to a different assertion.
Never mute, skip, weaken or rewrite the checks themselves.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import re
import subprocess
import sys

from secdev02_lexer_bulk_wrap import ROOT


def execute(command: list[str], timeout: int = 210) -> subprocess.CompletedProcess:
    return subprocess.run(
        command, cwd=ROOT, capture_output=True, text=True,
        timeout=timeout, check=False
    )


def fingerprint(result: subprocess.CompletedProcess) -> str:
    if result.returncode == 0:
        return "SUCCESS"
    lines = result.stderr.splitlines()
    assert_lines = [line.strip() for line in lines if (
        "AssertionError" in line or "SyntaxError" in line
        or "ModuleNotFoundError" in line or "FileNotFoundError" in line
    )]
    frames = [line.strip() for line in lines if (
        line.strip().startswith('File "') and ", line " in line
    )]
    if not assert_lines and not frames:
        return "UNKNOWN:" + "\n".join(lines[-8:])
    return (frames[-1] if frames else "") + " | " + (
        assert_lines[-1] if assert_lines else ""
    )



SOURCE_CHECKS = [
    ["python3", "tools/check.py"],
    ["php", "tests/aut04-migration-boundary.php"],
    ["php", "tests/aut-les-field-copy-boundary.php"],
    ["php", "tests/course-message-idempotency.php"],
    ["php", "tests/scheduler-guidance.php"],
    ["php", "tests/doc07-configuration-diagnostics.php"],
    ["php", "tests/catalog-literal-search.php"],
    ["php", "tests/cat04-public-metadata.php"],
    ["php", "tests/cat04-breadcrumb.php"],
    ["python3", "tests/frontend-contract-hooks.py"],
    ["python3", "tests/frontend-release-gate.py"],
    ["python3", "tests/frontend-catalog-options.py"],
    ["python3", "tests/frontend-menu-inheritance.py"],
    ["python3", "tests/frontend-course-details.py"],
    ["python3", "tests/frontend-classroom-layout.py"],
    ["python3", "tests/frontend-lesson-navigation.py"],
    ["python3", "tests/frontend-progress-feedback.py"],
    ["python3", "tests/frontend-my-courses.py"],
    ["python3", "tests/frontend-mycertificates.py"],
    ["python3", "tests/frontend-teachers-directory.py"],
    ["python3", "tests/database-byref-safety.py"],
]


def run_suite() -> tuple[list[str], subprocess.CompletedProcess]:
    for command in SOURCE_CHECKS:
        result = execute(command)
        if result.returncode:
            return command, result
    return ["suite", "all"], subprocess.CompletedProcess(
        args=["suite", "all"], returncode=0, stdout="", stderr=""
    )


def run() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    parser.add_argument("--max-reversions", type=int, default=45)
    parser.add_argument("--base-ref", default="HEAD")
    args = parser.parse_args()
    if args.self_test:
        assert fingerprint(subprocess.CompletedProcess([], 0, "", "")) == "SUCCESS"
        assert "AssertionError" in fingerprint(
            subprocess.CompletedProcess([], 1, "", 'File "/tmp/a.py", line 8\nAssertionError: x')
        )
        print("SECDEV-02 source-contract rollback fixtures: OK")
        return 0

    changed = execute(["git", "diff", "--name-only", "--diff-filter=M", args.base_ref, "--", "*.php"], 30)
    if changed.returncode:
        raise RuntimeError(changed.stderr)
    files = [name for name in changed.stdout.splitlines() if (ROOT / name).is_file()]
    original = {}
    for name in files:
        before = subprocess.run(
            ["git", "show", args.base_ref + ":" + name], cwd=ROOT,
            capture_output=True, check=True, timeout=30
        )
        original[name] = before.stdout
    reverted: list[str] = []
    command, status = run_suite()
    rounds = 0
    while status.returncode != 0 and rounds < args.max_reversions:
        previous = " ".join(command) + " | " + fingerprint(status)
        remain = [name for name in files if name not in reverted]
        if not remain:
            break
        # Most source inventories mention the specific controller/view/file.
        hints = status.stderr
        remain.sort(key=lambda n: (
            0 if Path(n).name in hints else 1,
            0 if n in hints else 1,
            n
        ))
        success = False
        for name in remain:
            path = ROOT / name
            candidate = path.read_bytes()
            path.write_bytes(original[name])
            trial = execute(command)
            changed_failure = fingerprint(trial) != fingerprint(status)
            if changed_failure:
                reverted.append(name)
                build = execute(["python3", "tools/build.py"])
                if build.returncode:
                    print(build.stderr[-1200:], file=sys.stderr)
                    return 1
                command, status = run_suite()
                rounds += 1
                print(
                    f"SECDEV-02 contract rollback: {name}; remaining failure "
                    f"{' '.join(command)}: {fingerprint(status)[:180]}", flush=True
                )
                success = True
                break
            path.write_bytes(candidate)
        if not success:
            print(
                "SECDEV-02: cannot isolate source contract failure: "
                + previous, file=sys.stderr
            )
            return 1
    if status.returncode:
        print(
            "SECDEV-02 source check still failing: " + status.stderr[-1800:],
            file=sys.stderr
        )
        return 1
    print(
        f"SECDEV-02 source contracts reconciled: "
        f"reverted_files={len(reverted)} of {len(files)}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(run())
