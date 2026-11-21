#!/usr/bin/env python3
"""SECDEV-07: detect *new* Ruff violations without hiding historical debt.

All checked Python sources are scanned. Existing debt is reported as counts,
not waived silently; the CI blocks every new finding signature per file.
"""
from __future__ import annotations
import argparse
from collections import Counter
import io
import json
import subprocess
import sys
import tarfile
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DIRECTORIES = ("tools", "scripts", "tests")


def invoke(working: Path) -> Counter[tuple[str, str, str]]:
    result = subprocess.run(
        ["ruff", "check", "--select", "E4,E7,E9",
         "--output-format", "json", *DIRECTORIES],
        cwd=working, capture_output=True, text=True, timeout=120,
    )
    if result.returncode not in (0, 1):
        raise RuntimeError("Ruff scanner unavailable")
    try:
        data = json.loads(result.stdout)
    except json.JSONDecodeError as error:
        raise RuntimeError("Ruff did not return a JSON report") from error
    findings = Counter()
    for row in data:
        name = Path(row["filename"])
        relative = name.relative_to(working) if name.is_absolute() else name
        path = working / relative
        # A signature is independent from line numbers so moving code does
        # not produce spurious new findings, but added duplicate issues do.
        line = path.read_text(encoding="utf-8").splitlines()[row["location"]["row"] - 1].strip()
        findings[(relative.as_posix(), row["code"], line)] += 1
    return findings


def verify(current: Counter, original: Counter) -> tuple[int, int]:
    introduced = current - original
    return sum(original.values()), sum(introduced.values())


def fixtures() -> None:
    before = Counter({("tools/legacy.py", "E701", "if x: pass"): 1})
    assert verify(before, before) == (1, 0)
    after = before.copy()
    after[("tools/new.py", "E701", "if y: pass")] += 1
    assert verify(after, before) == (1, 1)
    after[("tools/legacy.py", "E701", "if x: pass")] += 1
    assert verify(after, before) == (1, 2)
    print("SECDEV-07 Ruff gate: unchanged, new-file and duplicate regression fixtures OK")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-ref", default="origin/main")
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        fixtures()
        return 0
    try:
        current = invoke(ROOT)
        with tempfile.TemporaryDirectory(prefix="jdlearn-ruff-baseline-") as folder:
            base = Path(folder)
            archive = subprocess.run(
                ["git", "archive", "--format=tar", args.base_ref, *DIRECTORIES],
                cwd=ROOT, capture_output=True, timeout=60, check=True,
            )
            with tarfile.open(fileobj=io.BytesIO(archive.stdout)) as tar:
                for item in tar.getmembers():
                    dest = (base / item.name).resolve()
                    if not dest.is_relative_to(base.resolve()) or item.issym() or item.islnk():
                        raise RuntimeError("Unsafe historical Ruff baseline")
                tar.extractall(base, filter="data")
            historic = invoke(base)
        old_count, delta = verify(current, historic)
    except (RuntimeError, OSError, subprocess.CalledProcessError, subprocess.TimeoutExpired,
            KeyError, IndexError, ValueError) as error:
        print(f"SECDEV-07 Ruff baseline scanner failed: {error}", file=sys.stderr)
        return 2
    print(f"SECDEV-07 Ruff scanned all Python sources: historical findings={old_count}; "
          f"current findings={sum(current.values())}; introduced findings={delta}")
    if delta:
        for filename, code, _line in sorted((current - historic).keys())[:20]:
            print(f"New style finding: {filename}: {code}", file=sys.stderr)
    return 1 if delta else 0


if __name__ == "__main__":
    raise SystemExit(main())
