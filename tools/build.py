#!/usr/bin/env python3
"""Build a Joomla package, without dependencies or credentials."""
from pathlib import Path
import hashlib,io,zipfile,json,xml.etree.ElementTree as E,argparse,re,os,subprocess,datetime
ROOT=Path(__file__).resolve().parents[1]
DIST=ROOT/'dist'; DIST.mkdir(exist_ok=True)
parser=argparse.ArgumentParser()
parser.add_argument('--release-version',help='Produce a stable release artifact without changing source manifests')
parser.add_argument('--release-date',help='Release date YYYY-MM-DD (explicit for publication; defaults to the source commit date)')
args=parser.parse_args()
VERSION=E.parse(ROOT/'packages/pkg_jlms.xml').findtext('version')
if args.release_version and not re.fullmatch(r'(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)',args.release_version):
    parser.error('release version must be a stable numeric X.Y.Z')
if args.release_version and tuple(map(int,args.release_version.split('.'))) <= tuple(map(int,VERSION.split('.'))):
    parser.error('release version must be greater than current source package version')
OUTPUT_VERSION=args.release_version or VERSION
def source_date():
    if args.release_date:
        return args.release_date
    if os.getenv('SOURCE_DATE_EPOCH'):
        epoch=int(os.environ['SOURCE_DATE_EPOCH'])
        return datetime.datetime.fromtimestamp(epoch,datetime.timezone.utc).date().isoformat()
    # Commit date, not the time when a runner happened to start: development
    # packages remain byte-for-byte reproducible for an unchanged checkout.
    try:
        return subprocess.check_output(['git','log','-1','--format=%cs'],cwd=ROOT,text=True,stderr=subprocess.DEVNULL).strip()
    except (OSError,subprocess.CalledProcessError):
        parser.error('pass --release-date when building outside a Git checkout')
RELEASE_DATE=source_date()
try:
    if datetime.date.fromisoformat(RELEASE_DATE).isoformat()!=RELEASE_DATE:
        raise ValueError('Non canonical date')
except ValueError:
    parser.error('release date must be YYYY-MM-DD')

# Display names do not change plugin element/group or component/library IDs;
# Joomla upgrades the same installed extensions rather than creating new ones.
CHILD_NAMES={
    'lib_jlms':'JD Learn - Contracts',
    'com_jlms':'COM_JLMS',
    'plg_jlmsvideo_youtube':'PLG_JLMSVIDEO_YOUTUBE',
    'plg_jlmsvideo_vimeo':'PLG_JLMSVIDEO_VIMEO',
    'plg_jlmsvideo_cloudflarestream':'PLG_JLMSVIDEO_CLOUDFLARESTREAM',
    'plg_jlmsvideo_html5':'PLG_JLMSVIDEO_HTML5',
    'plg_task_jlms':'PLG_TASK_JLMS',
    'plg_privacy_jdlearn':'PLG_PRIVACY_JDLEARN',
    'plg_system_jlmsmenu':'PLG_SYSTEM_JLMSMENU',
    'plg_finder_jlms':'PLG_FINDER_JLMS',
    'plg_user_jdlearnconsent':'PLG_USER_JDLEARNCONSENT',
    'plg_actionlog_jdlearn':'PLG_ACTIONLOG_JDLEARN',
}
def stamp_manifest(source,display_name):
    def replace_once(pattern, repl, data, label):
        result,count=re.subn(pattern,lambda _m:repl,data,count=1)
        if count!=1: raise ValueError('Missing '+label+' in Joomla manifest')
        return result
    data=source.decode('utf-8') if isinstance(source,bytes) else source
    data=replace_once(r'<name>[^<]*</name>','<name>'+display_name+'</name>',data,'name')
    data=replace_once(r'<version>[^<]*</version>','<version>'+OUTPUT_VERSION+'</version>',data,'version')
    if re.search(r'<creationDate>[^<]*</creationDate>',data):
        data=replace_once(r'<creationDate>[^<]*</creationDate>','<creationDate>'+RELEASE_DATE+'</creationDate>',data,'creationDate')
    else:
        data=data.replace('<version>'+OUTPUT_VERSION+'</version>',
                          '<version>'+OUTPUT_VERSION+'</version><creationDate>'+RELEASE_DATE+'</creationDate>',1)
    root=E.fromstring(data)
    if root.tag!='extension' or root.findtext('version')!=OUTPUT_VERSION or root.findtext('creationDate')!=RELEASE_DATE:
        raise ValueError('Invalid stamped extension metadata')
    return data.encode('utf-8')
def child_archive(folder,key):
    if key not in CHILD_NAMES: raise ValueError('Unknown JD Learn constituent: '+key)
    manifest='jlms.xml' if key in ('com_jlms','lib_jlms') else key.split('_')[-1]+'.xml'
    entries=[]
    count=0
    for name,data in tree(folder):
        if name==manifest:
            data=stamp_manifest(data,CHILD_NAMES[key])
            count+=1
        entries.append((name,data))
    if count!=1: raise ValueError('Missing manifest '+manifest+' for '+key)
    return archive(entries)
def archive(entries):
    out=io.BytesIO()
    with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
        for name,data in sorted(entries):
            info=zipfile.ZipInfo(name,(2026,1,1,0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;info.external_attr=0o100644<<16
            z.writestr(info,data)
    return out.getvalue()
def tree(folder):return [(p.relative_to(folder).as_posix(),p.read_bytes()) for p in folder.rglob('*') if p.is_file()]
# Version the installed WAM assets by package AND content: development builds
# may retain the same semantic version across several PRs. The fingerprint also
# covers provider modules imported dynamically with the WAM script query string.
asset_sources=[(name,data) for name,data in tree(ROOT/'component/media')
               if name!='joomla.asset.json']
for key in ('youtube','vimeo','cloudflarestream','html5'):
    asset_sources += [(key+'/'+name,data) for name,data in tree(ROOT/'plugins/jlmsvideo'/key/'media')]
asset_digest=hashlib.sha256()
for name,data in sorted(asset_sources):
    asset_digest.update(name.encode('utf-8')+b'\0'+data+b'\0')
asset_version=OUTPUT_VERSION+'-'+asset_digest.hexdigest()[:12]
component_entries=[]
for name,data in tree(ROOT/'component'):
    if name == 'media/joomla.asset.json':
        assets=json.loads(data.decode('utf-8'))
        assets['version']=asset_version
        data=(json.dumps(assets,ensure_ascii=False,indent=2)+'\n').encode('utf-8')
    if name == 'jlms.xml':
        data=stamp_manifest(data,CHILD_NAMES['com_jlms'])
    component_entries.append((name,data))
parts=[('constituents/lib_jlms.zip',child_archive(ROOT/'libraries/jlms','lib_jlms')),('constituents/com_jlms.zip',archive(component_entries))]
for provider in ['youtube','vimeo','cloudflarestream','html5']:
    parts.append(('constituents/plg_jlmsvideo_'+provider+'.zip',child_archive(ROOT/'plugins/jlmsvideo'/provider,'plg_jlmsvideo_'+provider)))
parts.append(('constituents/plg_task_jlms.zip',child_archive(ROOT/'plugins/task/jlms','plg_task_jlms')))
parts.append(('constituents/plg_privacy_jdlearn.zip',child_archive(ROOT/'plugins/privacy/jdlearn','plg_privacy_jdlearn')))
parts.append(('constituents/plg_system_jlmsmenu.zip',child_archive(ROOT/'plugins/system/jlmsmenu','plg_system_jlmsmenu')))
parts.append(('constituents/plg_finder_jlms.zip',child_archive(ROOT/'plugins/finder/jlms','plg_finder_jlms')))
parts.append(('constituents/plg_user_jdlearnconsent.zip',child_archive(ROOT/'plugins/user/jdlearnconsent','plg_user_jdlearnconsent')))
parts.append(('constituents/plg_actionlog_jdlearn.zip',child_archive(ROOT/'plugins/actionlog/jdlearn','plg_actionlog_jdlearn')))
manifest=stamp_manifest((ROOT/'packages/pkg_jlms.xml').read_bytes(),'JD Learn')
parts.append(('pkg_jlms.xml',manifest))
data=archive(parts);out=DIST/f'pkg_jlms-{OUTPUT_VERSION}{"" if args.release_version else "-dev"}.zip';out.write_bytes(data)
digest=hashlib.sha256(data).hexdigest();(DIST/'SHA256SUMS.txt').write_text(digest+'  '+out.name+'\n')
print(json.dumps({'package':str(out.relative_to(ROOT)),'sha256':digest,'bytes':len(data),'constituents':12}))
