#!/usr/bin/env python3
"""One-shot PSR-12 remediation: accept only identical PHP tokens, lint and a strict rule-level reduction."""
from collections import Counter
from pathlib import Path
import json
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
SNIFFER = ROOT / ".runtime/secdev02-phpcs/vendor/bin/phpcs"
WRAPPER = ROOT / "tools/secdev02-wrap-disposable.php"


def sniff(file: str) -> Counter:
    result = subprocess.run(
        [str(SNIFFER), "--standard=PSR12", "--report=json", "--no-cache", file],
        capture_output=True, text=True, cwd=ROOT, timeout=25,
    )
    report = json.loads(result.stdout)
    return Counter(
        message["source"]
        for details in report["files"].values()
        for message in details["messages"]
    )


def main() -> None:
    names = [
        name.decode() for name in subprocess.check_output(
            ["git", "ls-files", "-z", "--", "*.php"], cwd=ROOT
        ).split(b"\0") if name
    ]
    # Omit the temporary formatter (it will be removed).
    names = [n for n in names if n != "tools/secdev02-wrap-disposable.php"]
    total = 0
    accepted: list[tuple[str, int, int]] = []
    for idx, name in enumerate(names):
        path = ROOT / name
        original = path.read_bytes()
        if not any(len(line) > 120 for line in original.splitlines()):
            continue
        before = sniff(name)
        best = original
        bestgain = 0
        bestmode = -1
        for mode in range(5):
            path.write_bytes(original)
            run = subprocess.run(
                ["php", str(WRAPPER), name, str(mode)], cwd=ROOT,
                capture_output=True, timeout=30,
            )
            if run.returncode:
                continue
            updated = path.read_bytes()
            if updated == original:
                continue
            lint = subprocess.run(["php", "-l", name], cwd=ROOT, capture_output=True, timeout=15)
            if lint.returncode:
                continue
            after = sniff(name)
            gain = sum(before.values()) - sum(after.values())
            if gain > bestgain and not (after - before):
                best = updated
                bestgain = gain
                bestmode = mode
        path.write_bytes(best)
        if bestgain:
            accepted.append((name, bestgain, bestmode))
            total += bestgain
        if idx % 100 == 0:
            print(f"Processed {idx}/{len(names)}, accepted {len(accepted)}, removed {total}", flush=True)
    print(f"Accepted {len(accepted)} files; removed {total} violations", flush=True)
    print("Top improvements:", sorted(accepted, key=lambda t: -t[1])[:30])


if __name__ == "__main__":
    try:
        main()
    except (OSError, RuntimeError, ValueError, subprocess.SubprocessError) as err:
        print(f"SECDEV-02 formatter failed: {err}", file=sys.stderr)
        raise SystemExit(2)
