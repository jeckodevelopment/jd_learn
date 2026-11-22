#!/usr/bin/env python3
"""Offline smoke tests for guarded Joomla package release preparation."""
from pathlib import Path
import hashlib
import subprocess
import sys
import tempfile
import xml.etree.ElementTree as E
import zipfile

root=Path(__file__).resolve().parents[1]
def call(*args,expected=0):
    p=subprocess.run([sys.executable,*map(str,args)],cwd=root,text=True,capture_output=True)
    if (p.returncode==0)!=(expected==0):
        raise AssertionError(f'Unexpected exit {p.returncode}: {args}: {p.stdout} {p.stderr}')
call('tools/build.py','--release-version','1.0.0','--release-date','2026-11-17')
call('tests/extension-manifest-metadata.py','--zip','dist/pkg_jlms-1.0.0.zip','--version','1.0.0','--date','2026-11-17')
archive=root/'dist/pkg_jlms-1.0.0.zip'
assert archive.is_file()
with zipfile.ZipFile(archive) as z:
    m=E.fromstring(z.read('pkg_jlms.xml'))
    assert m.findtext('version')=='1.0.0'
    assert m.findtext('packagename')=='jlms'
    assert m.find('./updateservers/server') is not None
    assert len(m.findall('./files/file'))==12
with tempfile.TemporaryDirectory() as tmp:
    feed=Path(tmp)/'jdlearn.xml'
    url='https://github.com/jeckodevelopment/jd_learn/releases/download/v1.0.0/pkg_jlms-1.0.0.zip'
    call('tools/release_feed.py','--version','1.0.0','--zip',archive,'--url',url,'--output',feed)
    record=E.parse(feed).getroot().find('./update')
    assert record.findtext('element')=='pkg_jlms'
    assert record.findtext('type')=='package'
    assert record.findtext('version')=='1.0.0'
    assert record.findtext('sha256')==hashlib.sha256(archive.read_bytes()).hexdigest()
    assert record.find('targetplatform').attrib['version']==r'6\.[0-9]+'
    call('tools/release_feed.py','--version','1.0.0','--zip',archive,'--url','https://example.invalid/malicious.zip','--output',feed,expected=1)
    call('tools/build.py','--release-version','bad-version',expected=1)
    # DOC-10: bind release notes, source commit and feed to exactly one archive.
    sums=Path(tmp)/'SHA256SUMS.txt'
    sums.write_text(f"{hashlib.sha256(archive.read_bytes()).hexdigest()}  {archive.name}\n")
    evidence=Path(tmp)/'evidence.json'
    note=root/'docs/RELEASE_NOTES_CANDIDATE_1.0.0.md'
    args=[
        '--version','1.0.0','--commit','a'*40,'--package',archive,
        '--feed',feed,'--checksums',sums,'--notes',note,'--output',evidence,
    ]
    call('tools/release_candidate_evidence.py',*args)
    call('tools/release_candidate_evidence.py',*args,'--verify')
    sums.write_text('0'*64+f"  {archive.name}\n")
    call('tools/release_candidate_evidence.py',*args,'--verify',expected=1)
    sums.write_text(f"{hashlib.sha256(archive.read_bytes()).hexdigest()}  {archive.name}\n")
    with evidence.open('r',encoding='utf-8') as stream:
        import json
        record=json.load(stream)
    assert record['status']=='candidate_not_approved'
    assert record['manual_joomla_acceptance']=='not_asserted'
    record['package_sha256']='0'*64
    evidence.write_text(json.dumps(record))
    call('tools/release_candidate_evidence.py',*args,'--verify',expected=1)
print('Stable package and Joomla feed smoke checks passed (no public deployment)')
