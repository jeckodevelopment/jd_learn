#!/usr/bin/env python3
"""SECDEV-02: greedy, per-long-line, lexer-exact PSR-12 whitespace remediation.

Earlier whole-file formatting variants were rejected because one candidate
introduced an unrelated style regression elsewhere in that file. This tool
proposes *one line at a time* and keeps it only after a strict per-file/per-rule
PHPCS reduction, exact PHP significant-token identity, PHP lint and source
contract preservation. HTML/PHP transition lines and comments are excluded.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_whitespace_bulk_refactor import spaces
from secdev02_calls_reflow_bulk import required_source_fragments

LIMIT = 120


def line_candidates(
    source: bytes, line_start: int, line_end: int,
    whitespace: list[list[int]], indentation: int,
) -> list[bytes]:
    line = source[line_start:line_end]
    if len(line) <= LIMIT or b"<?" in line or b"?>" in line:
        return []
    if line.lstrip().startswith((b"//", b"#", b"/*", b"*")):
        return []
    if indentation > 32:
        return []
    points = [
        (a,b) for a,b in whitespace
        if line_start+26 <= a and b <= line_end-8 and b-a <= 4
        and b"\n" not in source[a:b] and b"\r" not in source[a:b]
    ]
    if not points:
        return []
    proposals = []
    for target in (104, 96, 112, 88, 80):
        for extra in (4, 0, 8, 2, 12):
            cont = b" " * min(28, indentation+extra)
            selected = []
            current = line_start
            for _ in range(14):
                if line_end-current+(len(cont) if current>line_start else 0) <= LIMIT:
                    break
                allowance = target - (len(cont) if current>line_start else 0)
                options = [
                    (a,b) for a,b in points
                    if current + 22 <= a <= current+allowance
                ]
                if not options:
                    break
                # Prefer breaking at a comma, boolean expression boundary,
                # a fluent call or an array separator over arbitrary spaces.
                def rank(pair):
                    a,b=pair
                    left=source[max(line_start,a-5):a].rstrip()
                    right=source[b:min(line_end,b+5)].lstrip()
                    priority=(
                        10 if left.endswith(b",") else
                        9 if right.startswith((b"&&",b"||",b"??")) else
                        8 if right.startswith((b"->",b"?->")) else
                        7 if right.startswith((b"?",b":")) else
                        5 if right.startswith(b".") else
                        0
                    )
                    return (priority, a)
                a,b=max(options,key=rank)
                selected.append((a,b))
                current=b
            if not selected:
                continue
            out=bytearray(source)
            for a,b in reversed(selected):
                out[a:b]=b"\n"+cont
            updated=bytes(out)
            if updated != source and updated not in proposals:
                proposals.append(updated)
            if len(proposals)>=14:
                return proposals
    return proposals


def main() -> int:
    ap=argparse.ArgumentParser()
    ap.add_argument("--phpcs",type=Path)
    ap.add_argument("--self-test",action="store_true")
    ap.add_argument("--max-files",type=int,default=180)
    args=ap.parse_args()
    if args.self_test:
        example=b"    $value = functionCall("+b"$argument, "*14+b"$last);\n"
        whitespace=[[i,i+1] for i,v in enumerate(example) if v==32]
        result=line_candidates(example,0,len(example)-1,whitespace,4)
        assert result and b"\n        " in result[0]
        assert line_candidates(b"<?php echo 1; ?>",0,16,[],0)==[]
        print("SECDEV-02 incremental whitespace splitter self-test OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        ap.error("Pinned PHPCS binary required")
    contracts=required_source_fragments()
    names=subprocess.check_output(
        ["git","ls-files","-z","--","*.php"],cwd=ROOT
    ).split(b"\0")
    files=[]
    for raw in names:
        if not raw:
            continue
        name=raw.decode("utf-8")
        if not name.startswith(("component/","libraries/","plugins/","tests/")):
            continue
        path=ROOT/name
        if not path.is_file():
            continue
        data=path.read_bytes()
        count=sum(len(line)>LIMIT for line in data.splitlines())
        if count:
            files.append((count,name))
    files.sort(reverse=True)
    accepted_files=accepted_lines=rejected=removed=0
    for _,name in files[:args.max_files]:
        path=ROOT/name
        original=path.read_bytes()
        protected=contracts.get(name,set())
        initial=phpcs_counts(args.phpcs,path)
        if not initial:
            continue
        signature=lex(path)["hash"]
        good=0
        attempts=0
        # Progressively repair one long source line per pass; do not alter
        # any other line when considering a candidate.
        while attempts<36:
            data=path.read_bytes()
            offsets=spaces(path)
            before=phpcs_counts(args.phpcs,path)
            changed=False
            offset=0
            for line in data.splitlines(keepends=True):
                content=line.rstrip(b"\r\n")
                begin,end=offset,offset+len(content)
                offset+=len(line)
                if len(content)<=LIMIT:
                    continue
                indent=len(content)-len(content.lstrip(b" \t"))
                proposals=line_candidates(data,begin,end,offsets,indent)
                for trial in proposals:
                    attempts+=1
                    if any(p in data and p not in trial for p in protected):
                        continue
                    path.write_bytes(trial)
                    counts=phpcs_counts(args.phpcs,path)
                    if (
                        sum(counts.values()) < sum(before.values())
                        and all(counts[k]<=before[k] for k in counts)
                        and lint(path)
                        and lex(path)["hash"]==signature
                    ):
                        good+=1
                        accepted_lines+=1
                        removed+=sum(before.values())-sum(counts.values())
                        changed=True
                        break
                    path.write_bytes(data)
                    if attempts>=36:
                        break
                if changed or attempts>=36:
                    break
            if not changed:
                path.write_bytes(data)
                break
        if good:
            accepted_files+=1
            print(f"SECDEV-02 incremental accepted {name} fixed={good}",flush=True)
        elif attempts:
            rejected+=1
    print(
        f"SECDEV-02 incremental accepted_files={accepted_files} "
        f"lines={accepted_lines} rejected_files={rejected} net_removed={removed}",
        flush=True,
    )
    return 0


if __name__=="__main__":
    raise SystemExit(main())
