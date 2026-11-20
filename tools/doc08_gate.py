#!/usr/bin/env python3
"""DOC-08: the 1.0.0 core flows must have real guides and separately recorded acceptance.

Preflight validates the inventory; --release additionally requires independent
manual evidence and explicit release sign-off. No CI result counts as manual QA.
"""
from pathlib import Path
import argparse
import json
import re

ROOT = Path(__file__).resolve().parents[1]
MATRIX = ROOT / "docs/CORE_FLOW_DOCUMENTATION_1.0.0.json"
REQUIRED = frozenset((
    "installation_upgrade", "course_editing", "programme_lessons",
    "course_publication", "catalogue_search", "pathways",
    "student_progress", "enrolments", "provider_setup",
    "provider_cloudflare", "provider_html5", "provider_youtube",
    "provider_vimeo", "mail_templates", "mail_dispatch",
    "certificates", "course_archive", "administration_acl",
    "dashboard", "frontend_overrides", "release_qa",
))
SAFE = re.compile(r"^[A-Za-z0-9_./-]+\.md$")


def check(matrix: dict, release: bool = False) -> None:
    if matrix.get("schema") != 1 or matrix.get("version") != "1.0.0":
        raise ValueError("DOC-08: unknown matrix schema or version")
    rows = matrix.get("flows")
    if not isinstance(rows, list) or not all(isinstance(row, dict) for row in rows):
        raise ValueError("DOC-08: flows must be an array of objects")
    ids = [row.get("id") for row in rows]
    if len(ids) != len(set(ids)) or set(ids) != REQUIRED:
        raise ValueError("DOC-08: incomplete, duplicated or out-of-scope core flow inventory")
    for row in rows:
        guide = row.get("guide", "")
        if not isinstance(guide, str) or not SAFE.fullmatch(guide) or guide.startswith("/") or ".." in Path(guide).parts:
            raise ValueError(f"DOC-08: unsafe guide reference for {row['id']}")
        path = ROOT / guide
        if not path.is_file() or path.stat().st_size < 150:
            raise ValueError(f"DOC-08: missing or empty operational guide for {row['id']}: {guide}")
        if row.get("guide_review") != "mapped" or not row.get("title") or not row.get("requirement"):
            raise ValueError(f"DOC-08: unreviewed guide/requirement for {row['id']}")
        state = row.get("manual_acceptance")
        evidence = row.get("evidence")
        if state not in ("pending", "passed", "failed"):
            raise ValueError(f"DOC-08: invalid manual acceptance state for {row['id']}")
        if state == "passed":
            if not isinstance(evidence, str) or not SAFE.fullmatch(evidence) or not (ROOT / evidence).is_file():
                raise ValueError(f"DOC-08: acceptance evidence missing for {row['id']}")
        elif evidence is not None:
            raise ValueError(f"DOC-08: non-passing row must not provide acceptance evidence for {row['id']}")
        if release and state != "passed":
            raise ValueError(f"DOC-08: unaccepted core flow blocks release: {row['id']}")
    if matrix.get("release_acceptance") not in ("pending", "passed", "failed"):
        raise ValueError("DOC-08: invalid global release gate state")
    if release and matrix["release_acceptance"] != "passed":
        raise ValueError("DOC-08: missing overall manual release acceptance")


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--release", action="store_true", help="fail closed unless all manual release evidence is present")
    args = parser.parse_args()
    check(json.loads(MATRIX.read_text(encoding="utf-8")), args.release)
    print(f"DOC-08: {len(REQUIRED)} documented core flows; release acceptance checked={args.release}")


if __name__ == "__main__":
    main()
