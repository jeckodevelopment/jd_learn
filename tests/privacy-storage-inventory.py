#!/usr/bin/env python3
"""Fail when production JD Learn JavaScript starts using undocumented browser storage."""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
DOCS = (
    ROOT / 'docs/PRIVACY_COOKIES.md',
    ROOT / 'docs/PRIVACY_COOKIES_EN.md',
)
EXPECTED = {'jdlearn.video.speed.v1', 'jdlearn.video.language.v1'}
CALL = re.compile(r"""\blocalStorage\s*(?:\?\.)?\s*\.?(?:getItem|setItem|removeItem)\s*\(\s*(['"])([^'"]+)\1""")
ANY_CALL = re.compile(r'\blocalStorage\b')
roots = [ROOT / 'component/media/js', ROOT / 'plugins/jlmsvideo']
seen = set()
for base in roots:
    for path in base.rglob('*.js'):
        source = path.read_text(encoding='utf-8')
        calls = list(CALL.finditer(source))
        # Every usage must be an explicit, statically reviewable key; fail closed
        # instead of silently dropping computed/dynamic localStorage access.
        if len(calls) != len(list(ANY_CALL.finditer(source))):
            raise AssertionError(f'Untracked or dynamic localStorage usage: {path.relative_to(ROOT)}')
        seen.update(match.group(2) for match in calls)
if seen != EXPECTED:
    raise AssertionError(f'Browser storage inventory differs: expected={sorted(EXPECTED)}, found={sorted(seen)}')
for doc in DOCS:
    content = doc.read_text(encoding='utf-8')
    for key in EXPECTED:
        if not any(line.startswith('| `' + key + '` |') for line in content.splitlines()):
            raise AssertionError(f'{doc.name} lacks inventory row for {key}')
print('Privacy storage inventory: two documented first-party keys; no untracked direct calls')
