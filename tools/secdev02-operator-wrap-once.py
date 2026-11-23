#!/usr/bin/env python3
"""One-shot operator wrapping, guarded by lexical identity and PHPCS monotonicity."""
from collections import Counter
from pathlib import Path
import json
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
SNIFFER = ROOT / ".runtime/secdev02-phpcs/vendor/bin/phpcs"
WRAPPER = ROOT / "tools/secdev02-operator-wrap-once.php"


def scan(filename: str) -> Counter:
    result = subprocess.run(
        [str(SNIFFER), "--standard=PSR12", "--report=json", "--no-cache", filename],
        cwd=ROOT, capture_output=True, text=True, check=False, timeout=35,
    )
    report = json.loads(result.stdout)
    return Counter(
        message["source"] for file in report["files"].values()
        for message in file["messages"]
    )


def main() -> None:
    filenames = [
        p.decode() for p in subprocess.check_output(
            ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
        ).split(b"\0") if p
    ]
    excluded = ("tools/secdev02-operator-wrap-once.php",)
    accepted = []
    skipped = Counter()
    for filename in filenames:
        if filename in excluded:
            continue
        path = ROOT / filename
        original = path.read_bytes()
        if not any(len(line) > 120 for line in original.splitlines()):
            continue
        before = scan(filename)
        cmd = subprocess.run(
            ["php", str(WRAPPER), filename], cwd=ROOT,
            capture_output=True, check=False, timeout=20,
        )
        if cmd.returncode != 0:
            skipped["token mismatch or error"] += 1
            continue
        if path.read_bytes() == original:
            skipped["no safe operator position"] += 1
            continue
        syntax = subprocess.run(
            ["php", "-l", filename], cwd=ROOT, capture_output=True, timeout=20,
        )
        if syntax.returncode != 0:
            path.write_bytes(original)
            skipped["syntax regression"] += 1
            continue
        after = scan(filename)
        gain = sum(before.values()) - sum(after.values())
        if (after - before) or gain <= 0:
            path.write_bytes(original)
            skipped["style regression or no gain"] += 1
            continue
        accepted.append((filename, gain))
    print(f"Accepted {len(accepted)} files, removed {sum(x[1] for x in accepted)} findings")
    print("Reasons skipped:", skipped)
    print("Accepted files:", accepted)


if __name__ == "__main__":
    try:
        main()
    except (OSError, ValueError, RuntimeError, subprocess.SubprocessError) as err:
        print(f"SECDEV-02 wrapping stopped: {err}", file=sys.stderr)
        raise SystemExit(2)
