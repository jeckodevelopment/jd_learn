#!/usr/bin/env python3
"""Fix complete long PHP arrays instead of breaking individual operators.

Compose bracket-aware PHP array reflow, PHPCBF indentation normalization,
and strict lexer identity/source-contract/PHPCS per-rule no-regression gates.
Repeat locally within each file to handle nested array structures.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_array_reflow_bulk import scan, propose
from secdev02_calls_reflow_bulk import required_source_fragments

LIMIT=120


def fix_file(path:Path,phpcs:Path,phpcbf:Path,required:set[bytes],passes:int)->tuple[int,int]:
    original=path.read_bytes()
    token_hash=lex(path)["hash"]
    baseline=phpcs_counts(phpcs,path)
    if not baseline["Generic.Files.LineLength.TooLong"]:
        return 0,0
    total_removed=0
    cycles=0
    for cycle in range(passes):
        before=path.read_bytes()
        old_counts=phpcs_counts(phpcs,path)
        pairs=scan(path)
        if not pairs:
            break
        # Outer arrays containing several elements and inner independent
        # arrays receive separate proposals, never changing array keys/values.
        groups=[pairs]
        candidates=sorted(pairs,key=lambda x:(x[1]-x[0]),reverse=True)[:12]
        groups.extend([[item] for item in candidates if item[2]])
        best=before
        best_score=sum(old_counts.values())
        for group in groups:
            for width in (120,110,100,90):
                draft=propose(before,group,width)
                if draft==before:
                    continue
                if any(s in original and s not in draft for s in required):
                    continue
                path.write_bytes(draft)
                if not lint(path) or lex(path)["hash"]!=token_hash:
                    continue
                for normalized in (False,True):
                    if normalized:
                        subprocess.run(
                            [str(phpcbf),"--standard=PSR12","--no-cache",str(path)],
                            capture_output=True,check=False,timeout=70,
                        )
                    if not lint(path) or lex(path)["hash"]!=token_hash:
                        break
                    result=path.read_bytes()
                    if any(s in original and s not in result for s in required):
                        break
                    after=phpcs_counts(phpcs,path)
                    if any(after[k]>old_counts[k] for k in after):
                        if normalized:
                            break
                    else:
                        score=sum(after.values())
                        if score<best_score:
                            best_score=score
                            best=result
                    if not normalized:
                        path.write_bytes(draft)
        path.write_bytes(best)
        if best==before:
            break
        total_removed+=sum(old_counts.values())-best_score
        cycles+=1
    return cycles,total_removed


def main()->int:
    ap=argparse.ArgumentParser()
    ap.add_argument("--phpcs",type=Path)
    ap.add_argument("--phpcbf",type=Path)
    ap.add_argument("--max-files",type=int,default=180)
    ap.add_argument("--passes",type=int,default=3)
    ap.add_argument("--self-test",action="store_true")
    args=ap.parse_args()
    if args.self_test:
        src=b"$x = ["+b"'field' => 'value', "*10+b"'last' => 'yes'];\n"
        start=src.index(b"[")
        end=src.index(b"]",start)
        commas=[i for i,c in enumerate(src) if c==44]
        assert propose(src,[[start,end,commas]],120)!=src
        print("SECDEV-02 structural nested array reflow fixtures OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file() or not args.phpcbf or not args.phpcbf.is_file():
        ap.error("Pinned PHPCS and PHPCBF required")
    protected=required_source_fragments()
    tracked=subprocess.check_output(["git","ls-files","-z","--","*.php"],cwd=ROOT).split(b"\0")
    ranked=[]
    for item in tracked:
        if not item:
            continue
        name=item.decode()
        path=ROOT/name
        if not path.exists():
            continue
        count=sum(len(line)>LIMIT for line in path.read_bytes().splitlines())
        if count:
            ranked.append((count,name))
    ranked.sort(reverse=True)
    modified=removed=rejected=0
    for _,name in ranked[:args.max_files]:
        path=ROOT/name
        cycles,gain=fix_file(
            path,args.phpcs,args.phpcbf,protected.get(name,set()),args.passes,
        )
        if gain:
            modified+=1
            removed+=gain
            print(f"ARRAY GROUP accepted {name} cycles={cycles} removed={gain}",flush=True)
        else:
            rejected+=1
    print(f"ARRAY GROUP RESULT accepted_files={modified} rejected={rejected} fixed={removed}",flush=True)
    return 0


if __name__=="__main__":
    raise SystemExit(main())
