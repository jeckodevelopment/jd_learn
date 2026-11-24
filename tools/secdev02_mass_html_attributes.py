#!/usr/bin/env python3
"""Mass safe reflow of HTML tag attributes in mixed Joomla PHP views.

Break only HTML whitespace between *distinct* attribute names, never inside a
quoted attribute value, PHP code, script/style or textual HTML content.
Candidate positions are obtained from actual PHP T_INLINE_HTML byte spans.
The only edit substitutes an existing ASCII space with a line break and indent;
PHP code token stream and HTML attribute spelling remain unmodified.
"""
from __future__ import annotations

import argparse
import hashlib
import json
from pathlib import Path
import re
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

LIMIT=120
TOKENIZER=r"""
$source=file_get_contents($argv[1]);
$off=0; $html=[]; $hash=hash_init('sha256');
foreach(token_get_all($source) as $token) {
  $id=is_array($token)?$token[0]:0;
  $text=is_array($token)?$token[1]:$token;
  if($id===T_INLINE_HTML)$html[]=[$off,$off+strlen($text)];
  if($id!==T_INLINE_HTML && $id!==T_WHITESPACE) {
    hash_update($hash,$id.':'.strlen($text).':'.$text."\0");
  }
  $off+=strlen($text);
}
echo json_encode(['html'=>$html,'php_hash'=>hash_final($hash)],
  JSON_THROW_ON_ERROR);
"""
ATTR=re.compile(rb"[a-zA-Z_][a-zA-Z0-9_:\-.]*[ \t]*=")
RAW_TAG=re.compile(rb"<\s*/?\s*(?:script|style|textarea|pre)\b",re.I)


def scan(path: Path)->dict:
    p=subprocess.run(["php","-r",TOKENIZER,str(path)],capture_output=True,
                     text=True,check=True,timeout=35)
    return json.loads(p.stdout)


def points(data:bytes,spans:list[list[int]])->list[int]:
    locations=[]
    in_tag=False
    quoted=None
    in_comment=False
    in_raw=False
    for begin,end in spans:
        if RAW_TAG.search(data[begin:end]):
            # Skip entire mixed/raw PHP HTML chunk if scripts, CSS, or
            # whitespace-sensitive raw text may be present.
            in_raw=True
        if in_raw:
            if re.search(rb"</(?:script|style|pre|textarea)\s*>",data[begin:end],re.I):
                in_raw=False
                in_tag=False
                quoted=None
            continue
        i=begin
        while i<end:
            if in_comment:
                if data[i:i+3]==b"-->":
                    in_comment=False
                    i+=3
                    continue
                i+=1
                continue
            if not in_tag:
                if data[i:i+4]==b"<!--":
                    in_comment=True
                    i+=4
                    continue
                if data[i:i+1]==b"<" and re.match(rb"</?[a-zA-Z][\w:-]*",data[i:min(end,i+50)]):
                    in_tag=True
                    quoted=None
                i+=1
                continue
            char=data[i]
            if quoted is not None:
                if char==quoted:
                    quoted=None
                i+=1
                continue
            if char in (34,39):
                quoted=char
            elif char==62:
                in_tag=False
            elif char in (32,9):
                if i>begin and data[i-1] not in (32,9,10,13):
                    matched=ATTR.match(data[i+1:min(end,i+100)])
                    if matched and data[i-1] not in (60,47,61):
                        locations.append(i)
            i+=1
    return sorted(set(locations))


def long_lines(source:bytes)->list[tuple[int,int,int]]:
    result=[]
    cursor=0
    for line in source.splitlines(keepends=True):
        size=len(line.rstrip(b"\r\n"))
        if size>LIMIT:
            result.append((cursor,cursor+size,size))
        cursor+=len(line)
    return result


def propose(original:bytes,spots:list[int],width:int,extra:int,per_line:bool)->bytes:
    edits=[]
    for start,end,length in long_lines(original):
        indent=len(original[start:end])-len(original[start:end].lstrip(b" \t"))
        if indent>32:
            continue
        padding=b" " * min(28,indent+extra)
        eligible=[p for p in spots if start+17<=p<=end-9 and original[p:p+1]==b" "]
        if not eligible:
            continue
        cursor=start
        chosen=[]
        for _ in range(20):
            if end-cursor+(len(padding) if chosen else 0)<=LIMIT:
                break
            fits=[p for p in eligible if cursor+20<p<=cursor+width-len(padding) and p not in chosen]
            if not fits:
                break
            p=max(fits)
            chosen.append(p)
            cursor=p
            if per_line:
                break
        for p in chosen:
            edits.append((p,padding))
    output=bytearray(original)
    for p,padding in sorted(set(edits),reverse=True):
        output[p:p+1]=b"\n"+padding
    return bytes(output)


def main()->int:
    ap=argparse.ArgumentParser()
    ap.add_argument("--phpcs",type=Path)
    ap.add_argument("--max-files",type=int,default=300)
    ap.add_argument("--self-test",action="store_true")
    args=ap.parse_args()
    if args.self_test:
        data=b'<div class="one" id="two" data-name="three" data-value="four" role="region">'*3+b"\n"
        candidates=points(data,[[0,len(data)]])
        assert len(candidates)>=4
        trial=propose(data,candidates,95,4,False)
        assert trial!=data
        assert trial.replace(b"\n    ",b" ")==data
        print("SECDEV-02 HTML attribute syntax-preservation fixtures OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        ap.error("Pinned --phpcs required")
    protected=required_source_fragments()
    paths=subprocess.check_output(["git","ls-files","-z","--","*.php"],cwd=ROOT).split(b"\0")
    ranked=[]
    for item in paths:
        if not item:
            continue
        name=item.decode("utf-8")
        if "/tmpl/" not in name:
            continue
        path=ROOT/name
        if not path.exists():
            continue
        number=sum(len(line)>LIMIT for line in path.read_bytes().splitlines())
        if number:
            ranked.append((number,name))
    ranked.sort(reverse=True)
    accepted=removed=rejected=0
    for _,name in ranked[:args.max_files]:
        path=ROOT/name
        initial=path.read_bytes()
        manifest=scan(path)
        spots=points(initial,manifest["html"])
        if not spots:
            continue
        before=phpcs_counts(args.phpcs,path)
        required=protected.get(name,set())
        best=initial
        best_count=sum(before.values())
        for per_line in (False,True):
            for width in (114,108,102,96,88,78):
                for extra in (0,4,2,8,12):
                    trial=propose(initial,spots,width,extra,per_line)
                    if trial==initial or any(x in initial and x not in trial for x in required):
                        continue
                    path.write_bytes(trial)
                    if not lint(path):
                        continue
                    updated=scan(path)
                    if updated["php_hash"]!=manifest["php_hash"]:
                        continue
                    result=phpcs_counts(args.phpcs,path)
                    if any(result[k]>before[k] for k in result):
                        continue
                    current=sum(result.values())
                    if current<best_count:
                        best_count=current
                        best=trial
        path.write_bytes(best)
        if best!=initial:
            gain=sum(before.values())-best_count
            accepted+=1
            removed+=gain
            print(f"HTML ATTR accepted {name}: -{gain}",flush=True)
        else:
            rejected+=1
    print(f"HTML ATTR MASS RESULT accepted_files={accepted} rejected={rejected} fixed={removed}",flush=True)
    return 0


if __name__=="__main__":
    raise SystemExit(main())
