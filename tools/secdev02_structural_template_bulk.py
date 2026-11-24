#!/usr/bin/env python3
"""Conservative bulk template reflow: split long HTML tags and block boundaries.

Only T_INLINE_HTML spans are edited; PHP tokens, literal attributes and the
source-to-render HTML text are unchanged except for boundary whitespace.
Every file is independently checked with PHP lint, source contracts, and
strict PHPCS per-rule non-regression. The original is restored on rejection.
"""
from __future__ import annotations

import argparse
import json
import re
import subprocess
from pathlib import Path

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

LIMIT = 120
SCAN = r'''
$s = file_get_contents($argv[1]);
$offset=0; $parts=[];
foreach (token_get_all($s) as $token) {
  $value = is_array($token) ? $token[1] : $token;
  if (is_array($token) && $token[0] === T_INLINE_HTML) {
    $parts[] = [$offset, $offset + strlen($value)];
  }
  $offset += strlen($value);
}
echo json_encode($parts, JSON_THROW_ON_ERROR);
'''
# Only structural block boundaries. No whitespace injection between inline
# text nodes or before inline tags; no script/style/pre/textarea changes.
BLOCKS = rb'(?:div|section|header|footer|main|aside|nav|article|form|fieldset|ul|ol|li|dl|table|tbody|thead|tr|td|th|h[1-6]|p|details|summary)'
BOUNDARY = re.compile(rb'(?<=>)(?=<(?:/?' + BLOCKS + rb')(?=[\s>/]))', re.I)
ATTR = re.compile(rb'(?<=\x22)\x20(?=[a-zA-Z][\w:-]*=)')
# Avoid changing meaningful whitespace inside preformatted or raw-text tags.
RAW = re.compile(rb'<(?:script|style|pre|textarea)\b', re.I)


def spans(path: Path) -> list[tuple[int,int]]:
    p = subprocess.run(['php', '-r', SCAN, str(path)], check=True,
                       capture_output=True, text=True, timeout=30)
    return [tuple(x) for x in json.loads(p.stdout)]


def candidate(data: bytes, regions: list[tuple[int,int]],
              mode: str, extra: int) -> bytes:
    positions = []
    for start, end in regions:
        chunk = data[start:end]
        if RAW.search(chunk):
            # Conservatively ignore mixed raw HTML to avoid altering JS/CSS.
            continue
        pattern = BOUNDARY if mode == 'blocks' else ATTR
        for match in pattern.finditer(chunk):
            point = start + match.start()
            line_start = data.rfind(b'\n', 0, point) + 1
            line_end = data.find(b'\n', point)
            if line_end < 0: line_end = len(data)
            if line_end - line_start <= LIMIT:
                continue
            if point - line_start < 25 or point >= line_end - 10:
                continue
            # Attribute splitter is allowed only after quoted HTML attribute.
            # T_INLINE_HTML scan prevents touching PHP code or strings.
            if mode == 'attrs' and (point < 1 or data[point - 1:point] != b'"'):
                continue
            positions.append(point)
    edits = []
    for point in sorted(set(positions)):
        start = data.rfind(b'\n', 0, point) + 1
        line = data[start:point]
        indent = len(line) - len(line.lstrip(b' \t'))
        if indent > 30: continue
        pad = b' ' * min(indent + extra, 28)
        if mode == 'attrs':
            if data[point:point+1] != b' ': continue
            edits.append((point, 1, b'\n' + pad))
        else:
            edits.append((point, 0, b'\n' + pad))
    output = bytearray(data)
    for pos, width, value in reversed(edits):
        output[pos:pos+width] = value
    return bytes(output)


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument('--phpcs', type=Path)
    ap.add_argument('--self-test', action='store_true')
    args = ap.parse_args()
    if args.self_test:
        data = b'<div class="a" id="x"><section><p>hello</p></section></div>\n'
        assert candidate(data, [(0,len(data))], 'blocks', 2) == data
        big = b'<div id="x" class="abc" data-test="yes">' + b'<section><p>Hello</p></section>'*4 + b'</div>\n'
        change = candidate(big, [(0,len(big))], 'blocks', 2)
        assert change != big and change.replace(b'\n  ', b'') == big
        print('SECDEV-02 structural HTML-boundary fixtures OK')
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        ap.error('Pinned PHPCS executable required')
    required = required_source_fragments()
    tracked = subprocess.check_output(['git','ls-files','-z','--','*.php'],cwd=ROOT).split(b'\0')
    accepted = rejected = removed = 0
    for item in tracked:
        if not item: continue
        name = item.decode('utf-8')
        if '/tmpl/' not in name: continue
        path = ROOT/name
        if not path.is_file(): continue
        original = path.read_bytes()
        if not any(len(row)>LIMIT for row in original.splitlines()): continue
        parts = spans(path)
        if not parts: continue
        protected = required.get(name,set())
        before = phpcs_counts(args.phpcs,path)
        score = sum(before.values())
        if not score: continue
        best=original
        for strategy in ('blocks','attrs','both'):
            for extra in (2,4,0,8):
                trial = original
                if strategy in ('blocks','both'):
                    trial=candidate(trial,spans(path),'blocks',extra)
                if strategy in ('attrs','both'):
                    # Recomputed coordinates after any first transform.
                    path.write_bytes(trial)
                    trial=candidate(trial,spans(path),'attrs',extra)
                path.write_bytes(original)
                if trial==original or any(p in original and p not in trial for p in protected):
                    continue
                path.write_bytes(trial)
                if not lint(path):
                    continue
                after=phpcs_counts(args.phpcs,path)
                if any(after[k]>before[k] for k in after): continue
                total=sum(after.values())
                if total<score:
                    best,score=trial,total
        path.write_bytes(best)
        if best != original:
            accepted+=1
            removed+=sum(before.values())-score
            print(f'TEMPLATE accepted {name} removed={sum(before.values())-score}',flush=True)
        else: rejected+=1
    print(f'SECDEV-02 structural template accepted_files={accepted} rejected_files={rejected} net_removed={removed}',flush=True)
    return 0


if __name__=='__main__':
    raise SystemExit(main())
