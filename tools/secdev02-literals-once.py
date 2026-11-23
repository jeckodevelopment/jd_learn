#!/usr/bin/env python3
"""Mass remediation of long PHP constant-string literals, monotonic PSR-12."""
import json
import subprocess
from pathlib import Path
from collections import Counter

ROOT=Path(__file__).resolve().parents[1]
SNIFFER=ROOT/'.runtime/secdev02-phpcs/vendor/bin/phpcs'
WRAPPER=ROOT/'tools/secdev02-literals-once.php'

def run(*cmd):
    return subprocess.run(cmd,cwd=ROOT,capture_output=True,timeout=35)
def sniff(name):
    result=run(str(SNIFFER),'--standard=PSR12','--report=json','--no-cache',name)
    report=json.loads(result.stdout)
    return Counter(m['source'] for f in report['files'].values() for m in f['messages'])

files=[name.decode() for name in run('git','ls-files','-z','--','*.php').stdout.split(b'\0') if name]
fixed=[]; skipped=0
for i,name in enumerate(files):
    if name=='tools/secdev02-literals-once.php':continue
    path=ROOT/name; original=path.read_bytes()
    if not any(len(line)>120 for line in original.splitlines()):continue
    if original.count(b"'") + original.count(b'"') < 2:continue
    before=sniff(name); initial=sum(before.values())
    best=original; reduction=0; modebest=-1
    for mode in range(4):
        path.write_bytes(original)
        result=run('php',str(WRAPPER),name,str(mode))
        if result.returncode:continue
        now=path.read_bytes()
        if now==original:continue
        if run('php','-l',name).returncode:continue
        after=sniff(name)
        improvement=initial-sum(after.values())
        if improvement>reduction and not after-before:
            best=now;reduction=improvement;modebest=mode
    path.write_bytes(best)
    if reduction:fixed.append((name,reduction,modebest))
    else:skipped+=1
    if i%40==0:print("LITERAL CHECKPOINT",i,len(files),"accepted",len(fixed),"removed",sum(v for _,v,_ in fixed),flush=True)
print("LITERAL RESULT",len(fixed),"files",sum(v for _,v,_ in fixed),"removed; rejected",skipped,flush=True)
print("LITERAL TOP",sorted(fixed,key=lambda x:-x[1])[:50],flush=True)
