#!/usr/bin/env python3
"""PSR-12 mass rewrite of long PHP function/method/closure signatures.

Only pure PHP whitespace is inserted around lexer-delimited declarations.
All significant PHP tokens are identical, and candidates require zero new
PHPCS violations per rule and improvements in whole-file PSR-12 inventory.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

TOKENIZER=r"""
$s=file_get_contents($argv[1]);$tokens=token_get_all($s);
$offsets=[];$off=0;
foreach($tokens as $token) {
  $offsets[]=$off;
  $off+=strlen(is_array($token)?$token[1]:$token);
}
$pairs=[];$n=count($tokens);
for($i=0;$i<$n;$i++) {
  if(!is_array($tokens[$i]) || $tokens[$i][0]!==T_FUNCTION)continue;
  $start=$offsets[$i];$open=null;
  for($j=$i+1;$j<$n;$j++) {
    $t=is_array($tokens[$j])?$tokens[$j][1]:$tokens[$j];
    if($t==='(') {$open=$j;break;}
    if($t===';' || $t==='{')break;
  }
  if($open===null)continue;
  $depth=1;$commas=[];$end=null;
  for($k=$open+1;$k<$n;$k++) {
    $t=is_array($tokens[$k])?$tokens[$k][1]:$tokens[$k];
    if($t==='(')$depth++;
    if($t===')')$depth--;
    if($depth===1 && $t===',')$commas[]=$offsets[$k]+1;
    if($depth===0){$end=$offsets[$k];break;}
  }
  if($end!==null)$pairs[]=[$start,$offsets[$open],$end,$commas];
}
echo json_encode($pairs, JSON_THROW_ON_ERROR);
"""


def scan(path: Path) -> list:
    out=subprocess.run(["php","-r",TOKENIZER,str(path)],capture_output=True,
                       text=True,timeout=35,check=True)
    return json.loads(out.stdout)


def reflow(original: bytes, entries: list, offset: int) -> bytes:
    edits=[]
    selected=[]
    for begin,opener,closer,commas in entries:
        line_start=original.rfind(b"\n",0,begin)+1
        line_end=original.find(b"\n",begin)
        if line_end<0:
            line_end=len(original)
        if opener>=closer or closer>=line_end:
            continue
        if line_end-line_start<=120:
            continue
        if b"<?" in original[line_start:line_end] or b"?>" in original[line_start:line_end]:
            continue
        prefix=original[line_start:begin]
        indent=len(prefix)-len(prefix.lstrip(b" \t"))
        if indent>32:
            continue
        if any(begin<e and closer>b for b,e in selected):
            continue
        pad=b" " * min(28,indent+offset)
        base=b" " * indent
        for place in [opener+1]+list(commas):
            stop=place
            while stop<closer and original[stop:stop+1] in (b" ",b"\t"):
                stop+=1
            edits.append((place,stop,b"\n"+pad))
        start=closer
        while start>opener and original[start-1:start] in (b" ",b"\t"):
            start-=1
        edits.append((start,closer,b"\n"+base))
        selected.append((begin,closer))
    out=bytearray(original)
    for start,stop,value in sorted(set(edits),reverse=True):
        out[start:stop]=value
    return bytes(out)


def main() -> int:
    p=argparse.ArgumentParser()
    p.add_argument("--phpcs",type=Path)
    p.add_argument("--self-test",action="store_true")
    args=p.parse_args()
    if args.self_test:
        data=b"<?php\n    public function x(" + b"$first, $second, "*9 + b"$final): void {}\n"
        start=data.index(b"function")
        opening=data.index(b"(",start)
        closing=data.index(b")",opening)
        commas=[i+1 for i,v in enumerate(data) if v==44]
        updated=reflow(data,[[start,opening,closing,commas]],4)
        assert updated!=data and b"(\n" in updated
        print("SECDEV-02 function signature reflow fixtures OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        p.error("Pinned PHPCS is required")
    tracked=subprocess.check_output(["git","ls-files","-z","--","*.php"],cwd=ROOT).split(b"\0")
    contracts=required_source_fragments()
    accepted=removed=rejected=0
    for raw in tracked:
        if not raw:
            continue
        name=raw.decode("utf-8")
        path=ROOT/name
        if not path.is_file():
            continue
        initial=path.read_bytes()
        if not any(len(line)>120 for line in initial.splitlines()):
            continue
        items=scan(path)
        if not items:
            continue
        before=phpcs_counts(args.phpcs,path)
        if not before["Generic.Files.LineLength.TooLong"]:
            continue
        old_hash=lex(path)["hash"]
        best=initial
        best_count=before
        protected=contracts.get(name,set())
        for extra in (4,8,2,0):
            candidate=reflow(initial,items,extra)
            if candidate==initial:
                continue
            if any(x in initial and x not in candidate for x in protected):
                continue
            path.write_bytes(candidate)
            if not lint(path) or lex(path)["hash"]!=old_hash:
                continue
            after=phpcs_counts(args.phpcs,path)
            if any(after[k]>before[k] for k in after):
                continue
            if sum(after.values())<sum(best_count.values()):
                best=candidate
                best_count=after
        path.write_bytes(best)
        if best!=initial:
            accepted+=1
            score=sum(before.values())-sum(best_count.values())
            removed+=score
            print(f"SIGNATURE accepted {name} removed={score}",flush=True)
        else:
            rejected+=1
    print(f"SIGNATURE RESULT accepted_files={accepted} rejected_files={rejected} removed={removed}",flush=True)
    return 0


if __name__=="__main__":
    raise SystemExit(main())
