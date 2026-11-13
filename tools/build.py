#!/usr/bin/env python3
"""Build a Joomla package, without dependencies or credentials."""
from pathlib import Path
import hashlib,io,zipfile,json,xml.etree.ElementTree as E,argparse,re
ROOT=Path(__file__).resolve().parents[1]
DIST=ROOT/'dist'; DIST.mkdir(exist_ok=True)
parser=argparse.ArgumentParser()
parser.add_argument('--release-version',help='Produce a stable release artifact without changing source manifests')
args=parser.parse_args()
VERSION=E.parse(ROOT/'packages/pkg_jlms.xml').findtext('version')
if args.release_version and not re.fullmatch(r'(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)',args.release_version):
    parser.error('release version must be a stable numeric X.Y.Z')
if args.release_version and tuple(map(int,args.release_version.split('.'))) <= tuple(map(int,VERSION.split('.'))):
    parser.error('release version must be greater than current source package version')
OUTPUT_VERSION=args.release_version or VERSION
def archive(entries):
    out=io.BytesIO()
    with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
        for name,data in sorted(entries):
            info=zipfile.ZipInfo(name,(2026,1,1,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;info.external_attr=0o100644<<16
            z.writestr(info,data)
    return out.getvalue()
def tree(folder):return [(p.relative_to(folder).as_posix(),p.read_bytes()) for p in folder.rglob('*') if p.is_file()]
parts=[('constituents/lib_jlms.zip',archive(tree(ROOT/'libraries/jlms'))),('constituents/com_jlms.zip',archive(tree(ROOT/'component')))]
for provider in ['youtube','vimeo','cloudflarestream','html5']:
    parts.append(('constituents/plg_jlmsvideo_'+provider+'.zip',archive(tree(ROOT/'plugins/jlmsvideo'/provider))))
parts.append(('constituents/plg_task_jlms.zip',archive(tree(ROOT/'plugins/task/jlms'))))
parts.append(('constituents/plg_privacy_jdlearn.zip',archive(tree(ROOT/'plugins/privacy/jdlearn'))))
parts.append(('constituents/plg_system_jlmsmenu.zip',archive(tree(ROOT/'plugins/system/jlmsmenu'))))
parts.append(('constituents/plg_finder_jlms.zip',archive(tree(ROOT/'plugins/finder/jlms'))))
parts.append(('constituents/plg_user_jdlearnconsent.zip',archive(tree(ROOT/'plugins/user/jdlearnconsent'))))
manifest=(ROOT/'packages/pkg_jlms.xml').read_text()
if args.release_version:
    manifest=re.sub(r'(<version>)[^<]+(</version>)',lambda m:m.group(1)+args.release_version+m.group(2),manifest,count=1)
parts.append(('pkg_jlms.xml',manifest.encode()))
data=archive(parts);out=DIST/f'pkg_jlms-{OUTPUT_VERSION}{"" if args.release_version else "-dev"}.zip';out.write_bytes(data)
digest=hashlib.sha256(data).hexdigest();(DIST/'SHA256SUMS.txt').write_text(digest+'  '+out.name+'\n')
print(json.dumps({'package':str(out.relative_to(ROOT)),'sha256':digest,'bytes':len(data),'constituents':10}))
