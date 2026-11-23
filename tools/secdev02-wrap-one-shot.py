#!/usr/bin/env python3
"""Temporary, reversible PHPCS-guided long-line fix runner for SECDEV-02."""
from collections import Counter
from pathlib import Path
import json
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
PHPCS = ROOT / ".runtime/secdev02-phpcs/vendor/bin/phpcs"
WRAPPER = ROOT / "tools/secdev02-wrap-one-shot.php"


def phpcs(paths: list[str]) -> dict[str, Counter]:
    process = subprocess.run(
        [str(PHPCS), "--standard=PSR12", "--report=json", "--no-cache", "--encoding=utf-8", *paths],
        cwd=ROOT, capture_output=True, text=True, check=False, timeout=240,
    )
    if process.returncode not in (0, 1, 2, 3):
        raise RuntimeError(f"PHPCS failed with status {process.returncode}")
    report = json.loads(process.stdout)
    result: dict[str, Counter] = {}
    for name, details in report["files"].items():
        relative = Path(name)
        if relative.is_absolute():
            relative = relative.relative_to(ROOT)
        result[relative.as_posix()] = Counter(
            message["source"] for message in details["messages"]
        )
    return result


def main() -> int:
    files = [
        p.decode() for p in subprocess.check_output(
            ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
        ).split(b"\0") if p
    ]
    files = [f for f in files if f != "tools/secdev02-wrap-one-shot.php"]
    before = phpcs(files)
    originals: dict[str, bytes] = {}
    for name in files:
        path = ROOT / name
        data = path.read_bytes()
        wrapped = subprocess.run(
            ["php", str(WRAPPER), name], cwd=ROOT,
            capture_output=True, check=False, timeout=25,
        )
        if wrapped.returncode == 3:
            # Strict token preservation is mandatory. Skip unsupported PHP
            # syntax rather than weakening that invariant or blocking the batch.
            continue
        if wrapped.returncode != 0:
            raise RuntimeError(f"Formatter process failed: {name}")
        if path.read_bytes() != data:
            originals[name] = data
            syntax = subprocess.run(
                ["php", "-l", name], cwd=ROOT, capture_output=True, timeout=20,
            )
            if syntax.returncode != 0:
                path.write_bytes(data)
                del originals[name]
    print(f"Attempted safe line wrapping in {len(originals)} PHP files", flush=True)
    if not originals:
        return 0
    after = phpcs(files)
    accepted = []
    rejected = []
    for name, original in originals.items():
        introduced = after.get(name, Counter()) - before.get(name, Counter())
        saved = sum(before.get(name, Counter()).values())
        current = sum(after.get(name, Counter()).values())
        if introduced or current >= saved:
            (ROOT / name).write_bytes(original)
            rejected.append((name, sum(introduced.values()), saved - current))
        else:
            accepted.append((name, saved - current))
    final = phpcs(files)
    regressions = {
        name: count for name in files
        if (count := sum((final.get(name, Counter()) - before.get(name, Counter())).values())) > 0
    }
    if regressions:
        raise RuntimeError(f"Unexpected regressions remain: {len(regressions)} files")
    print(f"Accepted: {len(accepted)} files; rejected: {len(rejected)}; "
          f"net findings removed: {sum(sum(before.get(n, Counter()).values()) - sum(final.get(n, Counter()).values()) for n in files)}")
    print("Accepted sample:", accepted[:15])
    print("Rejected sample:", rejected[:15])
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, ValueError, RuntimeError, subprocess.SubprocessError) as exc:
        print(f"SECDEV-02 safe formatter failed: {exc}", file=sys.stderr)
        raise SystemExit(2)
