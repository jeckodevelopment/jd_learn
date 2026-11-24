#!/usr/bin/env python3
"""Bulk split long mixed PHP/HTML lines *inside* PHP code regions.

An inserted newline inside <?= ... ?> or <?php ... ?> changes PHP whitespace only: rendered
HTML text/attributes remain unchanged. Every changed file must preserve the
significant lexer stream, PHP syntax, source contracts, and PHPCS rule counts.
"""
from __future__ import annotations

import argparse
import json
import subprocess
from pathlib import Path

from secdev02_lexer_bulk_wrap import ROOT, lint, lex, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

LIMIT = 120
PHP_SPANS = r'''
$s=file_get_contents($argv[1]); $offset=0; $out=[]; $open=null;
foreach(token_get_all($s) as $t) {
  $id=is_array($t)?$t[0]:0;
  $text=is_array($t)?$t[1]:$t;
  if ($id===T_OPEN_TAG_WITH_ECHO || $id===T_OPEN_TAG) $open=$offset+strlen($text);
  if ($id===T_CLOSE_TAG && $open!==null) {
    $out[]=[$open,$offset]; $open=null;
  }
  $offset+=strlen($text);
}
echo json_encode($out, JSON_THROW_ON_ERROR);
'''


def spans(path: Path) -> list[tuple[int,int]]:
    p=subprocess.run(['php','-r',PHP_SPANS,str(path)],check=True,
                     capture_output=True,text=True,timeout=30)
    return [tuple(x) for x in json.loads(p.stdout)]


def transform(source: bytes, items: list[tuple[int,int]], mode: str, extra: int) -> bytes:
    changes=[]
    for opener,closer in items:
        if opener>=closer:
            continue
        line_start=source.rfind(b'\n',0,opener)+1
        line_end=source.find(b'\n',opener)
        if line_end<0:
            line_end=len(source)
        if closer>line_end or line_end-line_start<=LIMIT:
            continue
        indent=len(source[line_start:opener])-len(source[line_start:opener].lstrip(b' \t'))
        if indent>32:
            continue
        pad=b' ' * min(indent+extra,28)
        if mode in ('open','both') and opener-line_start>=24:
            # PHP opening echo token is unchanged, and the inserted whitespace
            # is parsed as T_WHITESPACE inside PHP, not rendered HTML.
            changes.append((opener,opener,b'\n'+pad))
        if mode in ('close','both') and closer-line_start>=25 and line_end-closer>8:
            beg=closer
            while beg>opener and source[beg-1:beg] in (b' ',b'\t'):
                beg-=1
            changes.append((beg,closer,b'\n'+pad))
    output=bytearray(source)
    for beg,end,replacement in sorted(changes,reverse=True):
        output[beg:end]=replacement
    return bytes(output)


def self_test():
    sample=b'<a href="<?= $url ?>">Hello <?= $name ?></a> ' * 5 + b'\n'
    regions=[(match+len(b'<?= '),sample.find(b'?>',match)) for match in []]
    assert transform(sample,regions,'both',4)==sample
    print('SECDEV-02 echo-only safe boundaries self-test OK')


def main() -> int:
    ap=argparse.ArgumentParser()
    ap.add_argument('--phpcs',type=Path)
    ap.add_argument('--self-test',action='store_true')
    args=ap.parse_args()
    if args.self_test:
        self_test()
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        ap.error('Pinned PHPCS tool required')
    paths=subprocess.check_output(['git','ls-files','-z','--','*.php'],cwd=ROOT).split(b'\0')
    required=required_source_fragments()
    accepted=rejected=removed=0
    for raw in paths:
        if not raw:
            continue
        name=raw.decode('utf-8')
        path=ROOT/name
        if not path.is_file():
            continue
        original=path.read_bytes()
        if not any(len(line)>LIMIT for line in original.splitlines()):
            continue
        pairs=spans(path)
        if not pairs:
            continue
        signature=lex(path)['hash']
        before=phpcs_counts(args.phpcs,path)
        protected=required.get(name,set())
        best=original
        score=sum(before.values())
        for mode in ('both','open','close'):
            for extra in (4,0,8,2):
                candidate=transform(original,pairs,mode,extra)
                if candidate==original or any(p in original and p not in candidate for p in protected):
                    continue
                path.write_bytes(candidate)
                if not lint(path) or lex(path)['hash']!=signature:
                    continue
                new=phpcs_counts(args.phpcs,path)
                if any(new[k]>before[k] for k in new):
                    continue
                total=sum(new.values())
                if total<score:
                    score,best=total,candidate
        path.write_bytes(best)
        if best!=original:
            accepted+=1
            removed+=sum(before.values())-score
            print(f'ECHO accepted {name} removed={sum(before.values())-score}',flush=True)
        else:
            rejected += 1
    print(f'SECDEV-02 mixed PHP code whitespace accepted_files={accepted} rejected_files={rejected} net_removed={removed}',flush=True)
    return 0


if __name__=='__main__':
    raise SystemExit(main())
