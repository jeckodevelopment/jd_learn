#!/usr/bin/env python3
"""Split PSR1 test fixtures into side-effect-only entry points and class helpers.

Extracts top-level declarations at their original runtime position, using
require_once for each declaration and carrying import aliases into a clean
single-declaration helper. Never changes production source. Every complete
candidate must pass PHP lint, source contracts and zero new PHPCS file/rule
findings, with ALL newly created helper files completely PSR12-clean.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import re
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

TOKENIZER = r"""
$src=file_get_contents($argv[1]);
$tokens=token_get_all($src, TOKEN_PARSE);$positions=[];$cursor=0;
foreach($tokens as $token) {
    $positions[]=$cursor;
    $cursor+=strlen(is_array($token)?$token[1]:$token);
}
$depth=0;$n=count($tokens);$items=[];$imports=[];$unsupported=false;
for($i=0;$i<$n;$i++) {
    $t=$tokens[$i];$id=is_array($t)?$t[0]:0;
    $v=is_array($t)?$t[1]:$t;
    if($depth===0 && $id===T_NAMESPACE) {$unsupported=true;break;}
    if($depth===0 && $id===T_USE) {
        $j=$i+1;
        for(;$j<$n;$j++) {
            $v2=is_array($tokens[$j])?$tokens[$j][1]:$tokens[$j];
            if($v2===';')break;
        }
        if($j<$n)$imports[]=substr($src,$positions[$i],$positions[$j]+1-$positions[$i]);
    }
    $kind=null;
    if($depth===0 && in_array($id,[T_CLASS,T_INTERFACE,T_TRAIT,T_ENUM,T_FUNCTION],true))
        $kind=$id===T_FUNCTION?'function':'class';
    if($kind!==null) {
        $j=$i+1;$name=null;
        while($j<$n) {
            if(is_array($tokens[$j]) && $tokens[$j][0]===T_STRING) {
                $name=$tokens[$j][1];
                break;
            }
            $value=is_array($tokens[$j])?$tokens[$j][1]:$tokens[$j];
            if($value==='(' || $value==='{' || $value===';')break;
            $j++;
        }
        if($name===null)continue;
        $begin=$positions[$i];
        $line=strrpos(substr($src,0,$begin),chr(10));
        $line=$line===false?0:$line+1;
        $prefix=substr($src,$line,$begin-$line);
        if(preg_match('/^[ \t]*(?:(?:abstract|final|readonly)[ \t]+)*$/i',$prefix))
            $begin=$line;
        $k=$j+1;
        for(;$k<$n;$k++) {
            $value=is_array($tokens[$k])?$tokens[$k][1]:$tokens[$k];
            if($value==='{')break;
            if($value===';')break;
        }
        if($k>=$n || (is_array($tokens[$k])?$tokens[$k][1]:$tokens[$k])!=='{')
            continue;
        $nest=1;$end=$k+1;
        for(;$end<$n;$end++) {
            $value=is_array($tokens[$end])?$tokens[$end][1]:$tokens[$end];
            if(!is_array($tokens[$end]) && $value==='{')$nest++;
            if(!is_array($tokens[$end]) && $value==='}')$nest--;
            if($nest===0)break;
        }
        if($nest!==0){$unsupported=true;break;}
        $items[]=[$begin,$positions[$end]+1,$name,$kind];
        $i=$end;
        continue;
    }
    if(!is_array($t) && $v==='{')$depth++;
    if(!is_array($t) && $v==='}')$depth--;
}
echo json_encode(['unsupported'=>$unsupported,'items'=>$items,
    'imports'=>$imports],JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
"""


def inspect(path: Path) -> dict:
    result=subprocess.run(["php","-r",TOKENIZER,str(path)],capture_output=True,
                          text=True,check=True,timeout=35)
    return json.loads(result.stdout)


def proposed(original: bytes, manifest: dict, basename: str):
    if manifest["unsupported"] or not manifest["items"]:
        return None
    if not original.startswith(b"<?php") or b"?>" in original:
        return None
    paths=[]
    edits=[]
    aliases="\n".join(manifest["imports"])
    for begin,end,name,kind in manifest["items"]:
        if begin<5 or end>len(original) or end<=begin:
            return None
        if not re.fullmatch(r"[A-Za-z_][A-Za-z0-9_]*",name):
            return None
        filename=f"{basename}.{name}.php"
        header=b"<?php\n\ndeclare(strict_types=1);\n\n"
        if aliases:
            header+=aliases.encode()+b"\n\n"
        body=original[begin:end].strip(b" \r\n\t")
        helper=header+body+b"\n"
        relative="helpers/"+filename
        require=(f"require_once __DIR__ . '/{relative}';").encode()
        edits.append((begin,end,require))
        paths.append((relative,helper))
    if len(set(p for p,_ in paths))!=len(paths):
        return None
    script=bytearray(original)
    for begin,end,replacement in sorted(edits,reverse=True):
        script[begin:end]=replacement
    return bytes(script),paths


def main() -> int:
    p=argparse.ArgumentParser()
    p.add_argument("--phpcs",type=Path)
    p.add_argument("--phpcbf",type=Path)
    p.add_argument("--self-test",action="store_true")
    args=p.parse_args()
    if args.self_test:
        source=b"<?php\nfinal class Item { public function run(): void {} }\necho 1;\n"
        start=source.index(b"final class")
        end=source.index(b"}",source.index(b"}"))+1
        proposal=proposed(source,{"unsupported":False,"items":[[start,end,"Item","class"]],
                                  "imports":[]},"example")
        assert proposal is not None
        assert b"require_once" in proposal[0]
        assert b"final class Item" in proposal[1][0][1]
        print("SECDEV-02 class fixture extraction tests OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        p.error("Pinned PHPCS required")
    contracts=required_source_fragments()
    tracked=subprocess.check_output(["git","ls-files","-z","--","tests/*.php"],cwd=ROOT).split(b"\0")
    accepted=removed=rejected=0
    for item in tracked:
        if not item:
            continue
        name=item.decode("utf-8")
        if not name.startswith("tests/") or name.count("/")!=1:
            continue
        path=ROOT/name
        if not path.is_file():
            continue
        old=path.read_bytes()
        before=phpcs_counts(args.phpcs,path)
        if before["PSR1.Files.SideEffects.FoundWithSymbols"]==0 and (
            before["PSR1.Classes.ClassDeclaration.MultipleClasses"]==0
        ):
            continue
        try:
            candidate=proposed(old,inspect(path),path.stem)
        except (subprocess.SubprocessError,ValueError):
            candidate=None
        if candidate is None:
            rejected+=1
            continue
        script,helpers=candidate
        if any(fragment in old and fragment not in script for fragment in contracts.get(name,set())):
            rejected+=1
            continue
        paths=[ROOT/"tests"/rel for rel,_ in helpers]
        if any(path.exists() for path in paths):
            rejected+=1
            continue
        for dest,(_,content) in zip(paths,helpers):
            dest.parent.mkdir(parents=True,exist_ok=True)
            dest.write_bytes(content)
            if args.phpcbf and args.phpcbf.is_file():
                subprocess.run([str(args.phpcbf),"--standard=PSR12","--no-cache",str(dest)],
                               capture_output=True,check=False,timeout=45)
        path.write_bytes(script)
        valid=lint(path) and all(lint(dest) for dest in paths)
        if valid:
            after=phpcs_counts(args.phpcs,path)
            new=[phpcs_counts(args.phpcs,dest) for dest in paths]
            valid=(
                not any(new)
                and all(after[key]<=before[key] for key in after)
                and sum(after.values())<sum(before.values())
            )
        if not valid:
            path.write_bytes(old)
            for dest in paths:
                dest.unlink()
            rejected+=1
            continue
        accepted+=1
        gain=sum(before.values())-sum(after.values())
        removed+=gain
        print(f"CLASS EXTRACTION {name}: {gain} findings",flush=True)
    print(f"CLASS EXTRACTION RESULT accepted={accepted} rejected={rejected} removed={removed}",flush=True)
    return 0


if __name__=="__main__":
    raise SystemExit(main())
