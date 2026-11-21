#!/usr/bin/env python3
"""SECDEV-07: syntax validation of every tracked first-party Python/JS module.

This is language-wide syntax validation, NOT SAST, SCA, or a security certification.
PHP is linted separately by the existing PHP matrix jobs.
"""
from __future__ import annotations

import argparse
import ast
import subprocess
import sys
import tempfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
EXTENSIONS = {".py", ".js", ".mjs", ".cjs"}


def validate(path: Path) -> str | None:
    """Return a diagnostic without copying source or code snippets to CI logs."""
    try:
        if path.suffix == ".py":
            ast.parse(path.read_text(encoding="utf-8"), filename=path.name)
        else:
            # Browser .js files are ES modules (exports/imports). Node treats .js
            # as CommonJS without package.json, so parse module syntax via stdin.
            command = (["node", "--check", str(path)] if path.suffix == ".cjs"
                       else ["node", "--input-type=module", "--check"])
            result = subprocess.run(
                command,
                input=None if path.suffix == ".cjs" else path.read_text(encoding="utf-8"),
                capture_output=True, text=True, check=False, timeout=15,
            )
            if result.returncode:
                return f"{path.name}: JavaScript syntax rejected"
    except (SyntaxError, UnicodeError):
        return f"{path.name}: Python syntax or encoding rejected"
    except (OSError, subprocess.TimeoutExpired):
        return f"{path.name}: syntax validation unavailable"
    return None


def tracked_sources() -> list[Path]:
    result = subprocess.run(
        ["git", "-C", str(ROOT), "ls-files", "-z", "--", "*.py", "*.js", "*.mjs", "*.cjs"],
        capture_output=True, check=True,
    )
    return sorted(
        ROOT / name.decode("utf-8", "surrogateescape")
        for name in result.stdout.split(b"\0") if name
    )


def self_test() -> None:
    with tempfile.TemporaryDirectory(prefix="jdlearn-syntax-") as temp:
        folder = Path(temp)
        samples = {
            "valid.py": "def ok():\n    return 1\n",
            "invalid.py": "def broken(:\n    pass\n",
            "valid.mjs": "export const answer = 42;\n",
            "invalid.mjs": "export const broken = ;\n",
        }
        for name, content in samples.items():
            (folder / name).write_text(content, encoding="utf-8")
        for name in samples:
            error = validate(folder / name)
            assert (error is None) == name.startswith("valid"), (name, error)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        self_test()
        print("SECDEV-07 syntax checker positive/negative fixtures: passed")
        return 0
    paths = tracked_sources()
    if not paths:
        print("SECDEV-07: no tracked language sources; fail closed", file=sys.stderr)
        return 1
    errors = [diagnostic for path in paths if (diagnostic := validate(path))]
    for error in errors:
        print(error, file=sys.stderr)
    print(f"SECDEV-07: checked {len(paths)} tracked Python/JavaScript files; failures={len(errors)}")
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
