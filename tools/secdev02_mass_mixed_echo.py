#!/usr/bin/env python3
"""Mass PSR-12 refactoring of expression-heavy mixed PHP/HTML templates.

All inserted newlines are inside actual PHP lexer spans, never HTML. The entire
significant PHP token stream and inline HTML remain byte-for-byte unchanged.
Proposals are accepted only after PHP lint, unchanged source contracts and
pinned PHPCS whole-file per-rule nonregression, optionally PHPCBF normalization.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_adaptive_operator_batch import operator_points
from secdev02_calls_reflow_bulk import required_source_fragments

SPANS = r"""
$src=file_get_contents($argv[1]);
$offset=0;$regions=[];$opening=null;
foreach(token_get_all($src) as $token) {
  $id=is_array($token)?$token[0]:0;
  $text=is_array($token)?$token[1]:$token;
  if ($id===T_OPEN_TAG_WITH_ECHO || $id===T_OPEN_TAG) {
    $opening=$offset+strlen($text);
  } elseif ($id===T_CLOSE_TAG && $opening!==null) {
    $regions[]=[$opening,$offset];$opening=null;
  }
  $offset+=strlen($text);
}
echo json_encode($regions, JSON_THROW_ON_ERROR);
"""
MAX_WIDTH=120


def regions(path:Path)->list:
    r=subprocess.run(["php","-r",SPANS,str(path)],capture_output=True,
                     text=True,check=True,timeout=35)
    return json.loads(r.stdout)


def reflow(source:bytes, spans:list, points:list, width:int, extra:int, after:bool)->bytes:
    positions=[]
    for line_start,line in lines(source):
        end=line_start+len(line)
        if len(line)<=MAX_WIDTH or b"<?" not in line and b"?>" not in line:
            continue
        indent=len(line)-len(line.lstrip(b" \t"))
        if indent>30:
            continue
        pad=b" " * min(indent+extra,28)
        stops=[]
        for open_pos,close_pos in spans:
            if close_pos<=line_start or open_pos>=end:
                continue
            left=max(open_pos,line_start+18)
            right=min(close_pos,end-5)
            if left>=right:
                continue
            for begin,finish,op in points:
                p=finish if after else begin
                if left<=p<=right:
                    stops.append(p)
            # Also include the PHP tag opening as a safe boundary when
            # it has a long expression that can be wrapped independently.
            if open_pos>=left and open_pos<right-25:
                stops.append(open_pos)
        stops=sorted(set(stops))
        if not stops:
            continue
        cursor=line_start
        chosen=[]
        for _ in range(18):
            if end-cursor+(len(pad) if chosen else 0)<=MAX_WIDTH:
                break
            upper=cursor+width-(len(pad) if chosen else 0)
            fits=[p for p in stops if cursor+17<p<=upper and p not in chosen]
            if not fits:
                break
            split=fits[-1]
            chosen.append(split)
            cursor=split
        positions.extend((p,pad) for p in chosen)
    out=bytearray(source)
    for p,indent in sorted(set(positions),reverse=True):
        out[p:p]=b"\n"+indent
    return bytes(out)


def lines(src:bytes):
    off=0
    for row in src.splitlines(keepends=True):
        content=row.rstrip(b"\n\r")
        yield off,content
        off+=len(row)


def main()->int:
    ap=argparse.ArgumentParser()
    ap.add_argument("--phpcs",type=Path)
    ap.add_argument("--phpcbf",type=Path)
    ap.add_argument("--self-test",action="store_true")
    args=ap.parse_args()
    if args.self_test:
        sample=b'<p><?= Text::_($value ? "a" : "b") . " long" . " text" ?></p>'*4+b"\n"
        opening=sample.index(b"<?=")+3
        closing=sample.index(b"?>")
        tokenpoints=[]
        for i,b in enumerate(sample):
            if b==46:
                tokenpoints.append([i,i+1,"."])
        trial=reflow(sample,[[opening,closing]],tokenpoints,80,4,False)
        assert trial!=sample and b"\n" in trial
        print("SECDEV-02 mixed PHP echo expression wrapping fixtures OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        ap.error("Pinned PHPCS required")
    protected=required_source_fragments()
    tracked=subprocess.check_output(["git","ls-files","-z","--","*.php"],cwd=ROOT).split(b"\0")
    accepted=rejected=removed=0
    for raw in tracked:
        if not raw:
            continue
        name=raw.decode()
        if "/tmpl/" not in name:
            continue
        path=ROOT/name
        if not path.exists():
            continue
        original=path.read_bytes()
        if not any(len(line)>MAX_WIDTH for line in original.splitlines()):
            continue
        php_regions=regions(path)
        if not php_regions:
            continue
        operators=operator_points(path)
        baseline=phpcs_counts(args.phpcs,path)
        signature=lex(path)["hash"]
        requirements=protected.get(name,set())
        best=original
        best_score=sum(baseline.values())
        for width in (114,104,94,84,74):
            for extra,after in ((4,False),(8,False),(4,True),(0,False)):
                candidate=reflow(original,php_regions,operators,width,extra,after)
                if candidate==original or any(s in original and s not in candidate for s in requirements):
                    continue
                path.write_bytes(candidate)
                if not lint(path) or lex(path)["hash"]!=signature:
                    continue
                for fix in (False,True):
                    if fix and args.phpcbf and args.phpcbf.is_file():
                        subprocess.run(
                            [str(args.phpcbf),"--standard=PSR12","--no-cache",str(path)],
                            capture_output=True,timeout=60,check=False,
                        )
                    if not lint(path) or lex(path)["hash"]!=signature:
                        break
                    result=path.read_bytes()
                    if any(s in original and s not in result for s in requirements):
                        break
                    counts=phpcs_counts(args.phpcs,path)
                    if any(counts[k]>baseline[k] for k in counts):
                        if fix:
                            break
                    elif sum(counts.values())<best_score:
                        best_score=sum(counts.values())
                        best=result
                    if not fix:
                        path.write_bytes(candidate)
        path.write_bytes(best)
        if best!=original:
            accepted+=1
            gain=sum(baseline.values())-best_score
            removed+=gain
            print(f"MIXED ECHO accepted {name} eliminated={gain}",flush=True)
        else:
            rejected+=1
    print(f"MIXED ECHO RESULT accepted_files={accepted} rejected={rejected} removed={removed}",flush=True)
    return 0


if __name__=="__main__":
    raise SystemExit(main())
