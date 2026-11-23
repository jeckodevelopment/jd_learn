#!/usr/bin/env python3
"""SECDEV-02: PSR-12 audit using real PHP_CodeSniffer, with regression detection.

Historical findings are explicitly counted. This is not a declaration that
the project is fully PSR-12 compliant until that count reaches zero.
"""
from __future__ import annotations

from collections import Counter
import argparse
import io
import json
from pathlib import Path
import subprocess
import sys
import tarfile
import tempfile

ROOT = Path(__file__).resolve().parents[1]


def tracked_php(root: Path) -> list[str]:
    result = subprocess.run(["git", "-C", str(ROOT), "ls-files", "-z", "--", "*.php"],
                            capture_output=True, check=True, timeout=40)
    return [x.decode("utf-8") for x in result.stdout.split(b"\0")
            if x and (root / x.decode("utf-8")).is_file()]


def signatures(phpcs: Path, root: Path, paths: list[str]) -> Counter[tuple[str, str, str, str]]:
    if not paths:
        raise RuntimeError("No PHP sources to audit")
    command = [str(phpcs), "--standard=PSR12", "--report=json",
               "--no-cache", "--encoding=utf-8", *paths]
    process = subprocess.run(command, cwd=root, text=True, capture_output=True,
                             timeout=240, check=False)
    try:
        report = json.loads(process.stdout)
        files = report["files"]
    except (json.JSONDecodeError, KeyError, TypeError) as exc:
        raise RuntimeError("PHPCS JSON report missing or invalid") from exc
    if process.returncode not in (0, 1, 2):
        raise RuntimeError(f"PHPCS invocation failed with exit {process.returncode}")
    findings: Counter[tuple[str, str, str, str]] = Counter()
    for filename, details in files.items():
        relative = Path(filename)
        if relative.is_absolute():
            relative = relative.relative_to(root)
        if ".." in relative.parts:
            raise RuntimeError("PHPCS returned unsafe source path")
        lines = (root / relative).read_text(encoding="utf-8").splitlines()
        for hit in details.get("messages", []):
            row = hit["line"]
            if not isinstance(row, int) or row < 1 or row > len(lines):
                raise RuntimeError("PHPCS returned invalid source line")
            # Source + diagnostic + stripped offending line: line-number changes
            # don't count as regressions, but duplicate violations do.
            findings[(relative.as_posix(), hit["source"], hit["message"],
                      lines[row - 1].strip())] += 1
    return findings


def baseline(root: Path, revision: str, target: Path) -> list[str]:
    archive = subprocess.run(["git", "-C", str(root), "archive", "--format=tar", revision],
                             capture_output=True, check=True, timeout=60)
    with tarfile.open(fileobj=io.BytesIO(archive.stdout)) as bundle:
        for member in bundle.getmembers():
            resolved = (target / member.name).resolve()
            if not resolved.is_relative_to(target.resolve()) or member.issym() or member.islnk():
                raise RuntimeError("Unsafe baseline archive member")
        bundle.extractall(target, filter="data")
    return sorted(str(p.relative_to(target)) for p in target.rglob("*.php")
                  if p.is_file() and ".runtime" not in p.parts)


def diff(current: Counter, old: Counter) -> Counter:
    return current - old


def self_test() -> None:
    a = ("component/site/src/Thing.php", "PSR12.ControlStructures.ControlStructureSpacing",
         "Expected 1 space", "if ($x) {")
    original = Counter({a: 1})
    assert not diff(original, original)
    altered = original.copy()
    altered[a] += 1
    assert diff(altered, original)[a] == 1
    updated = Counter()
    assert not diff(updated, original)
    new = ("plugins/system/something.php", "PSR12.Files.FileHeader", "Missing header", "<?php")
    assert diff(Counter({new: 1}), original)[new] == 1


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", default=".runtime/secdev02-phpcs/vendor/bin/phpcs")
    parser.add_argument("--base-ref", default="origin/main")
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("SECDEV-02 PSR-12 baseline/duplicate/new-file negative fixtures: OK")
        return 0
    phpcs = (ROOT / args.phpcs).resolve()
    if not phpcs.is_file():
        print("SECDEV-02: pinned PHP_CodeSniffer tool unavailable", file=sys.stderr)
        return 2
    try:
        now = signatures(phpcs, ROOT, tracked_php(ROOT))
        with tempfile.TemporaryDirectory(prefix="jdlearn-psr12-base-") as directory:
            source = Path(directory)
            files = baseline(ROOT, args.base_ref, source)
            before = signatures(phpcs, source, files)
        introduced = diff(now, before)
    except (RuntimeError, OSError, ValueError, subprocess.CalledProcessError,
            subprocess.TimeoutExpired) as exc:
        print(f"SECDEV-02 PSR-12 audit failed: {exc}", file=sys.stderr)
        return 2
    print(f"SECDEV-02 PHPCS PSR-12: historical={sum(before.values())}; "
          f"current={sum(now.values())}; introduced={sum(introduced.values())}")
    for (filename, source, _message, _code), count in sorted(introduced.items())[:40]:
        print(f"New PSR-12 finding: {filename}: {source} x{count}", file=sys.stderr)
    if now:
        print("SECDEV-02 PSR-12 debt remains; this gate does NOT certify full PSR-12 compliance.")
    return 1 if introduced else 0


if __name__ == "__main__":
    raise SystemExit(main())
