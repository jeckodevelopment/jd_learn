#!/usr/bin/env python3
"""Mass PSR-12 source reflow, gated by PHP lexer and PHPCS per-rule inventory.

Unlike per-line greediest formatting, this operates on complete long
expressions across PHP files. It enumerates semantic token boundaries, rewrites
all eligible long statements in each file, and only commits candidates that
pass significant-token equality, lint, source contracts, and full PHPCS
per-rule non-regression. The tool does NOT suppress or exclude warnings.
"""
from __future__ import annotations

import argparse
from bisect import bisect_right
from pathlib import Path
import json
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

LIMIT = 120
TOKENS = r"""
$s=file_get_contents($argv[1]);$offset=0;$items=[];
foreach(token_get_all($s) as $tok) {
  $id=is_array($tok)?$tok[0]:0;
  $v=is_array($tok)?$tok[1]:$tok;
  $length=strlen($v);
  if ($id!==T_INLINE_HTML && $id!==T_WHITESPACE
      && $id!==T_COMMENT && $id!==T_DOC_COMMENT) {
    $items[]=[$offset,$offset+$length,$v,$id];
  }
  $offset+=$length;
}
echo json_encode($items, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
"""


def tokens(path: Path) -> list:
    p=subprocess.run(["php","-r",TOKENS,str(path)],capture_output=True,
                     text=True,timeout=40,check=True)
    return json.loads(p.stdout)


def candidates(original: bytes, items: list, strategy: int) -> bytes:
    starts=[0]
    for i,ch in enumerate(original):
        if ch==10:
            starts.append(i+1)
    ends=[original.find(b"\n",a) for a in starts]
    ends=[len(original) if x<0 else x for x in ends]
    positions:dict[int,list[tuple[int,int,str]]]={}
    for begin,end,value,idnum in items:
        if begin>=len(original) or end>len(original):
            continue
        row=bisect_right(starts,begin)-1
        if row<0 or ends[row]-starts[row]<=LIMIT:
            continue
        # A long PHP literal must not be edited or split; only delimiters
        # that the PHP lexer recognizes as punctuation/operator are eligible.
        if value in (",",";","=>","&&","||","??","->","?->",".","?",":","(",
                     ")","[","]","+","-","=","===","!==","==","!="):
            if value in (",",";","(","["):
                split=end
            elif value in (")","]"):
                split=begin
            else:
                split=begin
            if starts[row]+24 <= split <= ends[row]-8:
                positions.setdefault(row,[]).append((split,end,value))
    changes=[]
    for row,linebegin in enumerate(starts):
        lineend=ends[row]
        if lineend-linebegin<=LIMIT:
            continue
        line=original[linebegin:lineend]
        trimmed=line.lstrip(b" \t")
        baseindent=len(line)-len(trimmed)
        if baseindent>32 or trimmed.startswith((b"//",b"#",b"/*",b"*")):
            continue
        if b"<?" in line or b"?>" in line:
            continue
        points=positions.get(row,[])
        if not points:
            continue
        indent=b" " * min(28,baseindent+(4 if strategy%2==0 else 8))
        cursor=linebegin
        selected=[]
        for i in range(25):
            width=lineend-cursor+(len(indent) if selected else 0)
            if width<=LIMIT:
                break
            target=(94,104,84,112,76,98)[strategy%6]
            available=[
                (point,op)
                for point,_end,op in points
                if cursor+20 < point <= cursor+target
                and point not in selected
            ]
            if not available:
                # If no boundary fits the preferred width, allow a longer
                # span while still staying inside PSR12's 120-char limit.
                available=[
                    (point,op)
                    for point,_end,op in points
                    if cursor+20 < point <= cursor+LIMIT-len(indent)
                    and point not in selected
                ]
            if not available:
                break
            if strategy in (0,1,2,3):
                # Favor comma, next boolean/operator, then the rightmost.
                def score(entry):
                    point,op=entry
                    weight={",":6,"&&":5,"||":5,"??":5,"=>":4,
                            "->":3,"?->":3,".":2,"(":1}.get(op,0)
                    return (weight,point)
                point,_=max(available,key=score)
            else:
                point,_=max(available,key=lambda entry:entry[0])
            selected.append(point)
            cursor=point
        if not selected:
            continue
        # Don't allow still-long leading segment to encourage a PHPCS
        # false improvement due to extra generated style errors.
        for point in selected:
            changes.append((point,b"\n"+indent))
    out=bytearray(original)
    for position,text in sorted(set(changes),reverse=True):
        out[position:position]=text
    return bytes(out)


def main() -> int:
    p=argparse.ArgumentParser()
    p.add_argument("--phpcs",type=Path)
    p.add_argument("--max-files",type=int,default=380)
    p.add_argument("--self-test",action="store_true")
    args=p.parse_args()
    if args.self_test:
        data=b"<?php\n$x = myFunction("+b"item, "*30+b"last);\n"
        # Synthetic lexer candidates for comma-separated arguments.
        positions=[(i,i+1,",",0) for i,ch in enumerate(data) if ch==44]
        assert candidates(data,positions,0)!=data
        assert candidates(b"<?php\n$x=1;\n",[],0)==b"<?php\n$x=1;\n"
        print("SECDEV-02 mass statement reflow fixtures OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        p.error("--phpcs mandatory")
    protection=required_source_fragments()
    names=subprocess.check_output(
        ["git","ls-files","-z","--","*.php"],cwd=ROOT
    ).split(b"\0")
    ranked=[]
    for raw in names:
        if not raw:
            continue
        name=raw.decode("utf-8")
        path=ROOT/name
        if not path.is_file():
            continue
        rawdata=path.read_bytes()
        length=sum(len(line)>LIMIT for line in rawdata.splitlines())
        if length:
            ranked.append((length,name))
    ranked.sort(reverse=True)
    accepted=rejected=removed=0
    for longcount,name in ranked[:args.max_files]:
        path=ROOT/name
        old=path.read_bytes()
        baseline=phpcs_counts(args.phpcs,path)
        if baseline["Generic.Files.LineLength.TooLong"]<1:
            continue
        origin=lex(path)["hash"]
        fragments=protection.get(name,set())
        t=tokens(path)
        best=old
        best_count=baseline
        for strategy in range(6):
            new=candidates(old,t,strategy)
            if new==old:
                continue
            if any(part in old and part not in new for part in fragments):
                continue
            path.write_bytes(new)
            if not lint(path) or lex(path)["hash"]!=origin:
                continue
            metrics=phpcs_counts(args.phpcs,path)
            if (
                any(metrics[key]>baseline[key] for key in metrics)
                or sum(metrics.values())>=sum(best_count.values())
            ):
                continue
            best=new
            best_count=metrics
        path.write_bytes(best)
        if best!=old:
            accepted+=1
            n=sum(baseline.values())-sum(best_count.values())
            removed+=n
            print(f"MASS RE-FLOW {name}: -{n} ({longcount} long lines initially)",flush=True)
        else:
            rejected+=1
    print(f"MASS RESULT accepted_files={accepted} rejected_files={rejected} "
          f"removed={removed}",flush=True)
    return 0


if __name__=="__main__":
    raise SystemExit(main())
