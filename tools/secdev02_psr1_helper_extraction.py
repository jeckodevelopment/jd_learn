#!/usr/bin/env python3
"""SECDEV-02: split global functions out of executable CLI test fixtures.

Each candidate is reversible, preserves the exact original function bodies,
and is admitted only when both PHP files have valid syntax, source contracts
are intact, and the pinned PHPCS PSR12 audit has no new file/rule findings.
Production and Joomla extension files are deliberately excluded.
"""
from __future__ import annotations

import argparse
from collections import Counter
import json
from pathlib import Path
import re
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

ANALYZE = r"""
$source=file_get_contents($argv[1]);
$tokens=token_get_all($source, TOKEN_PARSE);
$positions=[];$offset=0;
foreach($tokens as $token) {
    $value=is_array($token)?$token[1]:$token;
    $positions[]=$offset;
    $offset+=strlen($value);
}
$depth=0;$functions=[];$imports=[];$invalid=false;
$n=count($tokens);
for($i=0;$i<$n;$i++) {
    $token=$tokens[$i];
    $id=is_array($token)?$token[0]:0;
    $text=is_array($token)?$token[1]:$token;
    if($depth===0 && $id===T_NAMESPACE) $invalid=true;
    if($depth===0 && in_array($id,[T_CLASS,T_INTERFACE,T_TRAIT,T_ENUM],true)) $invalid=true;
    if($depth===0 && $id===T_USE) {
        $j=$i+1;
        while($j<$n) {
            $item=is_array($tokens[$j])?$tokens[$j][1]:$tokens[$j];
            if($item===';') break;
            $j++;
        }
        if($j<$n) $imports[]=[$positions[$i],$positions[$j]+1];
    }
    if($depth===0 && $id===T_FUNCTION) {
        $j=$i+1;
        while($j<$n && is_array($tokens[$j]) &&
            in_array($tokens[$j][0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT,
                T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,
                T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG],true)) $j++;
        if($j>=$n || !is_array($tokens[$j]) || $tokens[$j][0]!==T_STRING) continue;
        $k=$j+1;$parentheses=0;
        for(;$k<$n;$k++) {
            $v=is_array($tokens[$k])?$tokens[$k][1]:$tokens[$k];
            if($v==='(') $parentheses++;
            if($v===')') $parentheses--;
            if(!is_array($tokens[$k]) && $v==='{' && $parentheses===0) break;
            if(!is_array($tokens[$k]) && $v===';' && $parentheses===0) break;
        }
        if($k>=$n || (is_array($tokens[$k])?$tokens[$k][1]:$tokens[$k])!=='{') continue;
        $nested=1;$end=$k+1;
        for(;$end<$n;$end++) {
            $v=is_array($tokens[$end])?$tokens[$end][1]:$tokens[$end];
            if(!is_array($tokens[$end]) && $v==='{') $nested++;
            if(!is_array($tokens[$end]) && $v==='}') $nested--;
            if($nested===0) break;
        }
        if($nested!==0) {$invalid=true; continue;}
        $functions[]=[$positions[$i],$positions[$end]+1];
        $i=$end;
        continue;
    }
    if(!is_array($token) && $text==='{') $depth++;
    if(!is_array($token) && $text==='}') $depth--;
    if($depth<0) $invalid=true;
}
echo json_encode(['invalid'=>$invalid || $depth!==0,
    'functions'=>$functions,'imports'=>$imports],JSON_THROW_ON_ERROR);
"""

def inspect(path: Path) -> dict:
    p = subprocess.run(["php", "-r", ANALYZE, str(path)], capture_output=True,
                       text=True, timeout=35, check=False)
    if p.returncode or not p.stdout.strip():
        raise ValueError("PHP tokenizer rejected source")
    return json.loads(p.stdout)


def rewrite(data: bytes, manifest: dict, helper_name: str) -> tuple[bytes, bytes] | None:
    functions = manifest["functions"]
    if manifest["invalid"] or not functions:
        return None
    if not data.startswith(b"<?php") or b"?>" in data:
        return None
    if any(beg < 5 or end <= beg for beg, end in functions):
        return None
    for left, right in zip(functions, functions[1:]):
        if left[1] > right[0]:
            return None
    header = b"<?php\n\n"
    strict = re.search(rb"\bdeclare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;", data[:400])
    if strict:
        header += b"declare(strict_types=1);\n\n"
    aliases = [data[a:b] for a, b in manifest["imports"]]
    helper = header + b"\n".join(aliases) + (b"\n\n" if aliases else b"")
    helper += b"\n\n".join(data[a:b] for a,b in functions) + b"\n"
    script = bytearray(data)
    for a,b in reversed(functions):
        script[a:b] = b""
    # Must import the declarations before the first executable statement.
    insertion = 5
    if strict:
        insertion = strict.end()
    require_line = b"\n\nrequire_once __DIR__ . '/helpers/" + helper_name.encode() + b"';\n"
    script[insertion:insertion] = require_line
    return bytes(script), helper


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--phpcs", type=Path)
    ap.add_argument("--phpcbf", type=Path)
    ap.add_argument("--self-test", action="store_true")
    args=ap.parse_args()
    if args.self_test:
        sample=b"<?php\n\ndeclare(strict_types=1);\n\nfunction f(int $i): int {return $i+1;}\necho f(1);\n"
        # Self-test the insertion and exact body preservation.
        start=sample.index(b"function f")
        end=sample.index(b"}",start)+1
        version=rewrite(sample,{"invalid":False,"imports":[],"functions":[[start,end]]},"f.functions.php")
        assert version is not None
        assert b"function f(int $i): int {return $i+1;}" in version[1]
        assert b"require_once __DIR__" in version[0]
        assert b"function f" not in version[0]
        print("SECDEV-02 PSR1 helper extraction fixtures OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        ap.error("Pinned PHPCS executable required")
    contracts=required_source_fragments()
    accepted=rejected=removed=0
    paths=subprocess.check_output(["git","ls-files","-z","--","tests/*.php"],cwd=ROOT).split(b"\0")
    for raw in paths:
        if not raw:
            continue
        relative=raw.decode()
        if not relative.startswith("tests/") or relative.count("/")!=1:
            continue
        path=ROOT/relative
        if not path.is_file():
            continue
        original=path.read_bytes()
        before=phpcs_counts(args.phpcs,path)
        if before["PSR1.Files.SideEffects.FoundWithSymbols"]<1:
            continue
        helper_name=path.stem+".functions.php"
        helper_path=ROOT/"tests/helpers"/helper_name
        if helper_path.exists():
            continue
        try:
            proposal=rewrite(original,inspect(path),helper_name)
        except (ValueError,TimeoutError):
            proposal=None
        if proposal is None:
            print(f'PSR1 REJECT {relative}: no safe extraction manifest',flush=True)
            rejected+=1
            continue
        amended,helper=proposal
        required=contracts.get(relative,set())
        if any(fragment in original and fragment not in amended for fragment in required):
            print(f'PSR1 REJECT {relative}: protected source contract',flush=True)
            rejected+=1
            continue
        helper_path.parent.mkdir(parents=True,exist_ok=True)
        helper_path.write_bytes(helper)
        path.write_bytes(amended)
        valid=lint(path) and lint(helper_path)
        if valid and args.phpcbf and args.phpcbf.is_file():
            subprocess.run([str(args.phpcbf),"--standard=PSR12","--no-cache",str(helper_path)],
                           capture_output=True,timeout=35,check=False)
            valid=lint(helper_path)
        if valid:
            after=phpcs_counts(args.phpcs,path)
            helper_after=phpcs_counts(args.phpcs,helper_path)
            # Existing file must improve. The new PHP helper must be clean to
            # satisfy the repository-wide no-new-file-finding gate.
            valid=(after["PSR1.Files.SideEffects.FoundWithSymbols"]==0 and
                   not helper_after and
                   all(after[rule]<=count for rule,count in before.items()) and
                   sum(after.values())<sum(before.values()))
        if not valid:
            print(f'PSR1 REJECT {relative}: lint/rule issues; helper={dict(locals().get("helper_after", {}))} existing={dict(locals().get("after", {}))}',flush=True)
            path.write_bytes(original)
            helper_path.unlink()
            rejected+=1
            continue
        accepted+=1
        removed+=sum(before.values())-sum(after.values())
        print(f"SECDEV-02 PSR1 relocated {relative} with no new findings",flush=True)
    print(f"SECDEV-02 PSR1 extracted_files={accepted} rejected_files={rejected} "
          f"net_removed={removed}",flush=True)
    return 0


if __name__=="__main__":
    raise SystemExit(main())
