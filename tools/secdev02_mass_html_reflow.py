#!/usr/bin/env python3
"""Reflow long HTML tags in mixed PHP templates with exact attribute values.

Only whitespace *outside quoted HTML attributes* is changed, and optionally
only at boundaries between block-level HTML elements. PHP source tokens and
attribute values remain unchanged. Every file is gated by full PHP lint,
existing source text contracts, and PHPCS per-rule nonregression.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import re
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

LIMIT=120
BLOCK_TAGS=rb"(?:div|section|header|footer|main|aside|article|nav|form|fieldset|ul|ol|li|dl|table|tbody|thead|tr|td|th|h[1-6]|p|details|summary)"
BLOCK=re.compile(rb">(?=</?"+BLOCK_TAGS+rb"(?=[\s>/]))",re.I)
RAW=re.compile(rb"<(?:script|style|pre|textarea)\b",re.I)
TOKENIZER=r"""
$src=file_get_contents($argv[1]);$offset=0;$inline=[];$significant=[];
foreach(token_get_all($src) as $tok) {
  $id=is_array($tok)?$tok[0]:0;
  $v=is_array($tok)?$tok[1]:$tok;
  if($id===T_INLINE_HTML)$inline[]=[$offset,$offset+strlen($v)];
  if($id!==T_INLINE_HTML && $id!==T_WHITESPACE)
      $significant[]=[$id,$v];
  $offset+=strlen($v);
}
echo json_encode(['inline'=>$inline,
  'significant_hash'=>hash('sha256',serialize($significant))],
  JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
"""


def manifest(path:Path)->dict:
    result=subprocess.run(["php","-r",TOKENIZER,str(path)],capture_output=True,
                          text=True,timeout=35,check=True)
    return json.loads(result.stdout)


def breakpoints(source:bytes,parts:list,mode:str)->list[tuple[int,int]]:
    locations=[]
    for begin,end in parts:
        chunk=source[begin:end]
        if RAW.search(chunk):
            continue
        if mode in ("blocks","both"):
            for found in BLOCK.finditer(chunk):
                p=begin+found.end()
                if p<end:
                    locations.append((p,0))
        if mode in ("attrs","both"):
            quoted=False
            tag=False
            # Tokenized HTML: only break between complete quoted attributes
            # while inside an opening tag, never in text or a value.
            for i,char in enumerate(chunk):
                if char==60 and not quoted:
                    tag=True
                elif char==62 and not quoted:
                    tag=False
                elif char in (34,39) and tag:
                    # Account for alternate HTML quote styles.
                    previous=chunk[i-1:i]
                    if previous!=b"\\":
                        quoted=not quoted
                if not tag or quoted or char not in (32,9):
                    continue
                before=chunk[max(0,i-1):i]
                rest=chunk[i+1:i+42]
                if before not in (b'"',b"'"):
                    continue
                if re.match(rb"[A-Za-z_][A-Za-z0-9_:.-]*\s*=",rest):
                    locations.append((begin+i,1))
    return sorted(set(locations))


def propose(source:bytes,items:list[tuple[int,int]],offset:int)->bytes:
    edits=[]
    for line_start,line in line_bounds(source):
        line_end=line_start+len(line)
        if len(line)<=LIMIT:
            continue
        if line.lstrip().startswith((b"//",b"/*",b"*")):
            continue
        indentation=len(line)-len(line.lstrip(b" \t"))
        if indentation>30:
            continue
        padding=b" " * min(24,indentation+offset)
        points=[(p,skip) for p,skip in items if line_start+20<p<line_end-10]
        if not points:
            continue
        cursor=line_start
        used=[]
        for _ in range(15):
            if line_end-cursor+(len(padding) if used else 0)<=LIMIT:
                break
            available=[(p,skip) for p,skip in points
                       if cursor+20<p<cursor+105 and p not in used]
            if not available:
                available=[(p,skip) for p,skip in points
                           if cursor+20<p<=cursor+LIMIT-len(padding) and p not in used]
            if not available:
                break
            p,skip=max(available,key=lambda a:a[0])
            used.append(p)
            edits.append((p,skip,b"\n"+padding))
            cursor=p+skip
    data=bytearray(source)
    for p,skip,value in sorted(edits,reverse=True):
        data[p:p+skip]=value
    return bytes(data)


def line_bounds(data:bytes):
    off=0
    for line in data.splitlines(keepends=True):
        without=line.rstrip(b"\r\n")
        yield off,without
        off+=len(line)


def main()->int:
    ap=argparse.ArgumentParser()
    ap.add_argument("--phpcs",type=Path)
    ap.add_argument("--self-test",action="store_true")
    args=ap.parse_args()
    if args.self_test:
        sample=(b'<div class="one" id="two"><section><h2>Title</h2></section></div>'*4+b"\n")
        points=breakpoints(sample,[(0,len(sample))],"blocks")
        updated=propose(sample,points,2)
        assert updated!=sample and b"\n" in updated
        print("SECDEV-02 HTML block boundary reflow fixtures OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        ap.error("Pinned PHPCS required")
    names=subprocess.check_output(["git","ls-files","-z","--","*.php"],cwd=ROOT).split(b"\0")
    contracts=required_source_fragments()
    accepted=rejected=removed=0
    for raw in names:
        if not raw:
            continue
        name=raw.decode("utf-8")
        if "/tmpl/" not in name and "/View/" not in name:
            continue
        path=ROOT/name
        if not path.is_file():
            continue
        original=path.read_bytes()
        if not any(len(row)>LIMIT for row in original.splitlines()):
            continue
        old=manifest(path)
        if not old["inline"]:
            continue
        initial=phpcs_counts(args.phpcs,path)
        protected=contracts.get(name,set())
        best=original
        score=sum(initial.values())
        for mode in ("both","attrs","blocks"):
            points=breakpoints(original,old["inline"],mode)
            for extra in (2,0,4,8):
                candidate=propose(original,points,extra)
                if candidate==original or any(x in original and x not in candidate for x in protected):
                    continue
                path.write_bytes(candidate)
                if not lint(path) or manifest(path)["significant_hash"]!=old["significant_hash"]:
                    continue
                now=phpcs_counts(args.phpcs,path)
                if any(now[k]>initial[k] for k in now):
                    continue
                if sum(now.values())<score:
                    score=sum(now.values())
                    best=candidate
        path.write_bytes(best)
        if best!=original:
            accepted+=1
            gain=sum(initial.values())-score
            removed+=gain
            print(f"HTML MASS accepted {name} fixed={gain}",flush=True)
        else:
            rejected+=1
    print(f"HTML MASS RESULT accepted_files={accepted} rejected_files={rejected} fixed={removed}",flush=True)
    return 0


if __name__=="__main__":
    raise SystemExit(main())
