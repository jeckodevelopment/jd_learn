#!/usr/bin/env python3
"""Generate/validate a native Joomla package update feed (no network I/O)."""
import argparse
import hashlib
from pathlib import Path
import re
import xml.etree.ElementTree as ET
import zipfile

p=argparse.ArgumentParser()
p.add_argument('--version',required=True)
p.add_argument('--zip',required=True,dest='package')
p.add_argument('--url',required=True)
p.add_argument('--output',required=True)
p.add_argument('--joomla',default=r'6\\.[0-9]+')
p.add_argument('--php-minimum',default='8.3')
args=p.parse_args()
if not re.fullmatch(r'[1-9]\d*\.\d+\.\d+',args.version):
    p.error('stable semantic version X.Y.Z required')
if not re.fullmatch(r'https://github\.com/jeckodevelopment/jd_learn/releases/download/v'+re.escape(args.version)+r'/pkg_jlms-'+re.escape(args.version)+r'\.zip',args.url):
    p.error('ZIP URL must reference the exact public JD Learn GitHub release asset')
if not re.fullmatch(r'[0-9a-zA-Z().|\\\[\]+?*^$-]+',args.joomla):
    p.error('invalid Joomla compatibility pattern')
zip_path=Path(args.package)
assert zip_path.name==f'pkg_jlms-{args.version}.zip'
with zipfile.ZipFile(zip_path) as z:
    assert z.testzip() is None
    manifest=ET.fromstring(z.read('pkg_jlms.xml'))
    assert manifest.attrib.get('type')=='package'
    assert manifest.findtext('packagename')=='jlms'
    assert manifest.findtext('version')==args.version
    servers=manifest.findall('./updateservers/server')
    assert any(s.text=='https://www.jeckodevelopment.com/updates/jdlearn.xml' and s.attrib.get('type')=='extension' for s in servers)
digest=hashlib.sha256(zip_path.read_bytes()).hexdigest()
updates=ET.Element('updates')
entry=ET.SubElement(updates,'update')
for tag,value in [('name','JD Learn'),('description','JD Learn for Joomla'),('element','pkg_jlms'),('type','package'),('version',args.version)]:
    ET.SubElement(entry,tag).text=value
downloads=ET.SubElement(entry,'downloads')
ET.SubElement(downloads,'downloadurl',{'type':'full','format':'zip'}).text=args.url
ET.SubElement(ET.SubElement(entry,'tags'),'tag').text='stable'
ET.SubElement(entry,'targetplatform',{'name':'joomla','version':args.joomla})
ET.SubElement(entry,'php_minimum').text=args.php_minimum
ET.SubElement(entry,'sha256').text=digest
out=Path(args.output)
out.parent.mkdir(parents=True,exist_ok=True)
ET.indent(updates,space='  ')
ET.ElementTree(updates).write(out,encoding='utf-8',xml_declaration=True)
parsed=ET.parse(out).getroot()
assert parsed.findtext('./update/sha256')==digest
print(f'Generated {out}: {args.version}, sha256={digest}')
