#!/usr/bin/env python3
"""Extract Joomla test symbol declarations from executable CLI entry points.

Moves only top-level, single-line define() statements into separately included
definition-only files. Functions are extracted by the established tokenizer
and kept with their exact bodies/imports. Each file is transactionally checked
with PHP lint, pinned PSR-12 counters and source contracts; no suppressions.
"""
from __future__ import annotations
import argparse
from collections import Counter
from pathlib import Path
import re
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments
from secdev02_psr1_helper_extraction import inspect, rewrite

DEFINITION = re.compile(rb"(?m)^\\?define\([^\r\n;]*\);[ \t]*(?:\r?\n|$)")

def groups(data: bytes):
    matches = list(DEFINITION.finditer(data))
    result = []
    for hit in matches:
        if result and data[result[-1][-1].end():hit.start()].strip() == b"" and (
            hit.start() - result[-1][-1].end()
        ) <= 4:
            result[-1].append(hit)
        else:
            result.append([hit])
    return result

def make_declarations(data: bytes, stem: str):
    blocks = groups(data)
    creates = {}
    for no, block in enumerate(blocks, 1):
        filename = f"{stem}.bootstrap-defines-{no:02d}.php"
        helper = b"<?php\n\ndeclare(strict_types=1);\n\n"
        helper += b"\n".join(match.group().strip() for match in block) + b"\n"
        creates[filename] = helper
    transformed = data
    for no in range(len(blocks), 0, -1):
        block = blocks[no - 1]
        filename = f"{stem}.bootstrap-defines-{no:02d}.php"
        replacement = (
            "\nrequire_once __DIR__ . '/helpers/" + filename + "';\n"
        ).encode()
        transformed = transformed[:block[0].start()] + replacement + transformed[block[-1].end():]
    return transformed, creates

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--phpcs", type=Path, required=True)
    parser.add_argument("--phpcbf", type=Path, required=True)
    parser.add_argument("--self-test", action="store_true")
    args = parser.parse_args()
    if args.self_test:
        s = b"<?php\n\ndeclare(strict_types=1);\n$root='x';\n\\define('_JEXEC',1);\n\\define('JPATH_BASE',$root);\nrequire 'framework';\n\\define('JPATH_COMPONENT',JPATH_BASE);\n"
        out, new = make_declarations(s, "fixture")
        assert len(new) == 2 and b"\\define" not in out
        print("SECDEV-02 PSR1 declaration splitting fixtures: OK")
        return 0
    if not args.phpcs.is_file() or not args.phpcbf.is_file():
        parser.error("Pinned PHP_CodeSniffer and PHP_CodeBeautifier required")
    contract = required_source_fragments()
    accepted = rejected = removed = 0
    for path in sorted((ROOT / "tests").glob("*.php")):
        original = path.read_bytes()
        if b"<?php" not in original[:10]:
            continue
        original_counts = phpcs_counts(args.phpcs, path)
        if original_counts["PSR1.Files.SideEffects.FoundWithSymbols"] == 0:
            continue
        rel = path.relative_to(ROOT).as_posix()
        entry, declaration_helpers = make_declarations(original, path.stem)
        helper_dir = path.parent / "helpers"
        function_name = f"{path.stem}.functions.php"
        new_helpers = dict(declaration_helpers)
        keep = False
        created = []
        if entry != original:
            path.write_bytes(entry)
        try:
            manifest = inspect(path)
            # Closure capture use($value) is not a namespace import. The
            # generic tokenizer reports it at global depth before the body.
            manifest["imports"] = [
                (a, b) for a, b in manifest["imports"]
                if not re.match(rb"\\s*use\\s*\\(", entry[a:b])
            ]
            function_pair = rewrite(entry, manifest, function_name)
            if function_pair:
                entry, helper_content = function_pair
                # Avoid a new end-of-file-newline warning from removed bodies.
                entry = entry.rstrip(b"\r\n") + b"\n"
                new_helpers[function_name] = helper_content
            # Preserve the mandated single blank line after strict_types.
            entry = re.sub(
                rb"(declare\\s*\\(\\s*strict_types\\s*=\\s*1\\s*\\)\\s*;)\\n{3,}",
                rb"\\1\\n\\n", entry,
            )
            if not new_helpers:
                rejected += 1
                continue
            if any((helper_dir / name).exists() for name in new_helpers):
                rejected += 1
                continue
            if any(fragment in original and fragment not in entry
                   for fragment in contract.get(rel, set())):
                rejected += 1
                continue
            helper_dir.mkdir(parents=True, exist_ok=True)
            path.write_bytes(entry)
            for name, content in new_helpers.items():
                helper = helper_dir / name
                helper.write_bytes(content)
                created.append(name)
                if name == function_name:
                    subprocess.run(
                        [str(args.phpcbf), "--standard=PSR12", "--no-cache", str(helper)],
                        capture_output=True, timeout=45, check=False
                    )
            valid = lint(path) and all(lint(helper_dir / name) for name in new_helpers)
            if valid:
                after = phpcs_counts(args.phpcs, path)
                valid = (
                    after["PSR1.Files.SideEffects.FoundWithSymbols"] == 0
                    and all(after[key] <= original_counts[key] for key in after)
                    and sum(after.values()) < sum(original_counts.values())
                    and all(not phpcs_counts(args.phpcs, helper_dir / name)
                            for name in new_helpers)
                )
            if valid:
                keep = True
                accepted += 1
                delta = sum(original_counts.values()) - sum(after.values())
                removed += delta
                print(f"PSR1 SAFE accepted {rel}: -{delta}, helpers={len(new_helpers)}", flush=True)
                continue
            rejected += 1
            print(f"PSR1 SAFE rejected {rel}: semantics/style gate", flush=True)
        except (OSError, ValueError, subprocess.CalledProcessError, subprocess.TimeoutExpired) as exc:
            rejected += 1
            print(f"PSR1 SAFE rejected {rel}: {type(exc).__name__}", flush=True)
        finally:
            if not keep:
                path.write_bytes(original)
                for name in created:
                    (helper_dir / name).unlink(missing_ok=True)
    print(f"PSR1 SAFE RESULT accepted_files={accepted} rejected={rejected} removed={removed}", flush=True)
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
