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
call('tools/build.py','--release-version','1.0.0')
archive=root/'dist/pkg_jlms-1.0.0.zip'
assert archive.is_file()
with zipfile.ZipFile(archive) as z:
    m=E.fromstring(z.read('pkg_jlms.xml'))
    assert m.findtext('version')=='1.0.0'
    assert m.findtext('packagename')=='jlms'
    assert m.find('./updateservers/server') is not None
    assert len(m.findall('./files/file'))==10
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
print('Stable package and Joomla feed smoke checks passed (no public deployment)')
