#!/usr/bin/env python3
"""Targeted high-volume PSR-12 PHP boolean/call reflow with PHPCBF repair.

Earlier formatters rejected a correct line break whenever indentation or
bracing was not subsequently normalized. This pass tries several complete
statement layouts, runs the official PHPCBF formatter on each proposal,
then accepts ONLY lexer-equivalent, lint-clean, source-contract-preserving,
and PHPCS per-file/per-rule nonregressing changes. Every trial is reversible.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import subprocess

from secdev02_lexer_bulk_wrap import ROOT, lex, lint, phpcs_counts
from secdev02_calls_reflow_bulk import required_source_fragments
from secdev02_adaptive_operator_batch import operator_points, propose

LIMIT=120


def improve(
    path: Path, phpcs: Path, phpcbf: Path, protected: set[bytes],
    max_tries: int,
) -> tuple[int,int]:
    old=path.read_bytes()
    oldtoken=lex(path)["hash"]
    baseline=phpcs_counts(phpcs,path)
    if baseline["Generic.Files.LineLength.TooLong"]==0:
        return 0,0
    points=operator_points(path)
    if not points:
        return 0,0
    settings=[
        (112,4,False),(104,4,False),(96,4,False),(108,8,False),
        (108,4,True),(100,4,True),(112,8,True),(92,8,True),
        (88,4,False),(80,4,False),(112,0,False),(105,0,True),
    ]
    best=old
    best_count=sum(baseline.values())
    for width,extra,after in settings[:max_tries]:
        proposed=propose(old,points,width,extra,after)
        if proposed==old:
            continue
        if any(fragment in old and fragment not in proposed for fragment in protected):
            continue
        path.write_bytes(proposed)
        if not lint(path) or lex(path)["hash"]!=oldtoken:
            continue
        for normalized in (False,True):
            if normalized:
                subprocess.run(
                    [str(phpcbf),"--standard=PSR12","--no-cache",str(path)],
                    capture_output=True,timeout=80,check=False,
                )
            if not lint(path) or lex(path)["hash"]!=oldtoken:
                break
            result=path.read_bytes()
            if any(fragment in old and fragment not in result for fragment in protected):
                break
            metrics=phpcs_counts(phpcs,path)
            if any(metrics[k]>baseline[k] for k in metrics):
                if normalized:
                    break
            else:
                score=sum(metrics.values())
                if score<best_count:
                    best_count=score
                    best=result
            if not normalized:
                path.write_bytes(proposed)
    path.write_bytes(best)
    return (int(best!=old), sum(baseline.values())-best_count)


def main()->int:
    p=argparse.ArgumentParser()
    p.add_argument("--phpcs",type=Path)
    p.add_argument("--phpcbf",type=Path)
    p.add_argument("--max-files",type=int,default=180)
    p.add_argument("--max-tries",type=int,default=12)
    p.add_argument("--self-test",action="store_true")
    args=p.parse_args()
    if args.self_test:
        example=b"$result = $object->getSomething()->getAnother()->getThird()->getFourth()->getFifth()->getFinal()->calculate()->getNext()->getLast()->getFinished();\n"
        points=[[i,i+2,"->"] for i in range(len(example)) if example[i:i+2]==b"->"]
        assert propose(example,points,88,4,False)!=example
        print("SECDEV-02 operator statement normalization fixtures OK")
        return 0
    if not args.phpcs or not args.phpcs.is_file() or not args.phpcbf or not args.phpcbf.is_file():
        p.error("pinned PHPCS and PHPCBF required")
    tracked=subprocess.check_output(["git","ls-files","-z","--","*.php"],cwd=ROOT).split(b"\0")
    ranked=[]
    for item in tracked:
        if not item:
            continue
        name=item.decode()
        path=ROOT/name
        if not path.exists():
            continue
        count=sum(len(line)>LIMIT for line in path.read_bytes().splitlines())
        if count:
            ranked.append((count,name))
    ranked.sort(reverse=True)
    protected=required_source_fragments()
    accepted=removed=rejected=0
    for _,name in ranked[:args.max_files]:
        path=ROOT/name
        success,gain=improve(
            path,args.phpcs,args.phpcbf,protected.get(name,set()),args.max_tries,
        )
        if success:
            accepted+=1
            removed+=gain
            print(f"OPERATOR GROUP accepted {name} fixed={gain}",flush=True)
        else:
            rejected+=1
    print(
        f"OPERATOR GROUP RESULT accepted_files={accepted} "
        f"rejected_files={rejected} net_removed={removed}",flush=True,
    )
    return 0


if __name__=="__main__":
    raise SystemExit(main())
