#!/usr/bin/env python3
"""Build a Joomla package, without dependencies or credentials."""
from pathlib import Path
import hashlib,io,zipfile,json,xml.etree.ElementTree as E
ROOT=Path(__file__).resolve().parents[1]
DIST=ROOT/'dist'; DIST.mkdir(exist_ok=True)
VERSION=E.parse(ROOT/'packages/pkg_jlms.xml').findtext('version')
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
parts.append(('pkg_jlms.xml',(ROOT/'packages/pkg_jlms.xml').read_bytes()))
data=archive(parts);out=DIST/f'pkg_jlms-{VERSION}-dev.zip';out.write_bytes(data)
digest=hashlib.sha256(data).hexdigest();(DIST/'SHA256SUMS.txt').write_text(digest+'  '+out.name+'\n')
print(json.dumps({'package':str(out.relative_to(ROOT)),'sha256':digest,'bytes':len(data),'constituents':9}))
