#!/usr/bin/env python3
"""SECDEV-02: coordinate multiple large safe-refactor batches in one PR.

Run the existing proven per-expression formatter in two global passes:
  * first prioritize the highest-debt source and integration test files;
  * then revisit the entire source/test inventory with a broader selection.
Keep all existing PSR-12 rules, PHP token identity and source contracts.
Separate runs are reproducible with --pass N; no automatic PR creation.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import subprocess
import sys

from secdev02_lexer_bulk_wrap import ROOT
from secdev02_granular_expression_batch import longlines


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--phpcs", type=Path, required=True)
    ap.add_argument("--pass", dest="pass_num", type=int, choices=(1, 2), required=True)
    ap.add_argument("--self-test", action="store_true")
    args = ap.parse_args()
    if args.self_test:
        assert longlines(b"$a = 1;\n") == []
        print("SECDEV-02 coordinated bulk fixtures: OK")
        return 0
    settings = {
        1: ("100", "12", "10"),
        2: ("175", "8", "7"),
    }
    top, examined, fixed = settings[args.pass_num]
    result = subprocess.run(
        [
            sys.executable,
            str(ROOT / "tools/secdev02_granular_expression_batch.py"),
            "--phpcs", str(args.phpcs),
            "--top-files", top,
            "--max-examined", examined,
            "--max-fixes", fixed,
        ],
        cwd=ROOT, check=False
    )
    return result.returncode


if __name__ == "__main__":
    raise SystemExit(main())
