#!/usr/bin/env python3
"""Mass source-safe long PHP literal reflow, with AST-equivalence verification.

Rewrite substantial quoted PHP string constants into line-wrapped adjacent
constant fragments. The value, PHP AST and all source contracts must remain
exactly the same. A file is accepted only after an actual pinned PSR12
improvement, PHP syntax, zero new rule warnings and AST equivalence.
"""
from __future__ import annotations

import argparse
import json
from pathlib import Path
import subprocess
import tempfile

from secdev02_lexer_bulk_wrap import ROOT, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments

TOKEN_SCAN = r"""
$source = file_get_contents($argv[1]);
$offset=0; $found=[];
foreach (token_get_all($source) as $token) {
  $value=is_array($token)?$token[1]:$token;
  if (is_array($token) && $token[0]===T_CONSTANT_ENCAPSED_STRING)
    $found[]=[$offset, strlen($value)];
  $offset+=strlen($value);
}
echo json_encode($found, JSON_THROW_ON_ERROR);
"""
LIMIT = 120


def tokens(path: Path) -> list[list[int]]:
    x = subprocess.run(
        ["php", "-r", TOKEN_SCAN, str(path)], check=True, capture_output=True,
        text=True, timeout=30,
    )
    return json.loads(x.stdout)


def split_plain_literal(
    literal: bytes, first_width: int, later_width: int, indent: int,
    period_on_next_line: bool,
) -> bytes | None:
    if len(literal) < 47 or len(literal) < first_width + 8:
        return None
    quote = literal[:1]
    if quote not in (b"'", b'"') or literal[-1:] != quote:
        return None
    source = literal[1:-1]
    # Never split escaped, interpolated, multi-line or uncertain PHP strings.
    if any(x in source for x in (b"\\", b"\r", b"\n", quote)):
        return None
    if quote == b'"' and b"$" in source:
        return None
    if source and source[0] == 0:
        return None
    if first_width < 18 or later_width < 24:
        return None
    splits=[]
    remaining=source
    width=first_width
    for _ in range(20):
        if len(remaining) <= width:
            break
        options=[]
        for i in range(max(15,width-28),min(len(remaining)-12,width)+1):
            if remaining[i] & 0xC0 == 0x80:
                continue
            before=remaining[i-1:i]
            if before in (b" ", b",", b";", b":", b"/", b"?", b"&", b"=", b"-", b"_", b"."):
                options.append((i, 1))
            elif remaining[i:i+1] in (b" ", b",", b";"):
                options.append((i, 0))
        if options:
            position=max(options, key=lambda pair:(pair[1],pair[0]))[0]
        else:
            position=width
            while position>15 and remaining[position] & 0xC0 == 0x80:
                position-=1
        if position<15 or position>=len(remaining)-12:
            return None
        splits.append(remaining[:position])
        remaining=remaining[position:]
        width=later_width
    splits.append(remaining)
    if len(splits)<2 or b"".join(splits)!=source:
        return None
    if any(quote in part for part in splits):
        return None
    spaces=b" " * min(indent+4,28)
    separator=(b" .\n"+spaces) if not period_on_next_line else (b"\n"+spaces+b". ")
    return b"(" + separator.join(quote+p+quote for p in splits)+b")"


def propose(data: bytes, locations: list[list[int]], width: int, period_next: bool) -> bytes:
    changes=[]
    for start,size in locations:
        row_start=data.rfind(b"\n",0,start)+1
        row_end=data.find(b"\n",start)
        if row_end<0:
            row_end=len(data)
        if row_end-row_start<=LIMIT or start+size>row_end:
            continue
        prefix=data[row_start:start]
        if b"<?" in prefix or b"?>" in prefix:
            continue
        indent=len(prefix)-len(prefix.lstrip(b" \t"))
        if indent>28 or start-row_start>102:
            continue
        first=min(width-(start-row_start)-2,103-(start-row_start))
        later=min(width-6,110-indent-6)
        literal=data[start:start+size]
        candidate=split_plain_literal(literal,first,later,indent,period_next)
        if candidate is not None:
            changes.append((start,size,candidate))
    out=bytearray(data)
    for start,size,patch in reversed(changes):
        out[start:start+size]=patch
    return bytes(out)


def equivalent(script: Path, composer: Path, original: Path, amended: Path) -> bool:
    response=subprocess.run(
        ["php",str(script),str(composer),str(original),str(amended)],
        capture_output=True,timeout=45,
    )
    return response.returncode==0


def main() -> int:
    p=argparse.ArgumentParser()
    p.add_argument("--phpcs",type=Path)
    p.add_argument("--phpcbf",type=Path)
    p.add_argument("--parser-dir",type=Path)
    p.add_argument("--max-files",type=int,default=500)
    p.add_argument("--self-test",action="store_true")
    args=p.parse_args()
    if args.self_test:
        sample=b"'"+(b'{"field_name":"sample","enabled":false},'*9)+b"'"
        changed=split_plain_literal(sample,64,90,4,False)
        assert changed is not None and b" .\n" in changed
        assert split_plain_literal(b"'short'",40,90,4,False) is None
        assert split_plain_literal(b"'\\\\path ' + b'a'*90+b"'",40,90,4,False) is None
        print("SECDEV-02 AST-guarded long PHP literal self-test OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file():
        p.error("valid pinned --phpcs required")
    if not args.parser_dir or not (args.parser_dir/"vendor/autoload.php").is_file():
        p.error("PHP-parser composer installation required")
    parser=ROOT/"tools/secdev02_literal_ast_equivalence.php"
    protected=required_source_fragments()
    raw=subprocess.check_output(["git","ls-files","-z","--","*.php"],cwd=ROOT).split(b"\0")
    entries=[]
    for item in raw:
        if not item:
            continue
        path=ROOT/item.decode()
        if path.exists():
            total=sum(len(x)>LIMIT for x in path.read_bytes().splitlines())
            if total:
                entries.append((total,item.decode()))
    entries.sort(reverse=True)
    accepted=rejected=removed=0
    with tempfile.TemporaryDirectory() as temp:
        original_path=Path(temp)/"original.php"
        for _,name in entries[:args.max_files]:
            path=ROOT/name
            original=path.read_bytes()
            matches=tokens(path)
            if not matches:
                continue
            before=phpcs_counts(args.phpcs,path)
            if not before["Generic.Files.LineLength.TooLong"]:
                continue
            original_path.write_bytes(original)
            best=original
            best_total=sum(before.values())
            required=protected.get(name,set())
            for width in (98,112,120,88,104,76):
                for period_next in (False,True):
                    trial=propose(original,matches,width,period_next)
                    if trial==original or any(s in original and s not in trial for s in required):
                        continue
                    path.write_bytes(trial)
                    if not lint(path):
                        continue
                    if args.phpcbf and args.phpcbf.is_file():
                        subprocess.run(
                            [str(args.phpcbf),"--standard=PSR12","--no-cache",str(path)],
                            capture_output=True,timeout=60,
                        )
                    if not lint(path):
                        continue
                    if not equivalent(parser,args.parser_dir,original_path,path):
                        continue
                    candidate=path.read_bytes()
                    if any(s in original and s not in candidate for s in required):
                        continue
                    after=phpcs_counts(args.phpcs,path)
                    if any(after[k]>before[k] for k in after):
                        continue
                    total=sum(after.values())
                    if total<best_total:
                        best_total=total
                        best=candidate
            path.write_bytes(best)
            if best!=original:
                gain=sum(before.values())-best_total
                removed+=gain
                accepted+=1
                print(f"LITERAL accepted {name} net_fixed={gain}",flush=True)
            else:
                rejected+=1
    print(f"AST LITERALS accepted_files={accepted} rejected={rejected} net_removed={removed}",flush=True)
    return 0


if __name__=="__main__":
    raise SystemExit(main())
