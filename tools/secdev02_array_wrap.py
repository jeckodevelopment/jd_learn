#!/usr/bin/env python3
"""SECDEV-02: bulk formatter for long PHP array and argument lists.

Safely inserts whitespace only after real PHP comma tokens; ignores HTML,
strings and comments. Every accepted file has byte-identical non-whitespace
PHP tokens, passes lint, and reduces PHPCS PSR-12 findings without adding
any finding in any rule for that file.
"""
from __future__ import annotations
import argparse
from pathlib import Path
import subprocess
from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts

LIMIT=120

def propose(raw: bytes, token_points: list, target: int, extra: int) -> bytes:
    byline={}
    for pos, token in token_points:
        if token != ",":
            continue
        begin=raw.rfind(b"\n",0,pos)+1
        byline.setdefault(begin, []).append(pos)
    edits=[]
    cursor=0
    for line in raw.splitlines(keepends=True):
        start=cursor
        end=cursor+len(line.rstrip(b"\r\n"))
        cursor+=len(line)
        if end-start <= LIMIT:
            continue
        trimmed=line.lstrip(b" \t")
        if trimmed.startswith((b"//",b"#",b"/*",b"*")):
            continue
        indent=len(line)-len(trimmed)
        if indent>30:
            continue
        points=sorted(set(p for p in byline.get(start,[]) if p>start+25 and p<end-8))
        if not points:
            continue
        current=start
        prefix=b" " * min(indent+extra,32)
        for _ in range(35):
            remaining=end-current
            if remaining + (len(prefix) if current>start else 0) <= LIMIT:
                break
            width=target-(len(prefix) if current>start else 0)
            choices=[p for p in points if current+18 < p <= current+width]
            if not choices:
                break
            split=choices[-1]
            # The punctuation token is at pos-1; inserting after it cannot
            # change literal strings, heredoc, comment text or inline HTML.
            edits.append((split,b"\n"+prefix))
            current=split
    if not edits:
        return raw
    out=bytearray(raw)
    for pos,addition in sorted(edits,reverse=True):
        out[pos:pos]=addition
    return bytes(out)

def run():
    p=argparse.ArgumentParser()
    p.add_argument("--apply",action="store_true")
    p.add_argument("--self-test",action="store_true")
    p.add_argument("--phpcs",type=Path)
    args=p.parse_args()
    if args.self_test:
        line=b"    $data = ['one' => 1,'two' => 2,'three' => 3,'four' => 4,'five' => 5,'six' => 6,'seven' => 7,'eight' => 8,'nine' => 9,'ten' => 10,'eleven' => 11];\n"
        points=[[i+1,","] for i,c in enumerate(line) if c==44]
        assert len(line)>100
        assert propose(line,points,90,4) != line
        assert propose(b"echo 1;\n",[],100,4)==b"echo 1;\n"
        print("SECDEV-02 array/argument line wrapper fixtures OK")
        return
    if not args.apply or not args.phpcs or not args.phpcs.is_file():
        p.error("--apply and valid --phpcs required")
    paths=subprocess.check_output(["git","ls-files","-z","--","*.php"],cwd=ROOT).split(b"\0")
    accepted=rejected=removed=0
    for item in paths:
        if not item:
            continue
        path=ROOT/item.decode()
        if not path.is_file():
            continue
        original=path.read_bytes()
        if not any(len(line)>LIMIT for line in original.splitlines()):
            continue
        beforelex=lex(path)
        variants=[]
        for target,extra in [(105,4),(109,8),(95,4),(100,8),(113,4),(103,0)]:
            value=propose(original,beforelex["points"],target,extra)
            if value!=original and value not in variants:
                variants.append(value)
        if not variants:
            continue
        previous=phpcs_counts(args.phpcs,path)
        best=original
        bestscore=sum(previous.values())
        for value in variants:
            path.write_bytes(value)
            if not lint(path) or lex(path)["hash"]!=beforelex["hash"]:
                continue
            now=phpcs_counts(args.phpcs,path)
            if any(now[k]>v for k,v in previous.items()) or any(k not in previous for k in now):
                continue
            if sum(now.values())<bestscore:
                best=value
                bestscore=sum(now.values())
        path.write_bytes(best)
        if best==original:
            rejected+=1
        else:
            accepted+=1
            removed+=sum(previous.values())-bestscore
    print(f"SECDEV-02 array/args batch: accepted_files={accepted} rejected_files={rejected} net_removed={removed}")

if __name__=="__main__":
    run()
